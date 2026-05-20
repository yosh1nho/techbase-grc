#!/usr/bin/env python3
# script de exclusão total de um namespace
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
    # required=False e default="" garante que se não passarmos nada, ele assume vazio
    ap.add_argument("--tenant", required=False, default="", help="Namespace a limpar")
    args = ap.parse_args()

    pc = Pinecone(api_key=must_env("PINECONE_API_KEY"))
    index = pc.Index(must_env("PINECONE_INDEX"))
    namespace = str(args.tenant)

    try:
        index.delete(delete_all=True, namespace=namespace)
        print(f"OK: todos os registos foram apagados no namespace '{namespace}'")
    except Exception as e:
        if "Namespace not found" in str(e):
            print(f"OK: O namespace '{namespace}' já estava completamente vazio.")
        else:
            raise e

if __name__ == "__main__":
    raise SystemExit(main())