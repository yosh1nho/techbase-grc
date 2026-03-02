#!/usr/bin/env python3
#script de exclusão de documento por ID
import os, argparse
from pinecone import Pinecone
from dotenv import load_dotenv
from pathlib import Path

load_dotenv(Path(__file__).resolve().parents[1] / ".env")

def must_env(name: str) -> str:
    v = os.environ.get(name)
    if not v:
        raise RuntimeError(f"Missing env var: {name}")
    return v

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--tenant", required=True)
    ap.add_argument("--doc-id", required=True)
    args = ap.parse_args()

    pc = Pinecone(api_key=must_env("PINECONE_API_KEY"))
    index = pc.Index(must_env("PINECONE_INDEX"))
    namespace = str(args.tenant)

    # delete por filtro (records-based)
    index.delete(namespace=namespace, filter={"doc_id": {"$eq": args.doc_id}})

    print("OK: deleted by filter", args.doc_id, "in namespace", namespace)

if __name__ == "__main__":
    raise SystemExit(main())