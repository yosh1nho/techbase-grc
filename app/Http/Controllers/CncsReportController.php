<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\GeminiClient;
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
            'kpis'             => $this->buildKpis($year, $scope),
            'quarters'         => $this->buildQuarters($year),
            'measures'         => $this->buildMeasures($year),
            'compliance'       => $this->buildComplianceTable($request),
            // NOVO — dados que alimentam a IA e o PDF enriquecido
            'assets_summary'   => $this->buildAssetsSummary(),
            'incidents_detail' => $this->buildIncidentsDetail($year),
            'risk_summary'     => $this->buildRiskSummary($year),
        ]);
    }


    // =========================================================================
    // POST /api/cncs-reports/generate-narrative
    // Agrega dados reais da BD e pede à IA para gerar as secções narrativas.
    // Body: { year, scope, entity_name }
    // Retorna: { section3, section5, section6, section8 }
    // =========================================================================
    public function generateNarrative(Request $request): JsonResponse
    {
        $year       = (int) ($request->input('year', date('Y')));
        $scope      = $request->input('scope', 'relevant');
        $entityName = $request->input('entity_name', 'Entidade');
    
        // 1. Agregar todos os dados relevantes
        $kpis       = $this->buildKpis($year, $scope);
        $quarters   = $this->buildQuarters($year);
        $measures   = $this->buildMeasures($year);
        $assets     = $this->buildAssetsSummary();
        $incidents  = $this->buildIncidentsDetail($year);
        $risks      = $this->buildRiskSummary($year);
        $compliance = $this->buildComplianceNarrativeSummary();
    
        // 2. Construir o prompt com contexto real
        $prompt = $this->buildNarrativePrompt(
            $year, $entityName, $kpis, $quarters, $measures,
            $assets, $incidents, $risks, $compliance
        );
    
        // 3. Chamar a IA
        try {
            $gemini = new GeminiClient();
            $raw    = $gemini->generate($prompt);
    
            // Extrair JSON da resposta
            $parsed = $this->extractJsonFromAiResponse($raw);
    
            return response()->json([
                'success'  => true,
                'year'     => $year,
                'sections' => $parsed,
                'raw'      => $raw, // para debug; pode remover em produção
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar narrativa: ' . $e->getMessage(),
            ], 500);
        }
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
// GET /api/cncs-reports/incidents-for-report?year=2025&scope=relevant
// Devolve os incidentes da tabela `incident` do ano seleccionado,
// prontos para o step 2b do relatório anual.
// =========================================================================
public function incidentsForReport(Request $request): JsonResponse
{
    $year  = (int) ($request->input('year', date('Y')));
    $scope = $request->input('scope', 'relevant'); // relevant = só urgentes/graves
 
    $query = DB::table('incident as i')
        ->leftJoin('User as u', 'u.id_user', '=', 'i.reported_by')
        ->select([
            'i.id_incident',
            'i.title',
            'i.incident_type',
            'i.status',
            'i.severity',
            'i.is_urgent',
            'i.detected_at',
            'i.resolved_at',
            'i.closed_at',
            'i.affected_users',
            'i.affected_systems',
            'i.operational_impact',
            'i.containment_actions',
            'i.cross_border',
            'i.description',
            'u.name as reporter_name',
        ])
        ->whereNull('i.deleted_at')
        ->whereYear('i.created_at', $year)
        ->orderByDesc('i.detected_at');
 
    // Escopo "relevant" = urgentes OU severidade alta/crítica
    if ($scope === 'relevant') {
        $query->where(function ($q) {
            $q->where('i.is_urgent', true)
              ->orWhereIn('i.severity', ['high', 'critical', 'alto', 'crítico', 'critico']);
        });
    }
 
    $incidents = $query->get()->map(function ($i) {
        // Calcular duração em horas
        $durationHours = null;
        $start = $i->detected_at;
        $end   = $i->resolved_at ?? $i->closed_at;
        if ($start && $end) {
            $diff = (strtotime($end) - strtotime($start)) / 3600;
            $durationHours = round($diff, 1);
        }
 
        return [
            'id'                 => $i->id_incident,
            'title'              => $i->title,
            'incident_type'      => $i->incident_type,
            'status'             => $i->status,
            'severity'           => $i->severity,
            'is_urgent'          => (bool) $i->is_urgent,
            'detected_at'        => $i->detected_at,
            'resolved_at'        => $i->resolved_at ?? $i->closed_at,
            'duration_hours'     => $durationHours,
            'affected_users'     => $i->affected_users,
            'affected_systems'   => $i->affected_systems,
            'operational_impact' => $i->operational_impact,
            'cross_border'       => $i->cross_border,
            'description'        => $i->description,
            'reporter'           => $i->reporter_name,
        ];
    });
 
    // Totais agregados (todos os incidentes devolvidos)
    $totalAffectedUsers = $incidents
        ->filter(fn($i) => is_numeric($i['affected_users']))
        ->sum(fn($i) => (int) $i['affected_users']);
 
    $totalDurationHours = $incidents
        ->filter(fn($i) => $i['duration_hours'] !== null)
        ->sum(fn($i) => $i['duration_hours']);
 
    $hasCrossBorder = $incidents->contains(fn($i) => $i['cross_border'] === 'yes');
 
    return response()->json([
        'incidents'           => $incidents->values(),
        'totals' => [
            'count'           => $incidents->count(),
            'affected_users'  => $totalAffectedUsers,
            'duration_hours'  => round($totalDurationHours, 1),
            'cross_border'    => $hasCrossBorder,
        ],
    ]);
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


    // =========================================================================
    // POST /api/incidents/from-alert
    // Promove um alerta (Wazuh/Acronis) a Incidente (Rascunho)
    // =========================================================================
    public function storeFromAlert(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => ['required', 'string'],
            'description' => ['required', 'string'],
            'severity'    => ['nullable', 'string'],
            'source'      => ['nullable', 'string'],
        ]);

        // Se a severidade for alta, marcamos o incidente como urgente automaticamente
        $severityStr = strtolower($request->input('severity', ''));
        $isUrgent = in_array($severityStr, ['high', 'critical', 'alto', 'crítico', 'critico']);

        $userId = session('tb_user.id') ?? null;

        // Inserir na tabela de incidentes
        $incidentId = DB::table('cncs_report')->insertGetId([
            'title'              => 'Alerta: ' . $request->input('title'),
            'incident_type'      => $request->input('source', 'Alerta Automático'),
            'report_description' => $request->input('description') . "\n\nSeveridade Original: " . strtoupper($severityStr),
            'is_urgent'          => $isUrgent,
            'status'             => 'draft', // Fica como rascunho para ser revisto na aba de Incidentes
            'reported_by'        => $userId,
            'created_at'         => now(),
        ], 'id_report');

        return response()->json([
            'success'     => true,
            'incident_id' => $incidentId,
            'message'     => 'Alerta promovido a Incidente com sucesso!',
        ], 201);
    }


    /**
     * Resumo de ativos registados no sistema.
     */
    private function buildAssetsSummary(): array
    {
        // A tabela asset não tem deleted_at — usa status para filtrar inativos
        $total = DB::table('asset')->where('status', '!=', 'deleted')->count();
    
        $byCriticality = DB::table('asset')
            ->where('status', '!=', 'deleted')
            ->selectRaw("criticality, COUNT(*) as total")
            ->groupBy('criticality')
            ->pluck('total', 'criticality')
            ->toArray();
    
        $withoutBackup = DB::table('asset')
            ->where('status', '!=', 'deleted')
            ->where(function ($q) {
                $q->where('backup_enabled', false)->orWhereNull('backup_enabled');
            })
            ->count();
    
        $withoutAntimalware = DB::table('asset')
            ->where('status', '!=', 'deleted')
            ->where(function ($q) {
                $q->where('antimalware_enabled', false)->orWhereNull('antimalware_enabled');
            })
            ->count();
    
        $byType = DB::table('asset')
            ->where('status', '!=', 'deleted')
            ->selectRaw("type, COUNT(*) as total")
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();
    
        $offlineAgents = DB::table('asset')
            ->where('status', '!=', 'deleted')
            ->where('agent_status', 'not like', '%online%')
            ->whereNotNull('agent_status')
            ->count();
    
        return [
            'total'               => $total,
            'by_criticality'      => $byCriticality,
            'without_backup'      => $withoutBackup,
            'without_antimalware' => $withoutAntimalware,
            'by_type'             => $byType,
            'offline_agents'      => $offlineAgents,
        ];
    }
    
    /**
     * Detalhe dos incidentes do ano (além dos KPIs já existentes).
     * Inclui duração média estimada, afetados por tipo, alertas Wazuh/Acronis.
     */
    private function buildIncidentsDetail(int $year): array
    {
        // Incidentes por tipo e urgência
        $byType = DB::table('cncs_report')
            ->selectRaw("
                incident_type,
                COUNT(*) as total,
                SUM(CASE WHEN is_urgent THEN 1 ELSE 0 END) as urgent
            ")
            ->whereYear('created_at', $year)
            ->whereNull('deleted_at')
            ->groupBy('incident_type')
            ->get()
            ->map(fn($r) => [
                'type'   => $r->incident_type ?? 'Outro',
                'total'  => (int) $r->total,
                'urgent' => (int) $r->urgent,
            ])
            ->toArray();
    
        // Alertas automáticos convertidos a incidente (Wazuh / Acronis)
        $autoAlerts = DB::table('acronis_alert')
            ->whereYear('created_at', $year)
            ->count();
    
        // Distribuição por mês
        $byMonth = DB::table('cncs_report')
            ->selectRaw("EXTRACT(MONTH FROM created_at)::int as month, COUNT(*) as total")
            ->whereYear('created_at', $year)
            ->whereNull('deleted_at')
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->orderByRaw('EXTRACT(MONTH FROM created_at)')
            ->get()
            ->keyBy('month')
            ->map(fn($r) => (int) $r->total)
            ->toArray();
    
        // Status dos incidentes (draft / submitted / acknowledged)
        $byStatus = DB::table('cncs_report')
            ->selectRaw("status, COUNT(*) as total")
            ->whereYear('created_at', $year)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    
        return [
            'by_type'        => $byType,
            'by_month'       => $byMonth,
            'by_status'      => $byStatus,
            'auto_alerts'    => $autoAlerts,
        ];
    }
    
    /**
     * Resumo de riscos — novos, resolvidos, distribuição por score.
     */
    private function buildRiskSummary(int $year): array
    {
        $total = DB::table('risk')->whereNull('deleted_at')->count();
    
        // Novos riscos registados no ano — coluna chama-se "createdat" (sem underscore)
        $newThisYear = DB::table('risk')
            ->whereNull('deleted_at')
            ->whereYear('createdat', $year)
            ->count();
    
        // Distribuição por score (assessment mais recente)
        $scoreDistribution = DB::table(DB::raw('(
            SELECT DISTINCT ON (id_risk) id_risk, score
            FROM riskassessmenthistory
            ORDER BY id_risk, assessedat DESC
        ) AS la'))
            ->selectRaw("
                CASE
                    WHEN score >= 17 THEN 'Crítico (≥17)'
                    WHEN score >= 10 THEN 'Alto (10–16)'
                    WHEN score >= 5  THEN 'Médio (5–9)'
                    ELSE                  'Baixo (<5)'
                END as nivel,
                COUNT(*) as total
            ")
            ->groupBy(DB::raw("
                CASE
                    WHEN score >= 17 THEN 'Crítico (≥17)'
                    WHEN score >= 10 THEN 'Alto (10–16)'
                    WHEN score >= 5  THEN 'Médio (5–9)'
                    ELSE                  'Baixo (<5)'
                END
            "))
            ->pluck('total', 'nivel')
            ->toArray();
    
        // Planos de tratamento concluídos no ano
        $treated = DB::table('risktreatmentplan')
            ->whereNull('deleted_at')
            ->where('status', 'Concluído')
            ->whereYear('due_date', $year)
            ->count();
    
        // Riscos sem plano de tratamento (críticos)
        $highWithoutPlan = DB::table('risk as r')
            ->join(DB::raw('(
                SELECT DISTINCT ON (id_risk) id_risk, score
                FROM riskassessmenthistory
                ORDER BY id_risk, assessedat DESC
            ) AS la'), 'la.id_risk', '=', 'r.id_risk')
            ->leftJoin('risktreatmentplan as rtp', function ($j) {
                $j->on('rtp.id_risk', '=', 'r.id_risk')
                ->whereNull('rtp.deleted_at');
            })
            ->where('la.score', '>=', 17)
            ->whereNull('r.deleted_at')
            ->whereNull('rtp.id_plan')
            ->count();
    
        return [
            'total'                 => $total,
            'new_this_year'         => $newThisYear,
            'score_distribution'    => $scoreDistribution,
            'treated_this_year'     => $treated,
            'high_without_plan'     => $highWithoutPlan,
        ];
    }
    
    /**
     * Sumário de conformidade para o prompt da IA.
     * Conta controlos por status e frameworks avaliados.
     */
    private function buildComplianceNarrativeSummary(): array
    {
        $totals = DB::table(DB::raw('(
            SELECT DISTINCT ON (ca.id_control) ca.id_control, ca.status
            FROM compliance_assessment ca
            ORDER BY ca.id_control, ca.assessed_at DESC
        ) AS latest'))
            ->selectRaw("status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    
        $frameworks = DB::table('framework')
            ->pluck('name')
            ->toArray();
    
        return [
            'compliant'     => (int) ($totals['compliant']     ?? 0),
            'partial'       => (int) ($totals['partial']       ?? 0),
            'non_compliant' => (int) ($totals['non_compliant'] ?? 0),
            'frameworks'    => $frameworks,
        ];
    }
    
    /**
     * Constrói o prompt que vai para a IA, com todos os dados reais.
     */
    private function buildNarrativePrompt(
        int $year,
        string $entityName,
        array $kpis,
        array $quarters,
        array $measures,
        array $assets,
        array $incidents,
        array $risks,
        array $compliance
    ): string {
        $quartersText = collect($quarters)
            ->map(fn($q) => "{$q['q']}: {$q['total']} incidente(s) — tipos: {$q['types']}")
            ->join('; ');
    
        $measuresText = collect($measures)
            ->take(10)
            ->map(fn($m) => "- {$m['title']} [{$m['status']}]: {$m['detail']}")
            ->join("\n");
    
        $assetsCrit = collect($assets['by_criticality'])
            ->map(fn($v, $k) => "$k: $v")
            ->join(', ');
    
        $scoreDistrib = collect($risks['score_distribution'])
            ->map(fn($v, $k) => "$k: $v")
            ->join(', ');
    
        $frameworks = implode(', ', $compliance['frameworks'] ?: ['NIS2', 'QNRCS']);
    
        return <<<PROMPT
    És um especialista em cibersegurança e conformidade regulatória em Portugal.
    Gera as secções narrativas do Relatório Anual CNCS para a entidade "{$entityName}", ano {$year}.
    Usa os dados reais fornecidos. Escreve em português europeu formal, tom técnico, entre 80 a 200 palavras por secção.
    Responde APENAS com um objeto JSON válido, sem texto adicional, sem markdown, sem backticks.
    O JSON deve ter exatamente estas chaves: "section3", "section5", "section6", "section8".
    
    DADOS REAIS DO SISTEMA:
    
    ATIVOS ({$assets['total']} total):
    - Distribuição por criticidade: {$assetsCrit}
    - Agentes offline: {$assets['offline_agents']}
    
    INCIDENTES ({$kpis['incidents_total']} total no ano):
    - Com impacto relevante/substancial: {$kpis['incidents_relevant']}
    - Alertas automáticos (Wazuh/Acronis): {$incidents['auto_alerts']}
    - Por trimestre: {$quartersText}
    
    RISCOS ({$risks['total']} total registados):
    - Novos em {$year}: {$risks['new_this_year']}
    - Distribuição por score: {$scoreDistrib}
    - Tratamentos concluídos em {$year}: {$risks['treated_this_year']}
    - Riscos críticos sem plano de tratamento: {$risks['high_without_plan']}
    
    CONFORMIDADE (frameworks: {$frameworks}):
    - Controlos conformes: {$compliance['compliant']}
    - Parcialmente conformes: {$compliance['partial']}
    - Não conformes: {$compliance['non_compliant']}
    
    MEDIDAS IMPLEMENTADAS:
    {$measuresText}
    
    INSTRUÇÕES POR SECÇÃO:
    
    section3 — "Descrição sumária das principais atividades de segurança":
    Descreve as atividades de segurança realizadas no ano com base nos dados: avaliações de conformidade feitas, frameworks utilizados, gestão de riscos (novos riscos identificados, tratamentos concluídos), monitorização de ativos (Wazuh/Acronis), e resposta a incidentes. Não inventes factos.
    
    section5 — "Análise agregada dos incidentes com impacto relevante":
    Analisa os {$kpis['incidents_relevant']} incidentes de impacto relevante/substancial. Comenta a distribuição trimestral, os tipos mais frequentes, e o uso de alertas automáticos. Menciona que os dados de utilizadores afetados e duração devem ser verificados manualmente.
    
    section6 — "Recomendações de melhoria":
    Com base nos {$risks['high_without_plan']} riscos críticos sem plano, nos {$assets['without_backup']} ativos sem backup, nos {$compliance['non_compliant']} controlos não conformes e nos {$assets['without_antimalware']} ativos sem antimalware: formula recomendações concretas e prioritárias. Ordena por criticidade.
    
    section8 — "Outra informação relevante":
    Resume brevemente: total de ativos geridos, integração com sistemas de monitorização (Wazuh/Acronis), estado geral de conformidade regulatória, e qualquer nota sobre a maturidade do programa de segurança com base nos dados apresentados.
    
    Responde APENAS com JSON, exemplo de estrutura esperada:
    {"section3":"...","section5":"...","section6":"...","section8":"..."}
    PROMPT;
    }
    
    /**
     * Extrai o JSON da resposta da IA (que pode ter texto antes/depois).
     */
    private function extractJsonFromAiResponse(string $raw): array
    {
        // Tenta parsear directamente
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    
        // Tenta extrair JSON de dentro de um bloco de texto
        if (preg_match('/\{[\s\S]*\}/m', $raw, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
    
        // Fallback: devolve o texto raw como section3
        return [
            'section3' => $raw,
            'section5' => '',
            'section6' => '',
            'section8' => '',
        ];
    }
}