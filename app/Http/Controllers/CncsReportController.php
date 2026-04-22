<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CncsReportController extends Controller
{
    private const STATUSES        = ['draft', 'submitted', 'acknowledged'];
    private const INCIDENT_TYPES  = [
        'ransomware', 'malware', 'phishing', 'ddos', 'unauthorized_access',
        'data_breach', 'service_disruption', 'backup_failure', 'other',
    ];

    // =========================================================================
    // GET /api/cncs-reports
    // Lista todos os relatórios (com paginação simples).
    // ?status=draft|submitted|acknowledged
    // ?year=2025
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('cncs_report as r')
            ->leftJoin('User as u', 'u.id_user', '=', 'r.reported_by')
            ->select([
                'r.id_report',
                'r.title',
                'r.incident_type',
                'r.status',
                'r.is_urgent',
                'r.created_at',
                'r.submitted_at',
                'u.name  as reporter_name',
                'u.email as reporter_email',
            ])
            ->whereNull('r.deleted_at')
            ->orderByDesc('r.created_at');

        if ($request->filled('status')) {
            $query->where('r.status', $request->status);
        }
        if ($request->filled('year')) {
            $query->whereYear('r.created_at', $request->year);
        }

        $reports = $query->get()->map(fn($r) => $this->formatReport($r));

        return response()->json($reports);
    }

    // =========================================================================
    // GET /api/cncs-reports/{id}
    // Detalhes de um relatório específico.
    // =========================================================================
    public function show(int $id): JsonResponse
    {
        $r = DB::table('cncs_report as r')
            ->leftJoin('User as u', 'u.id_user', '=', 'r.reported_by')
            ->select([
                'r.*',
                'u.name  as reporter_name',
                'u.email as reporter_email',
            ])
            ->where('r.id_report', $id)
            ->whereNull('r.deleted_at')
            ->first();

        if (!$r) {
            return response()->json(['success' => false, 'message' => 'Relatório não encontrado.'], 404);
        }

        return response()->json($this->formatReport($r, true));
    }

    // =========================================================================
    // POST /api/cncs-reports
    // Cria um novo relatório (sempre em draft).
    // =========================================================================
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'              => ['required', 'string', 'max:255'],
            'incident_type'      => ['nullable', Rule::in(self::INCIDENT_TYPES)],
            'report_description' => ['nullable', 'string'],
            'is_urgent'          => ['boolean'],
        ]);

        $userId = session('tb_user.id') ?? null;

        $id = DB::table('cncs_report')->insertGetId([
            'title'              => $data['title'],
            'incident_type'      => $data['incident_type'] ?? null,
            'report_description' => $data['report_description'] ?? null,
            'is_urgent'          => (bool) ($data['is_urgent'] ?? false),
            'status'             => 'draft',
            'reported_by'        => $userId,
            'created_at'         => now(),
        ], 'id_report');

        $report = $this->show($id);
        return response()->json(['success' => true, 'report' => json_decode($report->content())], 201);
    }

    // =========================================================================
    // PUT /api/cncs-reports/{id}
    // Actualiza um relatório (apenas se draft).
    // =========================================================================
    public function update(Request $request, int $id): JsonResponse
    {
        $report = DB::table('cncs_report')->where('id_report', $id)->whereNull('deleted_at')->first();
        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Relatório não encontrado.'], 404);
        }
        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas relatórios em rascunho podem ser editados.',
            ], 409);
        }

        $data = $request->validate([
            'title'              => ['sometimes', 'string', 'max:255'],
            'incident_type'      => ['sometimes', 'nullable', Rule::in(self::INCIDENT_TYPES)],
            'report_description' => ['sometimes', 'nullable', 'string'],
            'is_urgent'          => ['sometimes', 'boolean'],
        ]);

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'Nenhum campo para actualizar.'], 422);
        }

        DB::table('cncs_report')->where('id_report', $id)->update($data);

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // POST /api/cncs-reports/{id}/submit
    // Submete o relatório ao CNCS (draft → submitted).
    // =========================================================================
    public function submit(int $id): JsonResponse
    {
        $report = DB::table('cncs_report')->where('id_report', $id)->whereNull('deleted_at')->first();
        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Relatório não encontrado.'], 404);
        }
        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => "O relatório já está em estado \"{$report->status}\".",
            ], 409);
        }

        DB::table('cncs_report')->where('id_report', $id)->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        return response()->json(['success' => true, 'status' => 'submitted']);
    }

    // =========================================================================
    // DELETE /api/cncs-reports/{id}
    // Soft delete (apenas drafts).
    // =========================================================================
    public function destroy(int $id): JsonResponse
    {
        $report = DB::table('cncs_report')->where('id_report', $id)->whereNull('deleted_at')->first();
        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Relatório não encontrado.'], 404);
        }
        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas rascunhos podem ser eliminados.',
            ], 409);
        }

        DB::table('cncs_report')->where('id_report', $id)->update(['deleted_at' => now()]);

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // GET /api/cncs-reports/report-data
    // Agrega os dados reais para preencher o relatório CNCS:
    //   - riscos por score (para a secção de KPIs)
    //   - incidentes trimestrais (relatórios CNCS já submetidos por trimestre)
    //   - planos de tratamento concluídos (secção 7 — medidas implementadas)
    //   - controlos de compliance conformes (para a tabela de conformidade)
    //
    // Query params:
    //   ?year=2025   (default: ano actual)
    //   ?scope=relevant|all
    // =========================================================================
    public function reportData(Request $request): JsonResponse
    {
        $year  = (int) ($request->input('year', date('Y')));
        $scope = $request->input('scope', 'relevant');

        return response()->json([
            'kpis'        => $this->buildKpis($year, $scope),
            'quarters'    => $this->buildQuarters($year),
            'measures'    => $this->buildMeasures($year),
            'compliance'  => $this->buildComplianceTable($request),
        ]);
    }

    // =========================================================================
    // GET /api/cncs-reports/compliance-table
    // Lista paginada de controlos conformes e parcialmente conformes.
    // Separado para poder paginar sem recarregar tudo.
    //
    // ?framework=NIS2|QNRCS|all  (default: all)
    // ?status=compliant|partial|non_compliant|all  (default: compliant,partial)
    // ?page=1  ?per_page=20
    // =========================================================================
    public function complianceTable(Request $request): JsonResponse
    {
        return response()->json($this->buildComplianceTable($request));
    }


// =========================================================================
// POST /api/cncs-reports/ai-summary
// Recebe o cachedReportData (já calculado pelo reportData()),
// constrói um prompt rico e devolve 3 narrativas via Gemini.
// =========================================================================
public function aiSummary(Request $request): JsonResponse
{
    $year  = (int) ($request->input('year', date('Y')));
    $scope = $request->input('scope', 'relevant');
    $data  = $request->input('report_data', []);

    // Extrai métricas do cachedReportData que o frontend já calculou
    $kpis     = $data['kpis']     ?? [];
    $quarters = $data['quarters'] ?? [];
    $measures = $data['measures'] ?? [];

    $incTotal    = $kpis['incidents_total']    ?? 0;
    $incRelevant = $kpis['incidents_relevant'] ?? 0;
    $highRisks   = $kpis['high_risks']         ?? 0;

    // Formata trimestres para o prompt
    $quarterLines = '';
    foreach ($quarters as $q) {
        $quarterLines .= "  - {$q['q']}: {$q['total']} incidente(s) — tipos: {$q['types']}\n";
    }
    if (!$quarterLines) $quarterLines = "  - Sem incidentes registados no ano.\n";

    // Formata medidas (top 5)
    $measureLines = '';
    foreach (array_slice($measures, 0, 5) as $m) {
        $measureLines .= "  - [{$m['status']}] {$m['title']}: {$m['detail']}\n";
    }
    if (!$measureLines) $measureLines = "  - Nenhuma medida registada.\n";

    // Enriquece com dados adicionais diretos da BD que o reportData() não inclui
    $riskBreakdown = DB::table('risk as r')
    ->leftJoin(DB::raw('(
        SELECT DISTINCT ON (id_risk) id_risk, probability, impact, score
        FROM riskassessmenthistory
        ORDER BY id_risk, assessedat DESC
    ) AS la'), 'la.id_risk', '=', 'r.id_risk')
    ->selectRaw("
        COUNT(*) FILTER (WHERE COALESCE(la.score, la.probability * la.impact, 0) >= 17) AS muito_alta,
        COUNT(*) FILTER (WHERE COALESCE(la.score, la.probability * la.impact, 0) BETWEEN 10 AND 16) AS alta,
        COUNT(*) FILTER (WHERE COALESCE(la.score, la.probability * la.impact, 0) BETWEEN 5 AND 9) AS media,
        COUNT(*) FILTER (WHERE r.status = 'open') AS abertos,
        COUNT(*) FILTER (WHERE r.status = 'closed') AS fechados
    ")
    ->whereNull('r.deleted_at')
    ->whereRaw("EXTRACT(year FROM r.createdat) = ?", [$year])
    ->first();

    $treatmentStats = DB::table('risktreatmentplan')
        ->selectRaw("
            COUNT(*) FILTER (WHERE status = 'Concluído') AS concluidos,
            COUNT(*) FILTER (WHERE status = 'Em curso') AS em_curso,
            COUNT(*) FILTER (WHERE status = 'Pendente') AS pendentes,
            COUNT(*) AS total
        ")
        ->whereNull('deleted_at')
        ->whereYear('due_date', $year)
        ->first();

    $complianceLines = '';
    $complianceRows = DB::table('compliance_assessment as ca')
        ->join('framework_control as fc', 'ca.id_control', '=', 'fc.id_control')
        ->join('framework_group as fg', 'fg.id_group', '=', 'fc.id_group')
        ->join('framework as f', 'f.id_framework', '=', 'fg.id_framework')
        ->selectRaw("
            f.name AS framework,
            COUNT(*) FILTER (WHERE ca.status = 'compliant') AS conformes,
            COUNT(*) FILTER (WHERE ca.status = 'partial') AS parciais,
            COUNT(*) FILTER (WHERE ca.status = 'non_compliant') AS nao_conformes,
            COUNT(*) AS total
        ")
        ->groupBy('f.name')
        ->get();

    foreach ($complianceRows as $row) {
        $complianceLines .= "  - {$row->framework}: {$row->conformes} conformes, {$row->parciais} parciais, {$row->nao_conformes} não conformes (total: {$row->total})\n";
    }
    if (!$complianceLines) $complianceLines = "  - Sem avaliações de conformidade registadas.\n";

    $docStats = DB::table('document')
        ->selectRaw("
            COUNT(*) FILTER (WHERE status = 'approved') AS aprovados,
            COUNT(*) FILTER (WHERE status = 'pending') AS pendentes,
            COUNT(*) FILTER (WHERE non_compliant = true) AS sem_assinatura,
            COUNT(*) AS total
        ")
        ->whereYear('created_at', $year)
        ->first();

    $wazuhCount  = DB::table('wazuh_alert_analysis')->whereYear('created_at', $year)->count();
    $acronisHigh = DB::table('acronis_alert')
        ->whereIn('severity', ['high', 'critical'])
        ->whereYear('created_at', $year)
        ->count();

    $scopeLabel = $scope === 'relevant' ? 'incidentes relevantes/substanciais' : 'todos os incidentes';

    $prompt = <<<PROMPT
Atuas como CISO experiente e perito em conformidade NIS2 e QNRCS (Portugal).
Redige 3 narrativas em português europeu formal para o Relatório Anual CNCS do ano {$year}.

DADOS DO ANO {$year} ({$scopeLabel}):

=== INCIDENTES ===
- Total registados: {$incTotal} | Relevantes/substanciais: {$incRelevant}
- Por trimestre:
{$quarterLines}
=== RISCOS ===
- Criados no ano: {$riskBreakdown->abertos} abertos, {$riskBreakdown->fechados} fechados
- Nível Muito Alta (≥17): {$riskBreakdown->muito_alta} | Alta (10-16): {$riskBreakdown->alta} | Média (5-9): {$riskBreakdown->media}
- Riscos alta/muito alta em aberto (acumulado): {$highRisks}

=== TRATAMENTO ===
- Planos no ano: {$treatmentStats->total} total | {$treatmentStats->concluidos} concluídos | {$treatmentStats->em_curso} em curso | {$treatmentStats->pendentes} pendentes
- Medidas implementadas (amostra):
{$measureLines}
=== CONFORMIDADE NIS2/QNRCS ===
{$complianceLines}
=== DOCUMENTOS ===
- No ano: {$docStats->total} total | {$docStats->aprovados} aprovados | {$docStats->pendentes} pendentes | {$docStats->sem_assinatura} sem assinatura digital

=== MONITORIZAÇÃO ===
- Alertas SIEM Wazuh processados: {$wazuhCount}
- Alertas Acronis alta/crítica severidade: {$acronisHigh}

INSTRUÇÕES DE OUTPUT:
Devolve EXCLUSIVAMENTE um JSON válido, sem texto antes nem depois, sem markdown, sem blocos ```.
Formato exato:
{
  "activities": "<Secção 3 — atividades de segurança — 3 a 5 parágrafos coesos baseados nos dados acima>",
  "recommendations": "<Secção 6 — recomendações — 3 a 5 pontos concretos priorizados pelos riscos e lacunas identificadas>",
  "extra_info": "<Secção 8 — outra informação — 1 a 2 parágrafos sobre monitorização (Wazuh/Acronis) e conformidade documental>"
}
PROMPT;

    try {
        $gemini = new \App\Services\GeminiClient();
        $raw    = $gemini->generate($prompt);

        // Limpa eventuais backticks que o modelo possa devolver
        $clean  = trim(preg_replace('/^```(?:json)?\s*/i', '', preg_replace('/\s*```$/i', '', trim($raw))));
        $parsed = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Resposta inválida do Gemini.', 'raw' => $raw], 500);
        }

        return response()->json([
            'activities'      => $parsed['activities']      ?? '',
            'recommendations' => $parsed['recommendations'] ?? '',
            'extra_info'      => $parsed['extra_info']      ?? '',
        ]);

    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function buildKpis(int $year, string $scope): array
    {
        // Riscos com score alto (usa assessment mais recente)
        $highRisks = DB::table('risk as r')
            ->join(DB::raw('(
                SELECT DISTINCT ON (id_risk) id_risk, score
                FROM riskassessmenthistory
                ORDER BY id_risk, assessedat DESC
            ) AS la'), 'la.id_risk', '=', 'r.id_risk')
            ->where('la.score', '>=', 17)
            ->whereNull('r.deleted_at')
            ->count();

        // Total de incidentes registados no ano (relatórios CNCS criados)
        $incidentsTotal = DB::table('cncs_report')
            ->whereYear('created_at', $year)
            ->whereNull('deleted_at')
            ->count();

        // Incidentes relevantes/substanciais (is_urgent = true)
        $incidentsRelevant = DB::table('cncs_report')
            ->whereYear('created_at', $year)
            ->where('is_urgent', true)
            ->whereNull('deleted_at')
            ->count();

        return [
            'incidents_total'    => $scope === 'all' ? $incidentsTotal : $incidentsRelevant,
            'incidents_relevant' => $incidentsRelevant,
            'high_risks'         => $highRisks,
        ];
    }

    private function buildQuarters(int $year): array
    {
        // Agrupar relatórios CNCS por trimestre
        $rows = DB::table('cncs_report')
            ->selectRaw("
                EXTRACT(QUARTER FROM created_at)::int AS quarter,
                COUNT(*) AS total,
                STRING_AGG(DISTINCT incident_type, ', ') AS types
            ")
            ->whereYear('created_at', $year)
            ->whereNull('deleted_at')
            ->groupByRaw('EXTRACT(QUARTER FROM created_at)')
            ->orderByRaw('EXTRACT(QUARTER FROM created_at)')
            ->get();

        // Garantir os 4 trimestres mesmo que não haja dados
        $byQ = $rows->keyBy('quarter');
        $typeLabels = [
            'ransomware'         => 'Ransomware',
            'malware'            => 'Malware',
            'phishing'           => 'Phishing',
            'ddos'               => 'DDoS',
            'unauthorized_access'=> 'Acesso indevido',
            'data_breach'        => 'Fuga de dados',
            'service_disruption' => 'Indisponibilidade',
            'backup_failure'     => 'Backup falhou',
            'other'              => 'Outro',
        ];

        return collect([1, 2, 3, 4])->map(function ($q) use ($byQ, $typeLabels) {
            $row = $byQ[$q] ?? null;
            $typesRaw = $row?->types ?? '';
            $typesFormatted = collect(explode(', ', $typesRaw))
                ->filter()
                ->map(fn($t) => $typeLabels[trim($t)] ?? $t)
                ->join(', ');

            return [
                'q'     => "Q{$q}",
                'total' => (int) ($row?->total ?? 0),
                'types' => $typesFormatted ?: '—',
            ];
        })->values()->toArray();
    }

    private function buildMeasures(int $year): array
    {
        // Planos de tratamento concluídos no ano = "medidas implementadas"
        $plans = DB::table('risktreatmentplan as rtp')
            ->leftJoin('risk as r', 'r.id_risk', '=', 'rtp.id_risk')
            ->leftJoin('asset as a', 'a.id_asset', '=', 'r.id_asset')
            ->select([
                'rtp.id_plan',
                'rtp.strategy',
                'rtp.due_date',
                'rtp.status',
                'r.title      as risk_title',
                'r.description as risk_description',
                'a.display_name as asset_name',
                'a.hostname     as asset_hostname',
            ])
            ->whereIn('rtp.status', ['Concluído', 'Em curso'])
            ->whereNull('rtp.deleted_at')
            ->whereYear('rtp.due_date', $year)
            ->orderByRaw("CASE WHEN rtp.status = 'Concluído' THEN 0 ELSE 1 END")
            ->limit(20)
            ->get();

        return $plans->map(fn($p) => [
            'title'  => $p->risk_title ?? "Plano #{$p->id_plan}",
            'detail' => trim(implode(' · ', array_filter([
                $p->risk_description,
                $p->asset_name ?? $p->asset_hostname,
                "Estratégia: {$p->strategy}",
            ]))),
            'tags'   => array_filter([$p->strategy]),
            'status' => $p->status === 'Concluído' ? 'Concluído' : 'Em progresso',
        ])->toArray();
    }

    private function buildComplianceTable(Request $request): array
    {
        $frameworkFilter = $request->input('framework', 'all');
        $statusFilter    = $request->input('status', 'compliant,partial'); // default: só conformes e parciais
        $page            = max(1, (int) $request->input('page', 1));
        $perPage         = min(100, max(5, (int) $request->input('per_page', 20)));

        // Assessment mais recente por controlo
        $query = DB::table(DB::raw('(
            SELECT DISTINCT ON (ca.id_control)
                ca.id_control,
                ca.status,
                ca.notes,
                ca.evidence_link,
                ca.assessed_at,
                ca.assessed_by
            FROM compliance_assessment ca
            ORDER BY ca.id_control, ca.assessed_at DESC
        ) AS latest'))
            ->join('framework_control as fc', 'fc.id_control', '=', 'latest.id_control')
            ->join('framework_group as fg', 'fg.id_group', '=', 'fc.id_group')
            ->join('framework as f', 'f.id_framework', '=', 'fg.id_framework')
            ->leftJoin('User as u', 'u.id_user', '=', 'latest.assessed_by')
            ->select([
                'fc.id_control',
                'fc.control_code',
                'fc.description',
                'fg.code    as group_code',
                'fg.name    as group_name',
                'f.name     as framework_name',
                'latest.status',
                'latest.notes',
                'latest.evidence_link',
                'latest.assessed_at',
                'u.name     as assessed_by_name',
            ]);

        // Filtro por framework
        if ($frameworkFilter !== 'all') {
            $query->where('f.name', $frameworkFilter);
        }

        // Filtro por status (pode ser lista separada por vírgula)
        $statuses = array_filter(array_map('trim', explode(',', $statusFilter)));
        $validStatuses = ['compliant', 'partial', 'non_compliant'];
        $statuses = array_intersect($statuses, $validStatuses);

        if (!empty($statuses) && !in_array('all', explode(',', $statusFilter))) {
            $query->whereIn('latest.status', $statuses);
        }

        $query->orderBy('f.name')->orderBy('fg.sort_order')->orderBy('fc.sort_order');

        // Paginação manual (evita Eloquent para manter consistência com o resto)
        $total   = (clone $query)->count();
        $rows    = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $statusLabels = [
            'compliant'     => 'Conforme',
            'partial'       => 'Parcialmente conforme',
            'non_compliant' => 'Não conforme',
        ];

        return [
            'data' => $rows->map(fn($r) => [
                'control_id'     => $r->id_control,
                'control_code'   => $r->control_code,
                'description'    => $r->description,
                'group_code'     => $r->group_code,
                'group_name'     => $r->group_name,
                'framework'      => $r->framework_name,
                'status'         => $r->status,
                'status_label'   => $statusLabels[$r->status] ?? $r->status,
                'notes'          => $r->notes,
                'evidence_link'  => $r->evidence_link,
                'assessed_at'    => $r->assessed_at,
                'assessed_by'    => $r->assessed_by_name,
            ])->toArray(),
            'pagination' => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int) ceil($total / $perPage),
            ],
        ];
    }

    private function formatReport($r, bool $full = false): array
    {
        $base = [
            'id'           => $r->id_report,
            'title'        => $r->title,
            'incident_type'=> $r->incident_type,
            'is_urgent'    => (bool) $r->is_urgent,
            'status'       => $r->status,
            'created_at'   => $r->created_at,
            'submitted_at' => $r->submitted_at ?? null,
            'reporter'     => $r->reporter_name ?? $r->reporter_email ?? null,
        ];

        if ($full) {
            $base['report_description'] = $r->report_description ?? null;
        }

        return $base;
    }
}