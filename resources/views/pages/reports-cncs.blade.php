@extends('layouts.app')
@section('title', 'Relatório CNCS • Techbase GRC')


@section('content')

{{-- ═══════════════════════════════════════════
     BARRA DE ALTERNÂNCIA DE TIPO DE RELATÓRIO
════════════════════════════════════════════ --}}
<div class="report-toggle-bar">
    <button id="tabBtnAnnual" class="report-toggle-btn active-annual">
        <i data-lucide="file-bar-chart-2"></i>
        Relatório Anual CNCS
    </button>
    <button id="tabBtn24h" class="report-toggle-btn">
        <i data-lucide="alarm-clock"></i>
        Alerta de Incidente (Notificação 24h)
    </button>
</div>

<div class="cncs-root">

    {{-- ════════════════════════════════════════════
         FORMULÁRIO ANUAL (visível por defeito)
    ══════════════════════════════════════════════ --}}
    <div id="formAnnual" class="cncs-sidebar" style="display:flex;">

        {{-- Cabeçalho anual --}}
        <div class="cncs-header">
            <h2>Relatório Anual CNCS</h2>
            <p>Modelo RF20 — gerado automaticamente a partir dos dados do sistema. Revisa e exporta em PDF.</p>
        </div>

        {{-- Step 1: Parâmetros base --}}
        <div class="cncs-step open" id="step1">
            <div class="cncs-step-head" data-toggle="step1">
                <span class="cncs-step-num">1</span>
                <span class="cncs-step-title">Parâmetros do relatório</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-group">
                    <label>Entidade</label>
                    <input id="cncsEntity" value="Clínica Exemplo" placeholder="Nome da entidade" />
                </div>

                <div class="field-row">
                    <div class="field-group">
                        <label>Ano civil</label>
                        <select id="cncsYear">
                            <option value="2026">2026</option>
                            <option value="2025" selected>2025</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Período</label>
                        <input id="cncsPeriod" value="01/01 – 31/12" />
                    </div>
                </div>

                <div class="field-group">
                    <label>Escopo de incidentes</label>
                    <select id="cncsIncidentScope">
                        <option value="relevant">Apenas relevante / substancial</option>
                        <option value="all">Todos (inclui alertas convertidos)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Step 2: Secções editáveis --}}
        <div class="cncs-step open" id="step2">
            <div class="cncs-step-head" data-toggle="step2">
                <span class="cncs-step-num">2</span>
                <span class="cncs-step-title">Secções com edição manual</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <p class="field-hint">Auto-preenchidas com base nos dados do sistema. Edita antes de exportar.</p>

                <div class="field-group">
                    <label>3 — Atividades de segurança</label>
                    <textarea id="cncsManualActivities" rows="4" placeholder="Carregando dados..."></textarea>
                </div>

                <div class="field-group">
                    <label>6 — Recomendações de melhoria</label>
                    <textarea id="cncsManualRecs" rows="4" placeholder="Carregando dados..."></textarea>
                </div>

                <div class="field-group">
                    <label>8 — Outra informação relevante</label>
                    <textarea id="cncsExtra" rows="3" placeholder="Ex.: auditorias externas, mudanças de fornecedores…"></textarea>
                </div>
            </div>
        </div>

        {{-- Step 2B: Dados do incidente --}}
        <div class="cncs-step" id="step2b">
            <div class="cncs-step-head" data-toggle="step2b">
                <span class="cncs-step-num">2b</span>
                <span class="cncs-step-title">Dados do incidente</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
        
                {{-- Incidente urgente com tooltip --}}
                <div class="field-group">
                    <label style="display:flex;align-items:center;gap:6px">
                        Incidente grave (Art. 23.º NIS2)
                        <span id="urgentInfoIcon" style="cursor:help;color:var(--muted);display:inline-flex">
                            <i data-lucide="info" style="width:13px;height:13px"></i>
                        </span>
                    </label>
        
                    {{-- Tooltip --}}
                    <div id="urgentTooltip" style="
                        display:none;
                        background:var(--panel);
                        border:1px solid rgba(251,191,36,.3);
                        border-radius:10px;
                        padding:10px 13px;
                        font-size:12px;
                        line-height:1.5;
                        color:var(--warn);
                        margin-bottom:8px;
                    ">
                        <b>Incidente grave / urgente</b><br>
                        Incidente com impacto relevante ou substancial que deve ser notificado ao
                        CNCS nos prazos previstos (alerta inicial: 24h; notificação: 72h; relatório final: 1 mês).
                        Ver Art. 23.º da Diretiva NIS2 (2022/2555).
                    </div>
        
                    <div style="display:flex;align-items:center;gap:10px">
                        <label style="
                            display:flex;align-items:center;gap:8px;
                            cursor:pointer;font-size:13px;font-weight:500;
                            text-transform:none;letter-spacing:0;color:var(--text)
                        ">
                            <input type="checkbox" id="cncsIsUrgent" style="width:16px;height:16px;accent-color:var(--warn)">
                            Marcar como incidente grave
                        </label>
                    </div>
                    <div class="field-hint">Ao marcar, o relatório ficará assinalado com flag de urgência.</div>
                </div>
        
                {{-- Tipo de incidente --}}
                <div class="field-group">
                    <label>Tipo de incidente</label>
                    <select id="cncsIncidentType">
                        <option value="">— Selecionar —</option>
                        <option value="ransomware">Ransomware</option>
                        <option value="malware">Malware</option>
                        <option value="phishing">Phishing</option>
                        <option value="ddos">DDoS</option>
                        <option value="unauthorized_access">Acesso não autorizado</option>
                        <option value="data_breach">Fuga de dados</option>
                        <option value="service_disruption">Indisponibilidade de serviço</option>
                        <option value="backup_failure">Falha de backup</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
        
                {{-- Secção 5 — dados manuais (utilizadores + duração) --}}
                <div class="field-row">
                    <div class="field-group">
                        <label>Utilizadores afetados</label>
                        <input type="number" id="cncsUsersAffected" placeholder="Ex: 1500" min="0" />
                        <div class="field-hint">Soma dos incidentes relevantes.</div>
                    </div>
                    <div class="field-group">
                        <label>Duração total (horas)</label>
                        <input type="number" id="cncsDuration" placeholder="Ex: 14.5" min="0" step="0.5" />
                        <div class="field-hint">Soma das janelas de indisponibilidade.</div>
                    </div>
                </div>
        
            </div>
        </div>

        {{-- Step 3: Dados de fecho --}}
        <div class="cncs-step" id="step3">
            <div class="cncs-step-head" data-toggle="step3">
                <span class="cncs-step-num">3</span>
                <span class="cncs-step-title">Assinatura e fecho</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-group">
                    <label>Data do relatório</label>
                    <input type="date" id="cncsReportDate" />
                </div>
                <div class="field-group">
                    <label>Responsável de segurança</label>
                    <input id="cncsSecurityOfficer" placeholder="Nome completo" />
                </div>
                <div class="field-group">
                    <label>Cargo / Função</label>
                    <input id="cncsSignature" placeholder="CISO, DPO, Responsável de segurança…" />
                </div>
            </div>
        </div>

        {{-- Step 4: Exportar --}}
        <div class="cncs-step" id="step4">
            <div class="cncs-step-head" data-toggle="step4">
                <span class="cncs-step-num">4</span>
                <span class="cncs-step-title">Exportar</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-row">
                    <div class="field-group">
                        <label>Formato</label>
                        <select id="cncsFormat">
                            <option value="pdf">PDF</option>
                            <option value="odt">ODT (backend)</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Guardar no sistema</label>
                        <select id="cncsSaveAsDoc">
                            <option value="yes">Sim (RF2)</option>
                            <option value="no">Não</option>
                        </select>
                    </div>
                </div>

                <div class="cncs-actions">
                    <button id="btnPreviewCNCS" class="btn">
                        <i data-lucide="refresh-cw" style="width:14px;height:14px"></i>
                        Atualizar prévia
                    </button>
                    <button id="btnExportCNCS" class="btn primary">
                        <i data-lucide="download" style="width:14px;height:14px"></i>
                        Exportar
                    </button>
                </div>

                <p class="field-hint">Ao exportar: cria documento "Relatório CNCS" e regista entrada de auditoria (RNF5).</p>
            </div>
        </div>

    </div>{{-- /formAnnual --}}

    {{-- ════════════════════════════════════════════
         FORMULÁRIO 24H (oculto por defeito)
    ══════════════════════════════════════════════ --}}
    <div id="form24h" class="cncs-sidebar" style="display:none;">

        {{-- Cabeçalho 24h --}}
        <div class="cncs-header" style="border-color:rgba(239,68,68,.25);background:rgba(239,68,68,.04);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                <div>
                    <h2 style="display:flex;align-items:center;gap:8px;">
                        <i data-lucide="alarm-clock" style="width:16px;height:16px;color:#f87171;flex-shrink:0;"></i>
                        Notificação Inicial de Incidente
                    </h2>
                    <p>Alerta 24h conforme Art. 23.º da Diretiva NIS2 (2022/2555) e Decreto-Lei n.º 125/2025. Submeter ao CNCS dentro de 24 horas após deteção.</p>
                </div>
            </div>

            {{-- Contactos CNCS --}}
            <div style="margin-top:12px;padding:10px 12px;border-radius:8px;border:1px solid rgba(239,68,68,.2);background:rgba(239,68,68,.06);font-size:11.5px;color:var(--text);">
                <div style="font-weight:700;color:#f87171;margin-bottom:5px;font-size:11px;text-transform:uppercase;letter-spacing:.04em;">📞 Contactos CNCS</div>
                <div style="display:flex;flex-direction:column;gap:3px;">
                    <span>📧 <a href="mailto:incidentes@cncs.gov.pt" style="color:#f87171;">incidentes@cncs.gov.pt</a></span>
                    <span>📞 <span style="font-family:var(--font-mono);font-weight:700;">+351 210 012 000</span> <span style="color:var(--muted)">(24/7)</span></span>
                    <span>🌐 <a href="https://www.cncs.gov.pt" target="_blank" style="color:#f87171;">www.cncs.gov.pt</a></span>
                </div>
            </div>

            {{-- Progresso das secções --}}
            <div style="margin-top:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                    <span style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;">Progresso</span>
                    <span id="notifProgressLabel" style="font-size:11px;font-family:var(--font-mono);color:#f87171;font-weight:700;">0 / 7 secções</span>
                </div>
                <div class="notif-progress-bar" id="notifProgressBar">
                    <div class="notif-progress-segment" id="npSeg1"></div>
                    <div class="notif-progress-segment" id="npSeg2"></div>
                    <div class="notif-progress-segment" id="npSeg3"></div>
                    <div class="notif-progress-segment" id="npSeg4"></div>
                    <div class="notif-progress-segment" id="npSeg5"></div>
                    <div class="notif-progress-segment" id="npSeg6"></div>
                    <div class="notif-progress-segment" id="npSeg7"></div>
                </div>
            </div>
        </div>

        {{-- SECÇÃO 1 — Identificação da Entidade --}}
        <div class="cncs-step open" id="n24Step1">
            <div class="cncs-step-head" data-toggle="n24Step1">
                <span class="cncs-step-num red">1</span>
                <span class="cncs-step-title">Identificação da Entidade</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-group urgent-field">
                    <label>Nome da entidade</label>
                    <input id="n24Entity" placeholder="Ex.: Clínica Central, SA" />
                </div>
                <div class="field-group urgent-field">
                    <label>NIF / NIPC</label>
                    <input id="n24Nif" placeholder="Ex.: 500 000 000" />
                </div>
                <div class="field-row">
                    <div class="field-group urgent-field">
                        <label>Setor de atividade</label>
                        <select id="n24Sector">
                            <option value="">— Selecionar —</option>
                            <option value="saude">Saúde</option>
                            <option value="energia">Energia</option>
                            <option value="transportes">Transportes</option>
                            <option value="financas">Banca / Finanças</option>
                            <option value="digital">Infraestrutura digital</option>
                            <option value="agua">Água</option>
                            <option value="alimentacao">Alimentação</option>
                            <option value="administracao">Administração pública</option>
                            <option value="espacial">Espacial</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="field-group urgent-field">
                        <label>Tipo de entidade</label>
                        <select id="n24EntityType">
                            <option value="">— Selecionar —</option>
                            <option value="essencial">Entidade Essencial</option>
                            <option value="importante">Entidade Importante</option>
                        </select>
                    </div>
                </div>
                <div class="field-group urgent-field">
                    <label>Responsável de segurança / CISO</label>
                    <input id="n24SecurityOfficer" placeholder="Nome completo" />
                </div>
                <div class="field-group urgent-field">
                    <label>Email de contacto</label>
                    <input type="email" id="n24ContactEmail" placeholder="ciso@empresa.pt" />
                </div>
                <div class="field-group urgent-field">
                    <label>Telefone de contacto</label>
                    <input type="tel" id="n24ContactPhone" placeholder="+351 200 000 000" />
                </div>
            </div>
        </div>

        {{-- SECÇÃO 2 — Deteção do Incidente --}}
        <div class="cncs-step" id="n24Step2">
            <div class="cncs-step-head" data-toggle="n24Step2">
                <span class="cncs-step-num red">2</span>
                <span class="cncs-step-title">Deteção do Incidente</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-row">
                    <div class="field-group urgent-field">
                        <label>Data e hora de deteção</label>
                        <input type="datetime-local" id="n24DetectedAt" />
                        <div class="field-hint">Quando o incidente foi identificado.</div>
                    </div>
                    <div class="field-group urgent-field">
                        <label>Data e hora de início (estimada)</label>
                        <input type="datetime-local" id="n24StartedAt" />
                        <div class="field-hint">Início estimado do incidente.</div>
                    </div>
                </div>
                <div class="field-group urgent-field">
                    <label>Quem detetou o incidente</label>
                    <select id="n24DetectedBy">
                        <option value="">— Selecionar —</option>
                        <option value="monitoring">Sistema de monitorização automático</option>
                        <option value="employee">Colaborador interno</option>
                        <option value="external">Entidade externa / terceiro</option>
                        <option value="csirt">CSIRT / CERT</option>
                        <option value="law">Autoridade policial / judicial</option>
                        <option value="user">Utilizador final</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
                <div class="field-group urgent-field">
                    <label>Como foi detetado</label>
                    <textarea id="n24DetectionMethod" rows="3" placeholder="Descreve o método ou ferramenta que levou à deteção (ex.: alerta SIEM, chamada de utilizador, verificação manual, etc.)"></textarea>
                </div>
            </div>
        </div>

        {{-- SECÇÃO 3 — Natureza do Incidente --}}
        <div class="cncs-step" id="n24Step3">
            <div class="cncs-step-head" data-toggle="n24Step3">
                <span class="cncs-step-num red">3</span>
                <span class="cncs-step-title">Natureza do Incidente</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-group urgent-field">
                    <label>Tipo / categoria do incidente</label>
                    <select id="n24IncidentType">
                        <option value="">— Selecionar —</option>
                        <optgroup label="Malicious Code">
                            <option value="ransomware">Ransomware</option>
                            <option value="malware">Malware / Vírus</option>
                            <option value="spyware">Spyware / Stalkerware</option>
                        </optgroup>
                        <optgroup label="Intrusão">
                            <option value="unauthorized_access">Acesso não autorizado</option>
                            <option value="account_compromise">Comprometimento de conta</option>
                            <option value="supply_chain">Ataque à cadeia de fornecimento</option>
                        </optgroup>
                        <optgroup label="Disponibilidade">
                            <option value="ddos">DDoS</option>
                            <option value="service_disruption">Indisponibilidade de serviço</option>
                            <option value="power_failure">Falha de energia / infraestrutura</option>
                        </optgroup>
                        <optgroup label="Dados">
                            <option value="data_breach">Violação / fuga de dados</option>
                            <option value="data_manipulation">Manipulação de dados</option>
                        </optgroup>
                        <optgroup label="Outros">
                            <option value="phishing">Phishing / Engenharia social</option>
                            <option value="insider">Ameaça interna</option>
                            <option value="vulnerability">Exploração de vulnerabilidade</option>
                            <option value="other">Outro</option>
                        </optgroup>
                    </select>
                </div>
                <div class="field-group urgent-field">
                    <label>Descrição inicial do incidente</label>
                    <textarea id="n24Description" rows="4" placeholder="Descreve sucintamente o que aconteceu, a natureza do ataque ou evento, e o contexto identificado até ao momento."></textarea>
                </div>
                <div class="field-group urgent-field">
                    <label>Estado atual do incidente</label>
                    <select id="n24Status">
                        <option value="ongoing">Em curso (ativo)</option>
                        <option value="contained">Contido (a investigar)</option>
                        <option value="resolved">Resolvido</option>
                        <option value="unknown">Desconhecido</option>
                    </select>
                </div>
                <div class="field-row">
                    <div class="field-group urgent-field">
                        <label>Vetores de ataque suspeitos</label>
                        <select id="n24AttackVector">
                            <option value="">— Selecionar —</option>
                            <option value="email">Email malicioso</option>
                            <option value="web">Aplicação web</option>
                            <option value="rdp">RDP / Acesso remoto</option>
                            <option value="usb">Dispositivo USB / físico</option>
                            <option value="supply">Cadeia de fornecimento</option>
                            <option value="insider">Acesso interno</option>
                            <option value="unknown">Desconhecido</option>
                        </select>
                    </div>
                    <div class="field-group urgent-field">
                        <label>Dados pessoais envolvidos</label>
                        <select id="n24PersonalData">
                            <option value="no">Não</option>
                            <option value="yes">Sim</option>
                            <option value="unknown">A avaliar</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECÇÃO 4 — Sistemas Afetados --}}
        <div class="cncs-step" id="n24Step4">
            <div class="cncs-step-head" data-toggle="n24Step4">
                <span class="cncs-step-num red">4</span>
                <span class="cncs-step-title">Sistemas e Serviços Afetados</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-group urgent-field">
                    <label>Sistemas / serviços afetados</label>
                    <textarea id="n24AffectedSystems" rows="3" placeholder="Lista os sistemas, aplicações ou infraestrutura impactada (ex.: ERP, sistemas clínicos, redes internas, servidores de ficheiros, etc.)"></textarea>
                </div>
                <div class="field-row">
                    <div class="field-group urgent-field">
                        <label>N.º de utilizadores afetados</label>
                        <input type="number" id="n24AffectedUsers" placeholder="Ex.: 500" min="0" />
                    </div>
                    <div class="field-group urgent-field">
                        <label>N.º de sistemas comprometidos</label>
                        <input type="number" id="n24AffectedSystems2" placeholder="Ex.: 12" min="0" />
                    </div>
                </div>
                <div class="field-group urgent-field">
                    <label>Serviços críticos interrompidos</label>
                    <textarea id="n24CriticalServices" rows="2" placeholder="Indica quais os serviços essenciais que ficaram indisponíveis ou degradados."></textarea>
                </div>
                <div class="field-group urgent-field">
                    <label>Impacto transfronteiriço</label>
                    <select id="n24CrossBorder">
                        <option value="no">Não identificado</option>
                        <option value="yes">Sim — afeta outros Estados-Membros da UE</option>
                        <option value="unknown">A avaliar</option>
                    </select>
                    <div class="field-hint">Se sim, indicar quais os países afetados nas notas adicionais.</div>
                </div>
                <div class="field-group urgent-field" id="n24CrossBorderCountriesGrp" style="display:none;">
                    <label>Países afetados (se transfronteiriço)</label>
                    <input id="n24CrossBorderCountries" placeholder="Ex.: Espanha, França" />
                </div>
            </div>
        </div>

        {{-- SECÇÃO 5 — Avaliação de Impacto --}}
        <div class="cncs-step" id="n24Step5">
            <div class="cncs-step-head" data-toggle="n24Step5">
                <span class="cncs-step-num red">5</span>
                <span class="cncs-step-title">Avaliação de Impacto</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-row">
                    <div class="field-group urgent-field">
                        <label>Nível de severidade</label>
                        <select id="n24Severity">
                            <option value="">— Selecionar —</option>
                            <option value="critical">Crítico</option>
                            <option value="high">Elevado</option>
                            <option value="medium">Médio</option>
                            <option value="low">Baixo</option>
                        </select>
                    </div>
                    <div class="field-group urgent-field">
                        <label>Critério (Art. 23.º NIS2)</label>
                        <select id="n24Criterion">
                            <option value="">— Selecionar —</option>
                            <option value="users">N.º elevado de utilizadores afetados</option>
                            <option value="duration">Longa duração da interrupção</option>
                            <option value="geographic">Extensão geográfica significativa</option>
                            <option value="data">Perda / comprometimento de dados</option>
                            <option value="financial">Impacto financeiro substancial</option>
                            <option value="reputational">Impacto reputacional</option>
                        </select>
                    </div>
                </div>
                <div class="field-group urgent-field">
                    <label>Impacto operacional</label>
                    <textarea id="n24OperationalImpact" rows="3" placeholder="Descreve o impacto nas operações da organização: interrupção de serviços, processos afetados, perdas estimadas, etc."></textarea>
                </div>
                <div class="field-group urgent-field">
                    <label>Impacto financeiro estimado (€)</label>
                    <input type="number" id="n24FinancialImpact" placeholder="Ex.: 50000" min="0" />
                    <div class="field-hint">Estimativa preliminar (a confirmar no relatório final).</div>
                </div>
                <div class="field-group urgent-field">
                    <label>Avaliação inicial de risco para terceiros</label>
                    <textarea id="n24ThirdPartyRisk" rows="2" placeholder="Indica se há risco para clientes, parceiros, fornecedores ou cidadãos."></textarea>
                </div>
            </div>
        </div>

        {{-- SECÇÃO 6 — Medidas Imediatas --}}
        <div class="cncs-step" id="n24Step6">
            <div class="cncs-step-head" data-toggle="n24Step6">
                <span class="cncs-step-num red">6</span>
                <span class="cncs-step-title">Medidas Imediatas Tomadas</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="field-group urgent-field">
                    <label>Medidas de contenção já implementadas</label>
                    <textarea id="n24Containment" rows="4" placeholder="Lista as ações já tomadas para conter o incidente (ex.: isolamento de sistemas, bloqueio de contas comprometidas, patches aplicados, etc.)"></textarea>
                </div>
                <div class="field-group urgent-field">
                    <label>Medidas de recuperação previstas</label>
                    <textarea id="n24Recovery" rows="3" placeholder="Plano de recuperação a curto prazo: restauro de backups, reinstalação de sistemas, etc."></textarea>
                </div>
                <div class="field-row">
                    <div class="field-group urgent-field">
                        <label>CSIRT / suporte externo ativado</label>
                        <select id="n24ExternalSupport">
                            <option value="no">Não</option>
                            <option value="yes">Sim</option>
                            <option value="planned">A contactar</option>
                        </select>
                    </div>
                    <div class="field-group urgent-field">
                        <label>Backups disponíveis</label>
                        <select id="n24BackupAvailable">
                            <option value="yes">Sim — atualizados</option>
                            <option value="partial">Parcialmente</option>
                            <option value="no">Não</option>
                            <option value="unknown">Desconhecido</option>
                        </select>
                    </div>
                </div>
                <div class="field-group urgent-field">
                    <label>Notificação a outras autoridades</label>
                    <textarea id="n24OtherAuthorities" rows="2" placeholder="Indica se foi notificada a CNPD (se dados pessoais envolvidos), Ministério Público, ou outras entidades."></textarea>
                </div>
            </div>
        </div>

        {{-- SECÇÃO 7 — Declaração e Assinatura --}}
        <div class="cncs-step" id="n24Step7">
            <div class="cncs-step-head" data-toggle="n24Step7">
                <span class="cncs-step-num red">7</span>
                <span class="cncs-step-title">Declaração e Assinatura</span>
                <span class="cncs-step-caret"><i data-lucide="chevron-down" style="width:15px;height:15px"></i></span>
            </div>
            <div class="cncs-step-body">
                <div class="urgency-banner">
                    <div class="ub-icon">
                        <i data-lucide="shield-alert" style="width:15px;height:15px;color:#f87171;"></i>
                    </div>
                    <div>
                        <b>Declaração de veracidade</b><br>
                        O declarante confirma que as informações prestadas são verdadeiras e completas ao melhor do seu conhecimento, e que a notificação é submetida dentro do prazo de 24h conforme exigido pelo Art. 23.º da Diretiva NIS2.
                    </div>
                </div>
                <div class="field-group urgent-field">
                    <label>Nome do declarante</label>
                    <input id="n24SignerName" placeholder="Nome completo" />
                </div>
                <div class="field-group urgent-field">
                    <label>Cargo / Função</label>
                    <input id="n24SignerRole" placeholder="Ex.: CISO, Diretor de TI, DPO" />
                </div>
                <div class="field-row">
                    <div class="field-group urgent-field">
                        <label>Data de submissão</label>
                        <input type="date" id="n24SubmitDate" />
                    </div>
                    <div class="field-group urgent-field">
                        <label>Hora de submissão</label>
                        <input type="time" id="n24SubmitTime" />
                    </div>
                </div>
                <div class="field-group urgent-field">
                    <label>Notas adicionais</label>
                    <textarea id="n24Notes" rows="3" placeholder="Qualquer informação adicional relevante para o CNCS."></textarea>
                </div>

                <div class="cncs-actions">
                    <button id="btnPreview24h" class="btn">
                        <i data-lucide="refresh-cw" style="width:14px;height:14px"></i>
                        Atualizar prévia
                    </button>
                    <button id="btnExport24h" class="btn" style="background:rgba(239,68,68,.15);color:#f87171;border-color:rgba(239,68,68,.3);">
                        <i data-lucide="send" style="width:14px;height:14px"></i>
                        Exportar PDF 24h
                    </button>
                </div>
                <p class="field-hint">Exporta a notificação em PDF para envio ao CNCS via <a href="mailto:incidentes@cncs.gov.pt" style="color:#f87171;">incidentes@cncs.gov.pt</a></p>
            </div>
        </div>

    </div>{{-- /form24h --}}

    {{-- ════════════════════════════════════════════
         COLUNA DIREITA — Pré-visualização (ANUAL)
    ══════════════════════════════════════════════ --}}
    <div id="previewAnnual" class="cncs-preview" style="display:block;">

        <div class="cncs-preview-topbar">
            <div class="cncs-preview-title">
                <h2>Pré-visualização</h2>
                <div class="cncs-preview-subtitle" id="pvSubtitle">Seleciona os parâmetros e clica em Atualizar prévia</div>
            </div>
            <div class="kpi-row">
                <span class="kpi-chip">
                    <i data-lucide="alert-triangle" style="width:13px;height:13px;color:var(--muted)"></i>
                    Incidentes: <b id="pvIncTotal">—</b>
                </span>
                <span class="kpi-chip warn">
                    <i data-lucide="shield-alert" style="width:13px;height:13px"></i>
                    Relev./Subst.: <b id="pvIncRelevant">—</b>
                </span>
                <span class="kpi-chip bad">
                    <i data-lucide="trending-up" style="width:13px;height:13px"></i>
                    Riscos altos: <b id="pvHighRisks">—</b>
                </span>
            </div>
        </div>

        <div class="cncs-preview-body" id="pvBody">

            {{-- Secção 1 + 2 --}}
            <div class="pv-section">
                <div class="pv-section-label">1 + 2 — Identificação</div>
                <div class="pv-two">
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="building-2" style="width:11px;height:11px"></i> Entidade</div>
                        <div class="tb-content" id="pvEntity">—</div>
                    </div>
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="calendar" style="width:11px;height:11px"></i> Período</div>
                        <div class="tb-content" id="pvPeriod">—</div>
                    </div>
                </div>
            </div>

            {{-- Secção 3 —  Atividades --}}
            <div class="pv-section">
                <div class="pv-section-label">3 — Atividades de segurança</div>
                <div class="pv-text-block">
                    <div class="tb-label"><i data-lucide="file-text" style="width:11px;height:11px"></i> Texto final</div>
                    <div class="tb-content" id="pvActivitiesText">—</div>
                </div>
            </div>

            {{-- Secção 4 — Estatística trimestral --}}
            <div class="pv-section">
                <div class="pv-section-label">4 — Estatística trimestral</div>
                <table class="pv-table">
                    <thead>
                        <tr>
                            <th>Trim.</th>
                            <th>Total</th>
                            <th>Tipos</th>
                        </tr>
                    </thead>
                    <tbody id="pvQuarterBody">
                        <tr>
                            <td colspan="3" class="muted" style="font-size:12px; padding: 10px 0">Nenhum dado. Clique em "Atualizar prévia".</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Secção 5 — Análise agregada --}}
            <div class="pv-section">
                <div class="pv-section-label">5 — Análise agregada (relevante / substancial)</div>

                <div class="pv-stats-grid" style="margin-bottom:10px">
                    <div class="pv-stat-box">
                        <div class="stat-label">Utilizadores afetados</div>
                        <div class="stat-value" id="pvUsersAffected">—</div>
                        <div class="stat-hint" id="pvUsersAffectedHint">—</div>
                    </div>
                    <div class="pv-stat-box">
                        <div class="stat-label">Duração agregada</div>
                        <div class="stat-value" id="pvDuration">—</div>
                        <div class="stat-hint" id="pvDurationHint">—</div>
                    </div>
                </div>

                <div class="pv-two">
                    <div>
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); margin-bottom:7px">Distribuição geográfica</div>
                        <div id="pvGeo" class="pv-geo-list"><div class="pv-geo-item"><span class="muted">—</span></div></div>
                    </div>
                    <div>
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); margin-bottom:7px">Impacto transfronteiriço</div>
                        <div id="pvCrossBorder">—</div>
                    </div>
                </div>
            </div>

            {{-- Secção 6 — Recomendações --}}
            <div class="pv-section">
                <div class="pv-section-label">6 — Recomendações de melhoria</div>
                <div class="pv-text-block">
                    <div class="tb-label"><i data-lucide="lightbulb" style="width:11px;height:11px"></i> Texto final</div>
                    <div class="tb-content" id="pvRecsText">—</div>
                </div>
            </div>

            {{-- Secção 7 — Medidas implementadas --}}
            <div class="pv-section">
                <div class="pv-section-label">7 — Problemas identificados e medidas implementadas</div>
                <div class="pv-measures" id="pvMeasures">
                    <div class="muted" style="font-size:12px">—</div>
                </div>
            </div>


            {{-- Secção: Conformidade NIS2 / QNRCS --}}
        <div class="pv-section">
            <div class="pv-section-label">
                <i data-lucide="shield-check" style="width:12px;height:12px"></i>
                Conformidade — NIS2 &amp; QNRCS
            </div>
        
            {{-- Filtros --}}
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px">
                <div class="field-group" style="min-width:160px">
                    <label>Framework</label>
                    <select id="complianceFrameworkFilter">
                        <option value="all">Todos</option>
                        <option value="NIS2">NIS2</option>
                        <option value="QNRCS">QNRCS</option>
                    </select>
                </div>
                <div class="field-group" style="min-width:200px">
                    <label>Estado</label>
                    <select id="complianceStatusFilter">
                        <option value="compliant,partial">Conformes e parciais</option>
                        <option value="compliant">Apenas conformes</option>
                        <option value="partial">Apenas parciais</option>
                        <option value="non_compliant">Não conformes</option>
                        <option value="all">Todos os estados</option>
                    </select>
                </div>
            </div>
        
            {{-- Loading spinner --}}
            <div id="complianceLoading" style="display:none;align-items:center;gap:10px;padding:20px 0;color:var(--muted);font-size:13px">
                <div class="pv-spinner"></div> A carregar controlos...
            </div>
        
            {{-- Tabela --}}
            <div style="overflow-x:auto">
                <table class="pv-table" style="min-width:700px">
                    <thead>
                        <tr>
                            <th style="white-space:nowrap">Controlo</th>
                            <th>Grupo</th>
                            <th>Descrição</th>
                            <th>Estado</th>
                            <th>Notas</th>
                            <th style="white-space:nowrap">Avaliado por</th>
                        </tr>
                    </thead>
                    <tbody id="complianceTbody">
                        <tr>
                            <td colspan="6" class="muted" style="text-align:center;padding:24px;font-size:12px">
                                A carregar...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        
            {{-- Paginador --}}
            <div id="compliancePager" style="
                display:flex;align-items:center;justify-content:space-between;
                padding:12px 0 0;gap:12px;flex-wrap:wrap
            "></div>
        
            <p class="field-hint" style="margin-top:8px">
                Apenas controlos avaliados são listados. Para avaliar controlos, acede ao módulo
                <a href="{{ route('compliance') }}" style="color:var(--info)">Compliance</a>.
            </p>
        </div>

            {{-- Secção 8 — Outra informação --}}
            <div class="pv-section">
                <div class="pv-section-label">8 — Outra informação relevante</div>
                <div class="pv-text-block">
                    <div class="tb-label"><i data-lucide="info" style="width:11px;height:11px"></i> Informação adicional</div>
                    <div class="tb-content" id="pvExtraText">—</div>
                </div>
            </div>

            {{-- Fecho / Assinatura --}}
            <div class="pv-section">
                <div class="pv-section-label">Fecho e assinatura</div>
                <div class="pv-sign-grid">
                    <div class="pv-sign-box">
                        <div class="sb-label">Data</div>
                        <div class="sb-value" id="pvSignDate">—</div>
                    </div>
                    <div class="pv-sign-box">
                        <div class="sb-label">Responsável</div>
                        <div class="sb-value" id="pvSignOfficer">—</div>
                    </div>
                    <div class="pv-sign-box">
                        <div class="sb-label">Cargo / Assinatura</div>
                        <div class="sb-value" id="pvSignRole">—</div>
                    </div>
                </div>
            </div>

            <p class="field-hint" style="text-align:center">
                Dados de demonstração (mock). Ao integrar com o backend, os valores serão preenchidos automaticamente.
            </p>

        </div>{{-- /cncs-preview-body --}}
    </div>{{-- /previewAnnual --}}

    {{-- ════════════════════════════════════════════
         COLUNA DIREITA — Pré-visualização 24H
    ══════════════════════════════════════════════ --}}
    <div id="preview24h" class="cncs-preview" style="display:none;">

        <div class="cncs-preview-topbar topbar-24h">
            <div class="cncs-preview-title">
                <h2 style="display:flex;align-items:center;gap:8px;">
                    <i data-lucide="alarm-clock" style="width:15px;height:15px;color:#f87171;"></i>
                    Notificação Inicial — 24 Horas
                    <span class="badge-24h">URGENTE</span>
                </h2>
                <div class="cncs-preview-subtitle" id="pv24Subtitle">Preenche os dados e clica em Atualizar prévia</div>
            </div>
            <div class="kpi-row">
                <span class="kpi-chip urgent">
                    <i data-lucide="shield-alert" style="width:13px;height:13px"></i>
                    Art. 23.º NIS2
                </span>
                <span class="kpi-chip urgent">
                    <i data-lucide="clock" style="width:13px;height:13px"></i>
                    Prazo: <b>24h</b>
                </span>
            </div>
        </div>

        <div class="cncs-preview-body">

            {{-- Cabeçalho formal do relatório --}}
            <div style="text-align:center;padding:10px 0 4px;">
                <div style="font-size:18px;font-weight:800;letter-spacing:-.01em;margin-bottom:4px;">NOTIFICAÇÃO INICIAL DE INCIDENTE</div>
                <div style="font-size:12px;color:var(--muted);">Alerta 24 Horas · CNCS — Centro Nacional de Cibersegurança</div>
                <div style="font-size:11px;color:var(--muted);margin-top:2px;">Artigo 23.º, Diretiva (UE) 2022/2555 · D.L. n.º 125/2025</div>
                <div style="display:inline-block;margin-top:8px;padding:4px 12px;border-radius:6px;border:1px solid rgba(239,68,68,.3);background:rgba(239,68,68,.08);font-size:11px;color:#f87171;font-family:var(--font-mono);font-weight:700;">
                    Confidencial — Uso exclusivo do CNCS
                </div>
            </div>

            {{-- 1. Identificação --}}
            <div class="pv-section">
                <div class="pv-section-label red-line">1 — Identificação da Entidade</div>
                <div class="pv-two" style="gap:10px;">
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="building-2" style="width:11px;height:11px"></i> Entidade</div>
                        <div class="tb-content" id="pv24Entity">—</div>
                    </div>
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="hash" style="width:11px;height:11px"></i> NIF / NIPC</div>
                        <div class="tb-content" id="pv24Nif">—</div>
                    </div>
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="layers" style="width:11px;height:11px"></i> Setor</div>
                        <div class="tb-content" id="pv24Sector">—</div>
                    </div>
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="shield" style="width:11px;height:11px"></i> Tipo</div>
                        <div class="tb-content" id="pv24EntityType">—</div>
                    </div>
                </div>
                <div class="pv-text-block" style="margin-top:8px;">
                    <div class="tb-label"><i data-lucide="user" style="width:11px;height:11px"></i> Responsável / Contacto</div>
                    <div class="tb-content" id="pv24Contact">—</div>
                </div>
            </div>

            {{-- 2. Deteção --}}
            <div class="pv-section">
                <div class="pv-section-label red-line">2 — Deteção do Incidente</div>
                <div class="pv-stats-grid">
                    <div class="pv-stat-box urgent-box">
                        <div class="stat-label">Detetado em</div>
                        <div class="stat-value" style="font-size:13px;font-weight:700;" id="pv24DetectedAt">—</div>
                    </div>
                    <div class="pv-stat-box urgent-box">
                        <div class="stat-label">Início estimado</div>
                        <div class="stat-value" style="font-size:13px;font-weight:700;" id="pv24StartedAt">—</div>
                    </div>
                </div>
                <div class="pv-text-block" style="margin-top:8px;">
                    <div class="tb-label"><i data-lucide="search" style="width:11px;height:11px"></i> Método de deteção</div>
                    <div class="tb-content" id="pv24DetectionMethod">—</div>
                </div>
            </div>

            {{-- 3. Natureza --}}
            <div class="pv-section">
                <div class="pv-section-label red-line">3 — Natureza do Incidente</div>
                <div class="pv-two">
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="zap" style="width:11px;height:11px"></i> Tipo</div>
                        <div class="tb-content" id="pv24IncidentType">—</div>
                    </div>
                    <div class="pv-text-block">
                        <div class="tb-label"><i data-lucide="activity" style="width:11px;height:11px"></i> Estado</div>
                        <div class="tb-content" id="pv24Status">—</div>
                    </div>
                </div>
                <div class="pv-text-block" style="margin-top:8px;">
                    <div class="tb-label"><i data-lucide="file-text" style="width:11px;height:11px"></i> Descrição</div>
                    <div class="tb-content" id="pv24Description">—</div>
                </div>
                <div class="pv-two" style="margin-top:8px;">
                    <div class="pv-text-block">
                        <div class="tb-label">Vetor suspeito</div>
                        <div class="tb-content" id="pv24AttackVector">—</div>
                    </div>
                    <div class="pv-text-block">
                        <div class="tb-label">Dados pessoais</div>
                        <div class="tb-content" id="pv24PersonalData">—</div>
                    </div>
                </div>
            </div>

            {{-- 4. Sistemas afetados --}}
            <div class="pv-section">
                <div class="pv-section-label red-line">4 — Sistemas e Serviços Afetados</div>
                <div class="pv-stats-grid" style="margin-bottom:8px;">
                    <div class="pv-stat-box urgent-box">
                        <div class="stat-label">Utilizadores</div>
                        <div class="stat-value" id="pv24AffectedUsers">—</div>
                    </div>
                    <div class="pv-stat-box urgent-box">
                        <div class="stat-label">Sistemas</div>
                        <div class="stat-value" id="pv24AffectedSystems">—</div>
                    </div>
                </div>
                <div class="pv-text-block">
                    <div class="tb-label"><i data-lucide="server" style="width:11px;height:11px"></i> Sistemas / Serviços</div>
                    <div class="tb-content" id="pv24Systems">—</div>
                </div>
                <div class="pv-text-block" style="margin-top:8px;">
                    <div class="tb-label"><i data-lucide="globe" style="width:11px;height:11px"></i> Impacto transfronteiriço</div>
                    <div class="tb-content" id="pv24CrossBorder">—</div>
                </div>
            </div>

            {{-- 5. Impacto --}}
            <div class="pv-section">
                <div class="pv-section-label red-line">5 — Avaliação de Impacto</div>
                <div class="pv-two">
                    <div class="pv-text-block">
                        <div class="tb-label">Severidade</div>
                        <div class="tb-content" id="pv24Severity">—</div>
                    </div>
                    <div class="pv-text-block">
                        <div class="tb-label">Impacto financeiro</div>
                        <div class="tb-content" id="pv24Financial">—</div>
                    </div>
                </div>
                <div class="pv-text-block" style="margin-top:8px;">
                    <div class="tb-label"><i data-lucide="alert-octagon" style="width:11px;height:11px"></i> Impacto operacional</div>
                    <div class="tb-content" id="pv24OperationalImpact">—</div>
                </div>
            </div>

            {{-- 6. Medidas --}}
            <div class="pv-section">
                <div class="pv-section-label red-line">6 — Medidas Imediatas</div>
                <div class="pv-text-block">
                    <div class="tb-label"><i data-lucide="shield-check" style="width:11px;height:11px"></i> Contenção</div>
                    <div class="tb-content" id="pv24Containment">—</div>
                </div>
                <div class="pv-text-block" style="margin-top:8px;">
                    <div class="tb-label"><i data-lucide="refresh-cw" style="width:11px;height:11px"></i> Recuperação planeada</div>
                    <div class="tb-content" id="pv24Recovery">—</div>
                </div>
            </div>

            {{-- 7. Declaração --}}
            <div class="pv-section">
                <div class="pv-section-label red-line">7 — Declaração e Assinatura</div>
                <div class="pv-sign-grid">
                    <div class="pv-sign-box" style="border-color:rgba(239,68,68,.2);">
                        <div class="sb-label">Declarante</div>
                        <div class="sb-value" id="pv24SignerName">—</div>
                    </div>
                    <div class="pv-sign-box" style="border-color:rgba(239,68,68,.2);">
                        <div class="sb-label">Cargo</div>
                        <div class="sb-value" id="pv24SignerRole">—</div>
                    </div>
                    <div class="pv-sign-box" style="border-color:rgba(239,68,68,.2);">
                        <div class="sb-label">Data / Hora submissão</div>
                        <div class="sb-value" id="pv24SubmitDateTime">—</div>
                    </div>
                </div>
                <div class="pv-text-block" style="margin-top:8px;">
                    <div class="tb-label"><i data-lucide="message-square" style="width:11px;height:11px"></i> Notas adicionais</div>
                    <div class="tb-content" id="pv24Notes">—</div>
                </div>
            </div>

            <p class="field-hint" style="text-align:center">
                Formulário de notificação obrigatória conforme Art. 23.º da Diretiva NIS2 e D.L. n.º 125/2025.
                Submeter ao CNCS via <a href="mailto:incidentes@cncs.gov.pt" style="color:#f87171;">incidentes@cncs.gov.pt</a>.
            </p>

        </div>
    </div>{{-- /preview24h --}}

</div>{{-- /cncs-root --}}

@push('styles')
    @vite(['resources/css/pages/reports-cncs.css'])
@endpush

{{-- pdfmake --}}
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/pdfmake.min.js"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/vfs_fonts.min.js"></script>

@vite(['resources/js/pages/reports-cncs.js'])
@endsection
