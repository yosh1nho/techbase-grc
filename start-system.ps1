# API Fake Acronis
Start-Process powershell -ArgumentList "-NoExit", "-Command", "
cd 'C:\Users\Administrador\Desktop\projetoTechbase\Middleware AcronisWazuh\teste acronis';
.\venv\Scripts\activate;
uvicorn fake_acronis_dynamic:app --port 9999 --reload
"

# Frontend (Vite)
Start-Process powershell -ArgumentList "-NoExit", "-Command", "
cd 'C:\Users\Administrador\Desktop\projetoTechbase\app\techbase-grc';
npm run dev
"

# Laravel
Start-Process powershell -ArgumentList "-NoExit", "-Command", "
cd 'C:\Users\Administrador\Desktop\projetoTechbase\app\techbase-grc';
C:\php\php.exe artisan serve
"

# Laravel Scheduler
Start-Process powershell -ArgumentList "-NoExit", "-Command", "
cd 'C:\Users\Administrador\Desktop\projetoTechbase\app\techbase-grc';
while (`$true) {
    C:\php\php.exe artisan schedule:run;
    Start-Sleep -Seconds 60;
}
"

# Laravel Queue Worker (Pinecone ingest + outros jobs assincronos)
Start-Process powershell -ArgumentList "-NoExit", "-Command", "
cd 'C:\Users\Administrador\Desktop\projetoTechbase\app\techbase-grc';
C:\php\php.exe artisan queue:work --timeout=300 --tries=1 --sleep=3
"

# ==========================================
# MemPalace API (Cérebro Histórico em Python)
# ==========================================
Start-Process powershell -ArgumentList "-NoExit", "-Command", "
cd 'C:\Users\Administrador\Desktop\projetoTechbase\app\techbase-grc\mempalace-api';
.\venv\Scripts\activate;
uvicorn api:app --port 8001 --reload
"