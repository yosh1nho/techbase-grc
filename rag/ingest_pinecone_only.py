#!/usr/bin/env python3

import os, json, uuid, argparse, re
from datetime import datetime, timezone

from pinecone import Pinecone
import tiktoken
import pdfplumber

from dotenv import load_dotenv
from pathlib import Path

load_dotenv(Path(__file__).resolve().parents[1] / ".env")

from chunkers.qnrcs_controls import split_qnrcs_by_control
from chunkers.generic import chunk_by_tokens
from chunkers.nis2_articles import split_nis2_by_articles


def must_env(name):
    v = os.environ.get(name)
    if not v:
        raise RuntimeError(f"Missing env var: {name}")
    return v


def now():
    return datetime.now(timezone.utc).isoformat()


def clean_text(s):
    """
    Limpa o texto extraído pelo pdfplumber:
    - Remove hífens de quebra de linha (word-wrap de PDFs): "infor-\nmação" → "informação"
    - Colapsa espaços múltiplos e tabs
    - Remove espaços antes de pontuação: "palavra ." → "palavra."
    - Normaliza quebras de linha (máx 2 seguidas)
    """
    if not s:
        return ""

    # Normaliza fim de linha
    s = s.replace("\r\n", "\n").replace("\r", "\n")

    # Junta palavras hifenizadas no final de linha: "infor-\nmação" → "informação"
    s = re.sub(r"-\n([a-záéíóúâêîôûãõàèìòùäëïöüçñ\w])", r"\1", s, flags=re.IGNORECASE)

    # Remove espaços antes de pontuação
    s = re.sub(r"\s+([.,;:!?)])", r"\1", s)

    # Colapsa espaços/tabs múltiplos numa linha
    s = re.sub(r"[ \t]+", " ", s)

    # Normaliza quebras de linha (máx 2)
    s = re.sub(r"\n{3,}", "\n\n", s)

    # Remove espaços no início/fim de cada linha
    s = "\n".join(line.strip() for line in s.splitlines())

    return s.strip()


def sanitize_meta(meta):
    """
    Pinecone não aceita null.
    Remove valores None.
    """
    clean = {}
    for k, v in meta.items():
        if v is None:
            continue
        clean[k] = v
    return clean


def load_pdf_pages(path):
    """
    Extrai texto página a página com pdfplumber.
    Usa extract_words() + tolerâncias afinadas para eliminar
    espaços espúrios que o pypdf introduzia em PDFs com kerning
    (ex: "inf or mação" → "informação").
    """
    pages = []

    with pdfplumber.open(path) as pdf:
        for i, page in enumerate(pdf.pages):

            # extract_words agrupa glifos em palavras reais
            # x_tolerance: distância horizontal máxima entre glifos da mesma palavra
            # y_tolerance: distância vertical (para PDFs com linhas próximas)
            words = page.extract_words(
                x_tolerance=3,
                y_tolerance=3,
                keep_blank_chars=False,
                use_text_flow=True,   # respeita ordem de leitura natural
                extra_attrs=[],
            )

            # Reconstrói o texto linha a linha preservando quebras de parágrafo
            if not words:
                pages.append({"page": i + 1, "text": ""})
                continue

            lines = []
            current_line = []
            prev_bottom = None

            for w in words:
                top = w.get("top", 0)
                bottom = w.get("bottom", top + 1)

                # Nova linha se o topo do word está abaixo do bottom anterior + tolerância
                if prev_bottom is not None and top > prev_bottom + 2:
                    lines.append(" ".join(current_line))
                    current_line = []

                current_line.append(w["text"])
                prev_bottom = bottom

            if current_line:
                lines.append(" ".join(current_line))

            pages.append({
                "page": i + 1,
                "text": "\n".join(lines),
            })

    return pages


def split_if_too_big(text, max_tokens=380, overlap=40):

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


def choose_chunks(text, profile, max_tokens, overlap):

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

    return split_qnrcs_by_control(text) or split_nis2_by_articles(text) or chunk_by_tokens(text, max_tokens, overlap)


def build_prefix(meta):

    cc = meta.get("control_code")

    if not cc:
        return ""

    art = meta.get("article_code") or ""
    chap = meta.get("chapter") or ""

    bits = [cc]

    if art:
        bits.append(art)

    if chap:
        bits.append(f"Capítulo {chap}")

    return " | ".join(bits) + "\n"


def main():

    ap = argparse.ArgumentParser()

    ap.add_argument("--file", required=True)
    ap.add_argument("--tenant", required=True)
    ap.add_argument("--doc-id", default=None)
    ap.add_argument("--doc-name", default=None)
    ap.add_argument("--profile", default="auto", choices=["auto","qnrcs","nis2","generic"])
    ap.add_argument("--max-tokens", type=int, default=450)
    ap.add_argument("--overlap", type=int, default=60)

    args = ap.parse_args()

    started = now()

    try:

        pinecone_key = must_env("PINECONE_API_KEY")
        index_name = must_env("PINECONE_INDEX")

        doc_id = args.doc_id or str(uuid.uuid4())
        doc_name = args.doc_name or os.path.basename(args.file)

        namespace = str(args.tenant)

        pages = load_pdf_pages(args.file)

        chunks = []

        for p in pages:

            text = clean_text(p["text"])

            if not text:
                continue

            page_chunks = choose_chunks(
                text,
                args.profile,
                args.max_tokens,
                args.overlap
            )

            for ch in page_chunks:

                meta = ch.get("meta") or {}

                meta["page_number"] = p["page"]

                chunks.append({
                    "text": ch["text"],
                    "meta": meta
                })

        pc = Pinecone(api_key=pinecone_key)

        index = pc.Index(index_name)

        records = []

        rec_i = 0

        for ch in chunks:

            ch_text = ch["text"]

            meta = sanitize_meta(ch.get("meta") or {})

            prefix = build_prefix(meta)

            ch_text = prefix + ch_text

            parts = split_if_too_big(ch_text)

            for part_idx, part in enumerate(parts):

                record = {
                    "_id": f"{doc_id}:{rec_i}",

                    "text": part,

                    "tenant": namespace,
                    "doc_id": doc_id,
                    "doc_name": doc_name,
                    "chunk_index": rec_i,
                    "source_file": os.path.basename(args.file),

                    "page_number": meta.get("page_number",0),

                    **meta,

                    "control_part": part_idx,
                    "control_parts": len(parts),

                    "ingested_at": started
                }

                records.append(record)

                rec_i += 1

        batch = 80

        for i in range(0, len(records), batch):

            index.upsert_records(
                namespace,
                records[i:i+batch]
            )

        print(json.dumps({
            "ok":True,
            "doc_id":doc_id,
            "chunks":len(records),
            "namespace":namespace,
            "finished_at":now()
        }))

    except Exception as e:

        print(json.dumps({
            "ok":False,
            "error":str(e),
            "started_at":started,
            "finished_at":now()
        }))


if __name__ == "__main__":
    main()