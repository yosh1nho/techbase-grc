import re
from pypdf import PdfReader

from chunkers.qnrcs_controls import split_qnrcs_by_control
from chunkers.generic import chunk_by_tokens

PDF = r"..\public\mock\frameworks\cncs-qnrcs-2019.pdf"

def pdf_to_text(path):
    reader = PdfReader(path)
    parts = []
    for i, page in enumerate(reader.pages):
        parts.append(f"\n\n--- page {i+1} ---\n{page.extract_text() or ''}")
    return "".join(parts)

text = pdf_to_text(PDF)

chunks = split_qnrcs_by_control(text)
print("controls chunks:", len(chunks))

# mostra os primeiros 10 control_codes encontrados
for c in chunks[:70]:
    print(c["meta"].get("control_code"), c["meta"].get("control_family"))

# fallback só pra comparar
g = chunk_by_tokens(text)
print("generic chunks:", len(g))