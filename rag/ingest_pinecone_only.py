#!/usr/bin/env python3
# Ingestão de PDFs/TXT para Pinecone (Integrated Embedding / Records)

import os, sys, json, uuid, argparse, re
from datetime import datetime, timezone

from pinecone import Pinecone
import tiktoken

# Fallback PDF reader
from pypdf import PdfReader

# Melhor extractor (se instalado)
try:
    import pdfplumber
except Exception:
    pdfplumber = None

from dotenv import load_dotenv
from pathlib import Path

load_dotenv(Path(__file__).resolve().parents[1] / ".env")

from chunkers.qnrcs_controls import split_qnrcs_by_control
from chunkers.generic import chunk_by_tokens
from chunkers.nis2_articles import split_nis2_by_articles


def must_env(name: str) -> str:
    v = os.environ.get(name)
    if not v:
        raise RuntimeError(f"Missing env var: {name}")
    return v


def now_utc_iso():
    return datetime.now(timezone.utc).isoformat()


def load_text_pdfplumber(path: str) -> str:
    # pdfplumber costuma reconstruir melhor palavras/linhas
    if pdfplumber is None:
        raise RuntimeError("pdfplumber not installed")
    parts = []
    with pdfplumber.open(path) as pdf:
        for i, page in enumerate(pdf.pages):
            # extract_text pode retornar None em algumas páginas
            txt = page.extract_text() or ""
            parts.append(f"\n\n--- page {i+1} ---\n{txt}")
    return "".join(parts)


def load_text_pypdf(path: str) -> str:
    reader = PdfReader(path)
    parts = []
    for i, page in enumerate(reader.pages):
        parts.append(f"\n\n--- page {i+1} ---\n{page.extract_text() or ''}")
    return "".join(parts)


def load_text(path: str) -> str:
    ext = os.path.splitext(path)[1].lower()
    if ext == ".pdf":
        # tenta pdfplumber primeiro, fallback pro pypdf
        try:
            return load_text_pdfplumber(path)
        except Exception:
            return load_text_pypdf(path)

    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        return f.read()


def clean_text(s: str) -> str:
    s = s.replace("\r\n", "\n").replace("\r", "\n")
    s = re.sub(r"[ \t]+", " ", s)
    s = re.sub(r"\n{3,}", "\n\n", s)
    return s.strip()


def split_if_too_big(text: str, max_tokens: int = 380, overlap: int = 40):
    enc = tiktoken.get_encoding("cl100k_base")
    toks = enc.encode(text)
    if len(toks) <= max_tokens:
        return [text]

    out = []
    start = 0
    n = len(toks)

    while start < n:
        end = min(start + max_tokens, n)
        out.append(enc.decode(toks[start:end]).strip())
        if end == n:
            break
        start = max(0, end - overlap)

    return out


def choose_chunks(text: str, profile: str, max_tokens: int, overlap: int):
    if profile == "qnrcs":
        chunks = split_qnrcs_by_control(text)
        if not chunks:
            chunks = chunk_by_tokens(text, max_tokens, overlap)
        return chunks

    if profile == "nis2":
        chunks = split_nis2_by_articles(text)
        if not chunks:
            chunks = chunk_by_tokens(text, max_tokens, overlap)
        return chunks

    if profile == "generic":
        return chunk_by_tokens(text, max_tokens, overlap)

    # auto
    return split_qnrcs_by_control(text) or split_nis2_by_articles(text) or chunk_by_tokens(text, max_tokens, overlap)


def build_prefix(ch_meta: dict) -> str:
    cc = ch_meta.get("control_code")
    if not cc:
        return ""

    art = ch_meta.get("article_code") or ""
    chap = ch_meta.get("chapter") or ""
    bits = [cc]
    if art:
        bits.append(art)
    if chap:
        bits.append(f"Capítulo {chap}")

    return " | ".join(bits) + "\n"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--file", required=True)
    ap.add_argument("--tenant", required=True)  # namespace
    ap.add_argument("--doc-id", default=None)
    ap.add_argument("--doc-name", default=None)
    ap.add_argument("--version", default="v1")
    ap.add_argument("--max-tokens", type=int, default=450)
    ap.add_argument("--overlap", type=int, default=60)
    ap.add_argument("--profile", default="auto", choices=["auto", "qnrcs", "nis2", "generic"])
    args = ap.parse_args()

    started_at = now_utc_iso()

    try:
        pinecone_key = must_env("PINECONE_API_KEY")
        index_name = must_env("PINECONE_INDEX")

        doc_id = args.doc_id or str(uuid.uuid4())
        doc_name = args.doc_name or os.path.basename(args.file)
        namespace = str(args.tenant)

        text = clean_text(load_text(args.file))
        if not text:
            raise ValueError("Empty extracted text (PDF pode ser scan; precisa OCR).")

        chunks = choose_chunks(text, args.profile, args.max_tokens, args.overlap)

        pc = Pinecone(api_key=pinecone_key)
        index = pc.Index(index_name)

        records = []
        rec_i = 0

        for ch in chunks:
            ch_text = ch["text"]
            ch_meta = ch.get("meta") or {}

            prefix = build_prefix(ch_meta)
            ch_text = prefix + ch_text

            parts = split_if_too_big(ch_text, max_tokens=380, overlap=40)

            for part_idx, part in enumerate(parts):
                record = {
                    "_id": f"{doc_id}:{rec_i}",
                    "text": part,

                    "tenant": namespace,
                    "doc_id": doc_id,
                    "doc_name": doc_name,
                    "version": args.version,
                    "chunk_index": rec_i,
                    "source_file": os.path.basename(args.file),
                    "ingested_at": started_at,

                    **ch_meta,

                    "control_part": part_idx,
                    "control_parts": len(parts),
                }
                records.append(record)
                rec_i += 1

        batch_size = 80  # <= 96
        for i in range(0, len(records), batch_size):
            index.upsert_records(namespace, records[i:i + batch_size])

        print(json.dumps({
            "ok": True,
            "doc_id": doc_id,
            "doc_name": doc_name,
            "chunks": len(records),
            "namespace": namespace,
            "profile": args.profile,
            "pdf_extractor": "pdfplumber" if pdfplumber is not None else "pypdf",
            "started_at": started_at,
            "finished_at": now_utc_iso(),
        }, ensure_ascii=False))
        return 0

    except Exception as e:
        print(json.dumps({
            "ok": False,
            "error": str(e),
            "started_at": started_at,
            "finished_at": now_utc_iso(),
        }, ensure_ascii=False))
        return 1


if __name__ == "__main__":
    raise SystemExit(main())