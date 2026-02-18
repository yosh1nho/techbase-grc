@extends('layouts.app')

@section('title', 'Gestão de Risco • Techbase GRC')

@section('content')
    <div class="card">
        <h3>Riscos (RF7, RF8, RF9, RF12)</h3>

        <div class="two" style="margin-top:10px">
            <div class="panel">
                <h2>Calculadora (Prob × Impacto)</h2>

                <div class="two">
                    <div class="field">
                        <label>Probabilidade</label>
                        <select id="prob">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="field">
                        <label>Impacto</label>
                        <select id="impact">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="kpirow">
                    <span class="chip">Score: <b id="scoreValue">1</b></span>
                    <span class="chip" id="classChip">Classe: Baixo</span>
                </div>

                <p class="hint">Regra: score = prob × impacto. Classificação conforme escala definida.</p>
            </div>

            <div class="panel">
                <h2>Aceitação formal de risco (RF12)</h2>
                <div class="field">
                    <label>Risco</label>
                    <select>
                        <option>R-003 — Falha de backup testado</option>
                    </select>
                </div>
                <div class="two">
                    <div class="field"><label>Aprovador</label><input placeholder="Nome / role" /></div>
                    <div class="field"><label>Data</label><input placeholder="YYYY-MM-DD" /></div>
                </div>
                <div class="field">
                    <label>Justificativa</label>
                    <textarea placeholder="Motivo para aceitar o risco e condições."></textarea>
                </div>
                <button class="btn warn" type="button">Registar aceitação</button>
            </div>
        </div>

        <div style="height:12px"></div>

        <div class="panel">
            <h2>Registo de riscos + recomendações (RF8)</h2>
            {{-- tua tabela aqui (igual já tens) --}}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function classify(score) {
            // exemplo simples (ajusta para tua escala)
            if (score <= 4) return { label: 'Baixo', chipClass: 'chip ok' };
            if (score <= 9) return { label: 'Médio', chipClass: 'chip warn' };
            return { label: 'Alto', chipClass: 'chip bad' };
        }

        function updateRiskCalc() {
            const prob = parseInt(document.getElementById('prob').value, 10);
            const impact = parseInt(document.getElementById('impact').value, 10);
            const score = prob * impact;

            document.getElementById('scoreValue').textContent = score;

            const cls = classify(score);
            const chip = document.getElementById('classChip');
            chip.textContent = 'Classe: ' + cls.label;
            chip.className = cls.chipClass;
        }

        document.getElementById('prob').addEventListener('change', updateRiskCalc);
        document.getElementById('impact').addEventListener('change', updateRiskCalc);

        updateRiskCalc(); // inicial
    </script>
@endpush