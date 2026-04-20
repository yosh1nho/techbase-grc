from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import subprocess
import os
import time

app = FastAPI(title="MemPalace GRC Bridge")

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PALACE_DIR = os.path.join(BASE_DIR, "mempalace_data")

if not os.path.exists(PALACE_DIR):
    os.makedirs(PALACE_DIR)
    subprocess.run(["mempalace", "init", PALACE_DIR])

UTF8_ENV = {**os.environ, "PYTHONIOENCODING": "utf-8"}

class MineRequest(BaseModel):
    content: str
    source_id: str

class SearchRequest(BaseModel):
    query: str

@app.post("/mine")
async def mine_memory(data: MineRequest):
    try:
        filename = f"{data.source_id}_{int(time.time())}.txt"
        file_path = os.path.join(PALACE_DIR, filename)
        
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(data.content)
        
        process = subprocess.run(
            ["mempalace", "mine", PALACE_DIR],
            cwd=PALACE_DIR,
            capture_output=True,
            text=True,
            encoding='utf-8',
            env=UTF8_ENV
        )
        
        if process.returncode != 0:
            raise Exception(f"MemPalace recusou: {process.stderr or process.stdout}")
            
        return {"status": "success", "message": "Memória guardada com sucesso!"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/search")
async def search_memory(data: SearchRequest):
    try:
        process = subprocess.run(
            ["mempalace", "search", data.query],
            cwd=PALACE_DIR,
            capture_output=True,
            text=True,
            encoding='utf-8',
            env=UTF8_ENV
        )
        
        if process.returncode != 0:
            raise Exception(f"MemPalace recusou: {process.stderr or process.stdout}")
            
        return {"status": "success", "context": process.stdout}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))