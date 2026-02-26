#!/usr/bin/env python3
# script para ingestão de documentos PDF em Pinecone com base no seu tipo (QNRCs, NIS2, etc)
import os, sys, json, uuid, argparse, re
from datetime import datetime, timezone

from pypdf import PdfReader
from pinecone import Pinecone
import tiktoken

from chunkers.qnrcs_controls import split_qnrcs_by_control
from chunkers.generic import chunk_by_tokens
from chunkers.nis2_articles import split_nis2_by_articles, split_nis2_recitals


def must_env(name: str) -> str:
    v = os.environ.get(name)
    if not v:
        raise RuntimeError(f"Missing env var: {name}")
    return v


def now_utc_iso():
    return datetime.now(timezone.utc).isoformat()


def load_text(path: str) -> str:
    ext = os.path.splitext(path)[1].lower()
    if ext == ".pdf":
        reader = PdfReader(path)
        parts = []
        for i, page in enumerate(reader.pages):
            parts.append(f"\n\n--- page {i+1} ---\n{page.extract_text() or ''}")
        return "".join(parts)
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


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--file", required=True)
    ap.add_argument("--tenant", required=True)  # namespace
    ap.add_argument("--doc-id", default=None)
    ap.add_argument("--doc-name", default=None)
    ap.add_argument("--version", default="v1")
    ap.add_argument("--max-tokens", type=int, default=450)
    ap.add_argument("--overlap", type=int, default=60)
    ap.add_argument("--profile", default="auto", choices=["auto","qnrcs","nis2","generic"])
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

        # Escolhe chunker
        if args.profile == "qnrcs":
            chunks = split_qnrcs_by_control(text)
            if not chunks:
                chunks = chunk_by_tokens(text, args.max_tokens, args.overlap)
        elif args.profile == "generic":
            chunks = chunk_by_tokens(text, args.max_tokens, args.overlap)
        elif args.profile == "nis2":
            chunks = split_nis2_by_articles(text) or chunk_by_tokens(text, args.max_tokens, args.overlap)
        else:  # auto
            chunks = split_qnrcs_by_control(text) or chunk_by_tokens(text, args.max_tokens, args.overlap)

        pc = Pinecone(api_key=pinecone_key)
        index = pc.Index(index_name)

        # Monta records (integrated embedding: upsert_records)
        records = []
        rec_i = 0

        for ch in chunks:
            ch_text = ch["text"]
            ch_meta = ch.get("meta") or {}

            # split de segurança para não estourar limite por record
            parts = split_if_too_big(ch_text, max_tokens=380, overlap=40)

            for part_idx, part in enumerate(parts):
                records.append({
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
                })
                rec_i += 1

        batch_size = 80  # <= 96
        for i in range(0, len(records), batch_size):
            index.upsert_records(namespace, records[i:i + batch_size])

        print(json.dumps({
            "ok": True,
            "doc_id": doc_id,
            "doc_name": doc_name,
            "chunks": len(records),  # total records (após split)
            "namespace": namespace,
            "profile": args.profile,
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