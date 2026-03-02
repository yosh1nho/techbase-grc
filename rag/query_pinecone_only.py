#!/usr/bin/env python3
import os, json, argparse
from pinecone import Pinecone
from dotenv import load_dotenv
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]  # sai de /rag para a raiz do projeto
load_dotenv(ROOT / ".env")
#script de consulta de documentos em Pinecone
def must_env(name: str) -> str:
    v = os.environ.get(name)
    if not v:
        raise RuntimeError(f"Missing env var: {name}")
    return v

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--tenant", required=True, help="Namespace (ex.: 102)")
    ap.add_argument("--text", required=True, help="Pergunta / consulta")
    ap.add_argument("--topk", type=int, default=8)
    ap.add_argument("--doc-id", default=None, help="Opcional: filtrar por doc_id")
    ap.add_argument("--debug", action="store_true", help="Imprime resposta bruta do Pinecone")
    args = ap.parse_args()

    pc = Pinecone(api_key=must_env("PINECONE_API_KEY"))
    index = pc.Index(must_env("PINECONE_INDEX"))

    namespace = str(args.tenant)

    flt = None
    if args.doc_id:
        flt = {"doc_id": {"$eq": args.doc_id}}

    # IMPORTANTÍSSIMO:
    # - NÃO existe "fields" dentro de query.
    # - "fields" é parâmetro separado do método.
    query = {
        "inputs": {"text": args.text},
        "top_k": args.topk,
    }
    if flt:
        query["filter"] = flt

    res = index.search_records(
        namespace=namespace,
        query=query,
        fields=[
            "text",
            "doc_id","doc_name","version","chunk_index","source_file",
            "section_type",
            "control_code","control_family",
            "article_num","article_code","chapter"
],
    )

    if args.debug:
        print(json.dumps(res, ensure_ascii=False, indent=2))
        return 0

    hits = res["result"]["hits"]

    out = []
    for h in hits:
        # Alguns retornos vêm com campos diretamente no hit (h["text"]),
        # outros vêm em h["fields"][...]. Vamos suportar os 2:
        fields = h.get("fields") or {}

        def get_field(k):
            return h.get(k) if h.get(k) is not None else fields.get(k)

        out.append({
            "id": h.get("_id"),
            "score": h.get("_score"),
            "doc_id": get_field("doc_id"),
            "doc_name": get_field("doc_name"),
            "version": get_field("version"),
            "chunk_index": get_field("chunk_index"),
            "source_file": get_field("source_file"),
            "section_type": get_field("section_type"),
            "control_code": get_field("control_code"),
            "control_family": get_field("control_family"),
            "article_num": get_field("article_num"),
            "article_code": get_field("article_code"),
            "chapter": get_field("chapter"),
            "text": get_field("text"),
        })

    print(json.dumps({"ok": True, "query": args.text, "topk": args.topk, "results": out}, ensure_ascii=False))
    return 0

if __name__ == "__main__":
    raise SystemExit(main())