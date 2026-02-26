import tiktoken
from typing import List, Dict, Any
#Script Chunker Generico
def chunk_by_tokens(text: str, max_tokens=450, overlap=60) -> List[Dict[str, Any]]:
    enc = tiktoken.get_encoding("cl100k_base")
    toks = enc.encode(text)
    out = []
    start = 0
    n = len(toks)

    while start < n:
        end = min(start + max_tokens, n)
        chunk = enc.decode(toks[start:end]).strip()
        if chunk:
            out.append({"text": chunk, "meta": {"section_type": "generic"}})
        if end == n:
            break
        start = max(0, end - overlap)

    return out