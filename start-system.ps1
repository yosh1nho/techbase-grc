# API Fake Acronis
Start-Process powershell -ArgumentList "-NoExit", "-Command", "
cd 'C:\Users\Administrador\Desktop\projetoTechbase\Middleware AcronisWazuh\teste acronis';
.\venv\Scripts\activate;
uvicorn fake_acronis_dynamic:app --port 9999 --reload
"

# Frontend (npm)
Start-Process powershell -ArgumentList "-NoExit", "-Command", "
cd 'C:\Users\Administrador\Desktop\projetoTechbase\app\techbase-grc';
npm run dev
"

# PHP Server
Start-Process powershell -ArgumentList "-NoExit", "-Command", "
cd 'C:\Users\Administrador\Desktop\projetoTechbase\app\techbase-grc';
C:\Users\Administrador\.config\herd-lite\bin\php.exe -c C:\php-config\php.ini -S 127.0.0.1:8000 -t public
"