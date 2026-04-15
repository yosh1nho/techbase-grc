@extends('layouts.app')
@section('title', 'Cyberplanner • Techbase GRC')

@section('content')



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

@push('styles')
@vite(['resources/css/pages/questionnaire.css'])
@endpush

@endsection


@push('scripts')

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

@vite(['resources/js/pages/questionnaire.js'])

@endpush