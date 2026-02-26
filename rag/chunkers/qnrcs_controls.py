import re
from typing import List, Dict, Any
#script chunker do QNRCS
CONTROL_RE = re.compile(
    r'(?m)^[ \t•\-–—]*'
    r'(?P<code>(ID|PR|DE|RS|RC)\.[A-Z]{2})'
    r'\s*[\.\-–—]\s*'
    r'(?P<num>\d+)'
    r'\b[^\n]*'
)

REF_LINE = re.compile(r'^\s*(ISO/IEC|NIST|COBIT|CIS(\s+CSC)?|R\.N\.)\b.*$', re.IGNORECASE)

def strip_reference_lines(s: str) -> str:
    out = []
    for line in s.splitlines():
        # remove linhas muito “de referência”
        if REF_LINE.match(line.strip()):
            continue
        out.append(line)
    return "\n".join(out).strip()

def split_qnrcs_by_control(text: str):
    matches = list(CONTROL_RE.finditer(text))
    chunks = []
    if not matches:
        return []

    for i, m in enumerate(matches):
        start = m.start()
        end = matches[i + 1].start() if i + 1 < len(matches) else len(text)

        base = m.group("code")     # ID.GA
        num = m.group("num")       # 1
        code_full = f"{base}-{num}"
        family = base

        chunk_text = text[start:end].strip()
        if "Descrição" not in chunk_text:
            continue
        
        chunk_text = strip_reference_lines(chunk_text)
        idx = chunk_text.find("Descrição")
        if idx != -1:
            chunk_text = chunk_text[idx:].strip()

        if not chunk_text:
            continue
        
        chunks.append({
            "text": chunk_text,
            "meta": {
                "section_type": "controls",
                "control_code": code_full,
                "control_family": family,
            }
        })

    return chunks