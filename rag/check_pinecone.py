#!/usr/bin/env python3
import os
from pinecone import Pinecone
from dotenv import load_dotenv
from pathlib import Path

# Carrega as variáveis de ambiente
load_dotenv(Path(__file__).resolve().parents[1] / ".env")

def main():
    pc = Pinecone(api_key=os.environ.get("PINECONE_API_KEY"))
    index = pc.Index(os.environ.get("PINECONE_INDEX"))
    
    # Pede as estatísticas reais ao Pinecone
    stats = index.describe_index_stats()
    
    print("\n=== O QUE ESTÁ REALMENTE DENTRO DO PINECONE ===")
    if not stats.namespaces:
        print("O Pinecone diz que não tem namespaces nenhuns!")
    
    for ns, info in stats.namespaces.items():
        nome_visivel = ns if ns != "" else "[Vazio / Default]"
        print(f"-> Nome exato para o script: '{ns}' | Vetores: {info.vector_count}")
    print("===============================================\n")

if __name__ == "__main__":
    main()