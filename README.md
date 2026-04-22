# Techbase GRC

Techbase GRC é uma plataforma de Governance, Risk, and Compliance focada na gestão de ativos, análise de riscos, planos de tratamento e verificação de conformidade com normativas de segurança (como o quadro CNCS).

Este projeto integra potentes capacidades de Inteligência Artificial para análise automatizada, geração de avaliações, RAG (Retrieval-Augmented Generation) para pesquisa documental e gestão de um "cérebro histórico" das ações na plataforma.

## 🚀 Tecnologias e Arquitetura

O sistema é composto por múltiplos serviços integrados:

### Backend Principal (PHP / Laravel)
- **Laravel 12** (PHP ^8.2) para a lógica central, API e servir a aplicação.
- **Base de Dados**: SQLite (por defeito, configurável no `.env`).
- **RBAC Integrado**: Controlo de acessos baseado em Funções (Roles) e Permissões.
- **Jobs e Filas**: Processamento em background para análise de documentos e sincronização de APIs externas.

### Frontend (JavaScript / Vite / Tailwind)
- **Vite** para compilação rápida de assets.
- **TailwindCSS v4** para a estilização da interface de utilizador.
- **Blade Templates** do Laravel para renderização do lado do servidor.
- Utilização de ícones via biblioteca `lucide`.

### Inteligência Artificial e RAG
- **Google Gemini API**: Utilizado como o LLM principal do sistema (via `App\Services\GeminiClient`) para gerar análises de ativos, avaliações automáticas de controlo e analisar documentos de evidência extraídos.
- **Pinecone (Vector Database)**: Utilizado para a arquitetura de *Retrieval-Augmented Generation* (RAG). Scripts Python localizados na pasta `rag/` (ex. `ingest_pinecone_only.py`, `query_pinecone_only.py`) tratam da vetorização e pesquisa semântica dos documentos de compliance.
- **MemPalace API (Python / FastAPI)**: Uma API Python local (situada na diretoria `mempalace-api/`) que funciona como um "Cérebro Histórico" ou memória a longo prazo. Ela guarda e minera contextos históricos que são passados ao Gemini para fornecer respostas mais alicerçadas no histórico da plataforma.

### Integrações com Ferramentas Externas
- **Wazuh API**: Integração nativa para sincronização de ativos de segurança e obtenção de alertas (Security SIEM/XDR).
- **Acronis API**: Sincronização de relatórios de backup e estado de proteção de ativos.

## 📦 Funcionalidades Principais

- **Dashboard Integrado**: KPIs dinâmicos e centralização de alertas do Wazuh, planos e compliance.
- **Gestão de Ativos**: Sincronização automática de ativos, gestão manual e avaliação do nível de criticidade com apoio do LLM (Gemini).
- **Gestão Documental e de Evidências**: Upload de políticas e procedimentos. Os documentos são submetidos a um pipeline de ingestão para o Pinecone após aprovação, ficando disponíveis para consulta no Chat RAG.
- **Conformidade (Compliance)**: Mapeamento de controlos contra frameworks normativas. Associação de documentos como evidências de cumprimento e avaliações baseadas nestas evidências.
- **Análise de Risco e Tratamento**: Registo e mitigação de riscos; criação de planos de tratamento (Treatment Plans) estruturados por tarefas, que incluem comentários alimentados também ao MemPalace.
- **Avaliações (Assessments)**: Processamento e emissão de avaliações guiadas pela inteligência artificial a partir de evidências recolhidas.
- **Assistente IA (Chat RAG)**: Capacidade do utilizador interrogar a base de conhecimentos de compliance da empresa (documentos aprovados), cruzando as respostas diretamente com as fontes (Pinecone + Gemini).
- **Relatórios**: Geração de relatórios de conformidade e status geral (ex: relatórios estilo CNCS).

## 🛠️ Configuração e Instalação

1. **Clonar e instalar dependências PHP:**
   ```bash
   composer install
   ```

2. **Instalar dependências Frontend:**
   ```bash
   npm install
   ```

3. **Configurar o Ambiente (`.env`):**
   Duplicar o `.env.example` para `.env` e preencher as chaves críticas:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   **Chaves Essenciais:**
   - Base de Dados (ex: SQLite default ou MySQL)
   - `GEMINI_API_KEY` (Para chamadas ao LLM)
   - `PINECONE_API_KEY` e `PINECONE_INDEX` (Para a indexação RAG em Python)
   - Credenciais das APIs: `WAZUH_API_USER`, `WAZUH_API_PASS`, `ACRONIS_API_*`
   - Opcional: Variáveis relativas ao Python (`PYTHON_BIN` se não usar a versão do sistema).

4. **Base de dados:**
   Executar as migrações:
   ```bash
   php artisan migrate
   ```

5. **Iniciar a Aplicação:**
   Para desenvolvimento local num ambiente Windows (exemplo), pode recorrer ao script `start-system.ps1` que arranca:
   - Compilador de assets Vite (`npm run dev`)
   - Servidor local do Laravel (`php artisan serve`)
   - Filas do Laravel (Queue Workers e Scheduler)
   - API de MemPalace (Python FastAPI na porta 8001)
   - Mock da API do Acronis (caso exista configurado)

## Estrutura de Diretórios Importante

- `/app` - Toda a lógica da aplicação Laravel (Controllers, Models, Services, Jobs).
- `/rag` - Scripts Python responsáveis pela chunking, embedding e pesquisa no Pinecone.
- `/mempalace-api` - API FastAPI independente que serve o armazenamento histórico "MemPalace".
- `/resources/views` - Vistas Blade que contêm o interface frontend.
- `/routes` - Definição das APIs REST e Web (ex. `web.php` tem os agrupamentos da aplicação, middleware e controllers principais).

---
*Este documento foi gerado para refletir o estado atual da arquitetura e das capacidades do sistema.*
