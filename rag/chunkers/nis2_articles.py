import re
from typing import List, Dict, Any

# Casa "Artigo" mesmo quando vem quebrado tipo "Ar tigo", "Ar t i g o", etc.
ARTIGO_WORD = r"A\s*r\s*t\s*i\s*g\s*o"

# Casa linhas tipo:
# "Artigo 23.o", "Artigo 23.º", "Ar tigo 23.\no", etc.
ARTICLE_RE = re.compile(
    rf"(?mi)^\s*{ARTIGO_WORD}\s+(?P<num>\d+)\s*(?:\.\s*)?(?:[ºo]\s*)?$"
)

# Também tolerante a "CAPÍTULO" com acento ou sem
CHAPTER_RE = re.compile(r"(?mi)^\s*CAP[IÍ]TULO\s+(?P<roman>[IVXLCDM]+)\s*$")


def split_nis2_by_articles(text: str) -> List[Dict[str, Any]]:
    chapters = list(CHAPTER_RE.finditer(text))
    articles = list(ARTICLE_RE.finditer(text))
    if not articles:
        return []

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
                "control_family": "NIS2-ART",
                "control_code": f"NIS2-ART-{art_num}",
            }
        })

    return chunks