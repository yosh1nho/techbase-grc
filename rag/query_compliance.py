#!/usr/bin/env python3
# script de consulta de documentos em Pinecone baseado em compliance (robusto)

import os, json, argparse
from pinecone import Pinecone


def must_env(name: str) -> str:
    v = os.environ.get(name)
    if not v:
        raise RuntimeError(f"Missing env var: {name}")
    return v


def norm_text(s: str) -> str:
    if not s:
        return ""
    return " ".join(s.replace("\r", "").split())


def build_block(item, idx: int) -> str:
    label = item.get("label") or "Trecho"
    score = item.get("score", 0.0)
    src = item.get("source_file") or "unknown"
    code = item.get("key") or "n/a"
    txt = item.get("text") or ""
    return (
        f"[{idx}] {label}: {code} | score={score:.3f} | fonte={src}\n"
        f"{txt}\n"
    )


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--tenant", required=True)
    ap.add_argument("--text", required=True, help="Pergunta / afirmação do utilizador")
    ap.add_argument("--topk", type=int, default=12, help="TopK inicial (busca)")
    ap.add_argument("--max-context", type=int, default=4, help="Qtd final de trechos no contexto")
    ap.add_argument(
        "--mode",
        choices=["qnrcs", "nis2", "auto"],
        default="auto",
        help="qnrcs=dedup por control_code; nis2=dedup por artigo; auto=tenta ambos",
    )
    ap.add_argument("--doc-id", default=None, help="Opcional: filtrar por doc_id específico")
    ap.add_argument("--min-score", type=float, default=0.80)
    ap.add_argument(
        "--prefer-section",
        default=None,
        help='Opcional: "controls" ou "article" (prioriza esse section_type)',
    )
    ap.add_argument("--debug", action="store_true", help="Mostra hits normalizados (diagnóstico)")
    args = ap.parse_args()

    pc = Pinecone(api_key=must_env("PINECONE_API_KEY"))
    index = pc.Index(must_env("PINECONE_INDEX"))
    namespace = str(args.tenant)

    flt = {"doc_id": {"$eq": args.doc_id}} if args.doc_id else None

    res = index.search_records(
        namespace=namespace,
        query={
            "inputs": {"text": args.text},
            "top_k": args.topk,
            **({"filter": flt} if flt else {}),
        },
        fields=[
            "text",
            "doc_id",
            "doc_name",
            "version",
            "page_number",
            "chunk_index",
            "source_file",
            "section_type",
            # QNRCS:
            "control_code",
            "control_family",
            # NIS2:
            "article_num",
            "article_code",
            "chapter",
            # split parts (se existirem)
            "control_part",
            "control_parts",
        ],
    )

    hits = res["result"]["hits"]

    # Normaliza hits: tenta pegar campos em h, h["fields"] e h["metadata"]
    norm = []
    for h in hits:
        score = h.get("_score")
        if score is None or float(score) < args.min_score:
            continue

        fields = h.get("fields") or {}
        meta = h.get("metadata") or {}
        def get_field(k):
            return h.get(k) if h.get(k) is not None else fields.get(k) if fields.get(k) is not None else meta.get(k)

        def get(k):
            v = h.get(k)
            if v is not None:
                return v
            v = fields.get(k)
            if v is not None:
                return v
            return meta.get(k)

        item = {
            "id": h.get("_id"),
            "score": float(score),
            "text": norm_text(get("text")),
            "doc_id": get("doc_id"),
            "doc_name": get("doc_name"),
            "version": get("version"),
            "chunk_index": get("chunk_index"),
            "source_file": get("source_file"),
            "section_type": get("section_type"),
            "control_code": get("control_code"),
            "control_family": get("control_family"),
            "article_num": get("article_num"),
            "article_code": get("article_code"),
            "chapter": get("chapter"),
            "control_part": get("control_part"),
            "control_parts": get("control_parts"),
        }
        norm.append(item)

    if args.debug:
        print(json.dumps({"normalized_hits": norm[:5]}, ensure_ascii=False, indent=2))
        return 0

    # Se for NIS2, filtra para artigos, mas só se existir algum article no retorno
    if args.mode == "nis2":
        if any(x.get("section_type") == "article" for x in norm):
            norm = [x for x in norm if x.get("section_type") == "article"]

    # Prioridade por section_type (se pedido)
    if args.prefer_section:
        pref = args.prefer_section
        norm.sort(key=lambda x: (0 if x.get("section_type") == pref else 1, -x["score"]))
    else:
        norm.sort(key=lambda x: -x["score"])

    chosen = []
    seen = set()

    def decide_mode(item):
        if args.mode in ["qnrcs", "nis2"]:
            return args.mode
        # auto
        if item.get("control_code"):
            return "qnrcs"
        if item.get("article_num") is not None:
            return "nis2"
        return "other"

    for it in norm:
        mode = decide_mode(it)

        if mode == "qnrcs":
            key = it.get("control_code") or it.get("control_family") or it["id"]
            label = "Controlo"
        elif mode == "nis2":
            # dedup por artigo (via control_code NIS2-ART-23, ou fallback)
            key = it.get("control_code") or it.get("article_num") or it.get("article_code") or it["id"]
            label = "Artigo"
        else:
            key = it["id"]
            label = "Trecho"

        if key in seen:
            continue

        it["key"] = str(key)
        it["label"] = label
        chosen.append(it)
        seen.add(key)

        if len(chosen) >= args.max_context:
            break

    context = "\n---\n".join(build_block(it, i + 1) for i, it in enumerate(chosen)).strip()

    out = {
        "ok": True,
        "query": args.text,
        "tenant": namespace,
        "topk_initial": args.topk,
        "max_context": args.max_context,
        "min_score": args.min_score,
        "context": context,
        "sources": [
            {
                "id": it["id"],
                "score": it["score"],
                "section_type": it.get("section_type"),
                "control_code": it.get("control_code"),
                "control_family": it.get("control_family"),
                "article_num": it.get("article_num"),
                "doc_name": it.get("doc_name"),
                "page_number": it.get("page_number"),
                "source_file": it.get("source_file"),
                "chunk_index": it.get("chunk_index"),
            }
            for it in chosen
        ],
    }

    print(json.dumps(out, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())