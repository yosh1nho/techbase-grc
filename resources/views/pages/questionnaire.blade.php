@extends('layouts.app')
@section('title', 'Cyberplanner • Techbase GRC')

@section('content')

<style>

/* ---------- TABS ÁREAS ---------- */

#areaTabs{
display:flex;
flex-wrap:wrap;
gap:8px;
margin-bottom:18px;
}

.area-tab{
display:flex;
align-items:center;
gap:8px;
padding:8px 14px;
border-radius:10px;
border:1px solid var(--line);
background:var(--chip);
cursor:pointer;
font-size:13px;
font-weight:600;
transition:.15s;
color:var(--text);
}

.area-tab:hover{
background:rgba(96,165,250,.12);
border-color:rgba(96,165,250,.35);
}

.area-tab.active{
background:rgba(28, 116, 223, 0.35);
border-color:#3b82f6;
color:white;
}

.area-tab-icon svg{
width:16px;
height:16px;
stroke-width:2;
}

.area-tab-badge{
margin-left:6px;
font-size:11px;
opacity:.8;
}

.area-tabs-wrap{
display:flex;
flex-wrap:wrap;
gap:8px;
margin-bottom:20px;
}

html[data-theme="dark"] .area-tab.active{
background:rgba(96,165,250,.22);
border-color:rgba(96,165,250,.45);
color:#cfe3ff;
}
/* ---------- ÁREA HEADER ---------- */

.area-header{
display:flex;
align-items:center;
gap:12px;
margin-bottom:14px;
}

.area-header-icon{
width:32px;
height:32px;
display:flex;
align-items:center;
justify-content:center;
border-radius:10px;
background:var(--chip);
border:1px solid var(--line);
}

.area-header-icon svg{
width:18px;
height:18px;
}

.area-header-title{
font-weight:700;
font-size:15px;
}

.area-header-desc{
font-size:12px;
opacity:.7;
}


/* ---------- PERGUNTAS ---------- */

.cp-row{
border:1px solid var(--line);
border-radius:12px;
padding:16px;
margin-bottom:14px;
background:rgba(255,255,255,.02);
transition:.15s;
}

.cp-row:hover{
border-color:rgba(96,165,250,.3);
box-shadow:0 4px 12px rgba(0,0,0,.25);
}

.cp-row-top{
display:flex;
justify-content:space-between;
align-items:flex-start;
margin-bottom:10px;
gap:12px;
}

.cp-row-question{
font-weight:600;
font-size:14px;
max-width:70%;
}


/* ---------- RISCO ---------- */

.risk-badge{
font-size:11px;
font-weight:700;
padding:4px 8px;
border-radius:8px;
background:var(--chip);
border:1px solid var(--line);
}


/* ---------- RESPOSTAS ---------- */

.cp-row-answers{
display:inline-flex;
background:var(--chip);
border:1px solid var(--line);
border-radius:10px;
padding:3px;
gap:3px;
}

.ans-opt{
display:flex;
align-items:center;
gap:6px;
padding:6px 14px;
border-radius:8px;
border:1px solid transparent;
background:transparent;
font-size:13px;
font-weight:600;
cursor:pointer;
transition:all .15s;
color:var(--text);
}

.ans-opt:hover{
background:rgba(96,165,250,.12);
}

.ans-opt.selected{
background:rgba(96,165,250,.20);
border-color:rgba(96,165,250,.45);
}


.ans-YES.selected{
background:rgba(34,197,94,.20);
border-color:rgba(34,197,94,.40);
color:#4ade80;
}

.ans-PARTIAL.selected{
background:rgba(234,179,8,.20);
border-color:rgba(234,179,8,.40);
color:#fde047;
}

.ans-NO.selected{
background:rgba(239,68,68,.20);
border-color:rgba(239,68,68,.40);
color:#f87171;
}

.ans-NA.selected{
background:rgba(148,163,184,.18);
border-color:rgba(148,163,184,.35);
color:#cbd5f5;
}

/* ícones */

.ans-icon svg{
width:14px;
height:14px;
stroke-width:2;
}


/* ---------- NOTAS ---------- */

.cp-row-notes textarea{
width:100%;
margin-top:8px;
border-radius:10px;
border:1px solid var(--border);
padding:8px;
font-size:12px;
background:rgba(0,0,0,.15);
}


/* ---------- META ---------- */

.cp-row-meta{
display:flex;
gap:14px;
margin-top:8px;
font-size:11px;
opacity:.7;
flex-wrap:wrap;
}

.meta-id{
font-family:monospace;
}


/* ---------- NAV ---------- */

.tab-nav{
display:flex;
justify-content:space-between;
align-items:center;
margin-top:20px;
}

.tab-nav-info{
font-size:12px;
opacity:.6;
}

/* elementos editáveis no plano */

.editable{
border-bottom:1px dashed rgba(96,165,250,.6);
padding:1px 3px;
cursor:text;
transition:.15s;
}

.editable:hover{
background:rgba(96,165,250,.08);
}

.editable:focus{
outline:none;
background:rgba(96,165,250,.15);
border-bottom:1px solid #60a5fa;
}

.report-item{
padding:14px;
border-radius:10px;
background:rgba(255,255,255,.02);
margin-bottom:12px;
border:1px solid var(--line);
}

.report-item-top{
display:flex;
justify-content:space-between;
margin-bottom:6px;
}

.report-item-q{
font-weight:600;
}

.report-item-action{
margin-top:6px;
font-size:13px;
}

.report-item-notes{
margin-top:6px;
font-size:12px;
opacity:.8;
}

.report-kpis{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(120px,1fr));
gap:12px;
margin-bottom:18px;
}

.report-kpi{
background:var(--chip);
border:1px solid var(--line);
border-radius:12px;
padding:12px;
text-align:center;
position:relative;
}

.report-kpi::top{
content:"";
position:absolute;
top:0;
left:0;
right:0;
height:3px;
background:var(--kc);
border-radius:12px 12px 0 0;
}

.rkpi-val{
font-size:20px;
font-weight:800;
color:var(--kc);
}

.rkpi-lbl{
font-size:11px;
opacity:.7;
margin-top:2px;
}

.report-hint{
display:flex;
align-items:center;
gap:6px;
font-size:12px;
opacity:.75;
margin-bottom:16px;
border-bottom:1px solid var(--line);
padding-bottom:10px;
}

.report-area{
margin-top:20px;
padding-top:12px;
border-top:1px solid var(--line);
}

.report-area-head{
display:flex;
align-items:center;
gap:8px;
font-weight:700;
margin-bottom:8px;
}

.report-area-count{
margin-left:auto;
font-size:11px;
opacity:.6;
}

.report-score-bar{
height:6px;
border-radius:6px;
background:var(--line);
overflow:hidden;
margin-top:6px;
}

.report-score-bar span{
display:block;
height:100%;
background:var(--kc);
}

.report-risk{
margin-top:16px;
margin-bottom:20px;
padding:14px;
border:1px solid var(--line);
border-radius:12px;
background:var(--chip);
}

.risk-title{
font-weight:700;
margin-bottom:10px;
font-size:13px;
}

.risk-row{
display:grid;
grid-template-columns:80px 1fr 30px;
align-items:center;
gap:10px;
margin-bottom:8px;
}

.risk-row span{
width:60px;
}

.risk-row b{
width:20px;
text-align:right;
}

.risk-bar{
height:8px;
border-radius:8px;
background:rgba(0,0,0,.08);
overflow:hidden;
position:relative;
}

html[data-theme="dark"] .risk-bar{
background:rgba(255,255,255,.08);
}

.risk-bar span{
display:block;
height:100%;
border-radius:8px;
transition:width .35s ease;
}

/* cores */

.risk-row.crit .risk-bar span{
background:#ef4444;
}

.risk-row.alto .risk-bar span{
background:#f97316;
}

.risk-row.medio .risk-bar span{
background:#eab308;
}

.risk-row.baixo .risk-bar span{
background:#22c55e;
}

.modal-overlay{
animation: modalFade .2s ease;
}

@keyframes modalFade{
from{opacity:0;}
to{opacity:1;}
}

</style>


<div class="card">
<h3>Cyberplanner</h3>

<div class="panel" style="margin-top:10px">

<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:16px;">

<div>
<h2 style="margin:0">Plano de Segurança</h2>
<p class="hint" style="margin-top:6px">
Responde por área • Edita o plano gerado • Exporta em PDF (QNRCS / NIS2)
</p>
</div>


<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">

<div style="text-align:center; min-width:60px;">
<div id="scorePercent" style="font-size:22px; font-weight:900; color:#6b7280;">—%</div>
<div id="scoreLabel" style="font-size:11px; color:#6b7280;">Score</div>
</div>

<span class="chip"><i data-lucide="check"></i> <b id="kYes">0</b></span>
<span class="chip"><i data-lucide="circle-dot"></i> <b id="kPartial">0</b></span>
<span class="chip"><i data-lucide="x"></i> <b id="kNo">0</b></span>
<span class="chip"><i data-lucide="minus"></i> <b id="kNA">0</b></span>

<button id="btnFinishQ" class="btn primary" type="button">
<i data-lucide="file-text"></i> Ver Plano & PDF
</button>

</div>
</div>


<div style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
<div style="flex:1; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08); border-radius:14px; overflow:hidden;">
<div id="progressBar" style="height:8px; width:0%; background:rgba(46,204,113,.75); transition:width .4s ease;"></div>
</div>
<span class="muted" id="progressText" style="font-size:12px; white-space:nowrap;">0/0 respondidas</span>
</div>

<div id="areaTabs"></div>

<div id="formSections"></div>

</div>
</div>



<div id="toast" style="position:fixed; right:16px; bottom:16px; z-index:9999; display:none; min-width:260px;">
<div class="panel" style="border:1px solid rgba(255,255,255,.12); border-left:3px solid #22c55e;">
<div style="font-weight:900;" id="toastTitle">—</div>
<div class="muted" style="margin-top:4px;" id="toastMsg">—</div>
</div>
</div>



<div id="reportModal" class="modal-overlay is-hidden" aria-hidden="true">

<div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="reportTitle"
style="width:min(860px, 96vw); max-height:92vh; display:flex; flex-direction:column;">

<div class="modal-header" style="flex-shrink:0;">

<div>
<div class="muted" style="margin-bottom:4px; font-size:12px;">Plano de Segurança</div>
<div id="reportTitle" style="font-size:18px; font-weight:800;">Plano — Draft</div>
</div>

<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">

<button id="btnGenPdf" class="btn ok" type="button">
<i data-lucide="download"></i> Gerar PDF
</button>

<button id="btnSendToDocs" class="btn primary" type="button">
<i data-lucide="send"></i> Enviar para Evidências
</button>

<button id="btnCloseReport" class="btn" type="button">
<i data-lucide="x"></i>
</button>

</div>
</div>


<div id="reportBody"
style="flex:1; overflow-y:auto; padding:16px; background:rgba(255,255,255,.02); border-radius:0 0 12px 12px;">
</div>

</div>
</div>

@endsection


@push('scripts')

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

@vite(['resources/js/pages/questionnaire.js'])

@endpush