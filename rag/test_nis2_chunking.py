from pypdf import PdfReader
from chunkers.nis2_articles import split_nis2_by_articles

PDF = r"..\public\mock\frameworks\NIS2.pdf"

def pdf_to_text(path):
    reader = PdfReader(path)
    return "\n".join((p.extract_text() or "") for p in reader.pages)

text = pdf_to_text(PDF)
chunks = split_nis2_by_articles(text)
print("nis2 article chunks:", len(chunks))
print(chunks[0]["meta"] if chunks else None)