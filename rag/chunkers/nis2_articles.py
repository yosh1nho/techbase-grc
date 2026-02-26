import re
from typing import List, Dict, Any

ARTICLE_RE = re.compile(r'(?m)^\s*Artigo\s+(?P<num>\d+)\.o\s*$')
CHAPTER_RE = re.compile(r'(?m)^\s*CAP[IÍ]TULO\s+(?P<roman>[IVXLCDM]+)\s*$')
RECITAL_RE = re.compile(r'(?m)^\s*\((?P<num>\d+)\)\s+')

def split_nis2_by_articles(text: str) -> List[Dict[str, Any]]:
    """
    1 chunk = 1 Artigo (com metadata: chapter_roman, article_num, section_type="article")
    """
    # Vamos guardar o capítulo mais recente antes de cada artigo (se existir)
    chapters = list(CHAPTER_RE.finditer(text))
    articles = list(ARTICLE_RE.finditer(text))

    if not articles:
        return []

    # helper: capítulo vigente para uma posição
    chapter_idx = 0
    def current_chapter(pos: int):
        nonlocal chapter_idx
        while chapter_idx + 1 < len(chapters) and chapters[chapter_idx + 1].start() < pos:
            chapter_idx += 1
        return chapters[chapter_idx].group("roman") if chapters and chapters[chapter_idx].start() < pos else None

    chunks = []
    for i, m in enumerate(articles):
        start = m.start()
        end = articles[i + 1].start() if i + 1 < len(articles) else len(text)

        art_num = int(m.group("num"))
        chapter_roman = current_chapter(start)

        chunk_text = text[start:end].strip()
        if not chunk_text:
            continue

        chunks.append({
            "text": chunk_text,
            "meta": {
                "section_type": "article",
                "chapter": chapter_roman,
                "article_num": art_num,
                "article_code": f"Artigo {art_num}.o",
            }
        })

    return chunks

def split_nis2_recitals(text: str) -> List[Dict[str, Any]]:
    """
    (Opcional) Chunk por Considerando (n)
    """
    matches = list(RECITAL_RE.finditer(text))
    if not matches:
        return []

    out = []
    for i, m in enumerate(matches):
        start = m.start()
        end = matches[i+1].start() if i+1 < len(matches) else len(text)
        n = int(m.group("num"))
        chunk_text = text[start:end].strip()
        if chunk_text:
            out.append({
                "text": chunk_text,
                "meta": {"section_type": "recital", "recital_num": n}
            })
    return out