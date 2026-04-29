<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * IncidentController
 *
 * Gere o ciclo de vida dos incidentes de segurança.
 * Endpoints expostos (prefixo /api/incidents):
 *
 *   GET    /                    → index()         lista + filtros
 *   POST   /                    → store()         criar incidente
 *   GET    /{id}                → show()          detalhe
 *   PUT    /{id}                → update()        editar (apenas open/contained)
 *   POST   /{id}/contain        → contain()       open → contained
 *   POST   /{id}/resolve        → resolve()       contained → resolved
 *   POST   /{id}/close          → close()         resolved → closed
 *   POST   /{id}/reopen         → reopen()        closed → open
 *   DELETE /{id}                → destroy()       soft delete (apenas open)
 *   POST   /{id}/reports        → createReport()  criar cncs_report ligado
 *   GET    /company-settings    → companySettings() dados da entidade
 *   PUT    /company-settings    → updateCompanySettings() editar
 */
class IncidentController extends Controller
{
    // ─────────────────────────────────────────────
    // Constantes partilhadas
    // ─────────────────────────────────────────────

    private const STATUSES = ['open', 'contained', 'resolved', 'closed'];

    private const INCIDENT_TYPES = [
        'ransomware', 'malware', 'phishing', 'ddos', 'unauthorized_access',
        'data_breach', 'service_disruption', 'backup_failure', 'other',
    ];

    private const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    private const SEVERITY_LABELS = [
        'low'      => 'Baixa',
        'medium'   => 'Média',
        'high'     => 'Alta',
        'critical' => 'Crítica',
    ];

    private const STATUS_LABELS = [
        'open'      => 'Aberto',
        'contained' => 'Contido',
        'resolved'  => 'Resolvido',
        'closed'    => 'Fechado',
    ];

    private const TYPE_LABELS = [
        'ransomware'          => 'Ransomware',
        'malware'             => 'Malware',
        'phishing'            => 'Phishing',
        'ddos'                => 'DDoS',
        'unauthorized_access' => 'Acesso indevido',
        'data_breach'         => 'Fuga de dados',
        'service_disruption'  => 'Indisponibilidade de serviço',
        'backup_failure'      => 'Falha de backup',
        'other'               => 'Outro',
    ];

    // =========================================================================
    // GET /api/incidents
    //
    // Query params:
    //   ?status=open|contained|resolved|closed
    //   ?severity=low|medium|high|critical
    //   ?type=ransomware|...
    //   ?urgent=1
    //   ?year=2026
    //   ?search=palavra
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('incident as i')
            ->leftJoin('User as u', 'u.id_user', '=', 'i.reported_by')
            ->leftJoin(
                DB::raw('(SELECT id_incident, COUNT(*) as report_count FROM cncs_report WHERE deleted_at IS NULL GROUP BY id_incident) as rc'),
                'rc.id_incident', '=', 'i.id_incident'
            )
            ->select([
                'i.id_incident',
                'i.title',
                'i.incident_type',
                'i.status',
                'i.severity',
                'i.is_urgent',
                'i.detected_at',
                'i.started_at',
                'i.resolved_at',
                'i.closed_at',
                'i.created_at',
                'u.name  as reporter_name',
                DB::raw('COALESCE(rc.report_count, 0) as report_count'),
            ])
            ->whereNull('i.deleted_at')
            ->orderByRaw("CASE i.status WHEN 'open' THEN 0 WHEN 'contained' THEN 1 WHEN 'resolved' THEN 2 ELSE 3 END")
            ->orderByDesc('i.created_at');

        if ($request->filled('status')) {
            $query->where('i.status', $request->status);
        }
        if ($request->filled('severity')) {
            $query->where('i.severity', $request->severity);
        }
        if ($request->filled('type')) {
            $query->where('i.incident_type', $request->type);
        }
        if ($request->boolean('urgent')) {
            $query->where('i.is_urgent', true);
        }
        if ($request->filled('year')) {
            $query->whereYear('i.created_at', $request->year);
        }
        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $query->where(function ($qb) use ($q) {
                $qb->where('i.title', 'ilike', $q)
                   ->orWhere('i.description', 'ilike', $q)
                   ->orWhere('i.affected_systems', 'ilike', $q);
            });
        }

        $incidents = $query->get()->map(fn($i) => $this->formatIncident($i));

        // Contadores para o painel
        $counts = DB::table('incident')
            ->selectRaw("
                COUNT(*) FILTER (WHERE status = 'open'      AND deleted_at IS NULL) AS open,
                COUNT(*) FILTER (WHERE status = 'contained' AND deleted_at IS NULL) AS contained,
                COUNT(*) FILTER (WHERE status = 'resolved'  AND deleted_at IS NULL) AS resolved,
                COUNT(*) FILTER (WHERE status = 'closed'    AND deleted_at IS NULL) AS closed,
                COUNT(*) FILTER (WHERE is_urgent = true     AND deleted_at IS NULL AND status NOT IN ('closed','resolved')) AS urgent_active
            ")
            ->first();

        return response()->json([
            'data'   => $incidents,
            'counts' => [
                'open'          => (int) ($counts->open ?? 0),
                'contained'     => (int) ($counts->contained ?? 0),
                'resolved'      => (int) ($counts->resolved ?? 0),
                'closed'        => (int) ($counts->closed ?? 0),
                'urgent_active' => (int) ($counts->urgent_active ?? 0),
            ],
        ]);
    }

    // =========================================================================
    // GET /api/incidents/{id}
    // =========================================================================
    public function show(int $id): JsonResponse
    {
        $i = DB::table('incident as i')
            ->leftJoin('User as u', 'u.id_user', '=', 'i.reported_by')
            ->select(['i.*', 'u.name as reporter_name', 'u.email as reporter_email'])
            ->where('i.id_incident', $id)
            ->whereNull('i.deleted_at')
            ->first();

        if (!$i) {
            return response()->json(['success' => false, 'message' => 'Incidente não encontrado.'], 404);
        }

        // Relatórios CNCS associados
        $reports = DB::table('cncs_report as r')
            ->leftJoin('User as u', 'u.id_user', '=', 'r.reported_by')
            ->select(['r.id_report', 'r.title', 'r.status', 'r.is_urgent', 'r.created_at', 'r.submitted_at', 'u.name as reporter_name'])
            ->where('r.id_incident', $id)
            ->whereNull('r.deleted_at')
            ->orderByDesc('r.created_at')
            ->get();

        return response()->json([
            ...$this->formatIncident($i, true),
            'reports' => $reports,
        ]);
    }

    // =========================================================================
    // POST /api/incidents
    // =========================================================================
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'incident_type'     => ['nullable', Rule::in(self::INCIDENT_TYPES)],
            'severity'          => ['nullable', Rule::in(self::SEVERITIES)],
            'is_urgent'         => ['boolean'],
            'detected_at'       => ['nullable', 'date'],
            'started_at'        => ['nullable', 'date'],
            'description'       => ['nullable', 'string'],
            'affected_systems'  => ['nullable', 'string'],
            'affected_users'    => ['nullable', 'string', 'max:255'],
            'attack_vector'     => ['nullable', 'string', 'max:100'],
            'personal_data'     => ['nullable', 'string', 'max:50'],
            'cross_border'      => ['nullable', 'string', 'max:50'],
            'containment_actions' => ['nullable', 'string'],
            'recovery_actions'  => ['nullable', 'string'],
            'operational_impact'=> ['nullable', 'string'],
            'financial_impact'  => ['nullable', 'string', 'max:255'],
        ]);

        $userId = session('tb_user.id') ?? null;

        $id = DB::table('incident')->insertGetId([
            'title'               => $data['title'],
            'incident_type'       => $data['incident_type'] ?? null,
            'severity'            => $data['severity'] ?? null,
            'is_urgent'           => (bool) ($data['is_urgent'] ?? false),
            'status'              => 'open',
            'detected_at'         => $data['detected_at'] ?? null,
            'started_at'          => $data['started_at'] ?? null,
            'description'         => $data['description'] ?? null,
            'affected_systems'    => $data['affected_systems'] ?? null,
            'affected_users'      => $data['affected_users'] ?? null,
            'attack_vector'       => $data['attack_vector'] ?? null,
            'personal_data'       => $data['personal_data'] ?? null,
            'cross_border'        => $data['cross_border'] ?? null,
            'containment_actions' => $data['containment_actions'] ?? null,
            'recovery_actions'    => $data['recovery_actions'] ?? null,
            'operational_impact'  => $data['operational_impact'] ?? null,
            'financial_impact'    => $data['financial_impact'] ?? null,
            'reported_by'         => $userId,
            'created_at'          => now(),
            'updated_at'          => now(),
        ], 'id_incident');

        return response()->json(['success' => true, 'id_incident' => $id], 201);
    }

    // =========================================================================
    // PUT /api/incidents/{id}
    // Apenas incidentes open ou contained podem ser editados.
    // =========================================================================
    public function update(Request $request, int $id): JsonResponse
    {
        $incident = DB::table('incident')->where('id_incident', $id)->whereNull('deleted_at')->first();

        if (!$incident) {
            return response()->json(['success' => false, 'message' => 'Incidente não encontrado.'], 404);
        }
        if (in_array($incident->status, ['resolved', 'closed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Incidentes resolvidos ou fechados não podem ser editados. Use Reabrir primeiro.',
            ], 409);
        }

        $data = $request->validate([
            'title'               => ['sometimes', 'string', 'max:255'],
            'incident_type'       => ['sometimes', 'nullable', Rule::in(self::INCIDENT_TYPES)],
            'severity'            => ['sometimes', 'nullable', Rule::in(self::SEVERITIES)],
            'is_urgent'           => ['sometimes', 'boolean'],
            'detected_at'         => ['sometimes', 'nullable', 'date'],
            'started_at'          => ['sometimes', 'nullable', 'date'],
            'description'         => ['sometimes', 'nullable', 'string'],
            'affected_systems'    => ['sometimes', 'nullable', 'string'],
            'affected_users'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'attack_vector'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'personal_data'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'cross_border'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'containment_actions' => ['sometimes', 'nullable', 'string'],
            'recovery_actions'    => ['sometimes', 'nullable', 'string'],
            'operational_impact'  => ['sometimes', 'nullable', 'string'],
            'financial_impact'    => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'Nenhum campo para actualizar.'], 422);
        }

        DB::table('incident')->where('id_incident', $id)->update([
            ...$data,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // POST /api/incidents/{id}/contain   (open → contained)
    // =========================================================================
    public function contain(int $id): JsonResponse
    {
        return $this->transition($id, from: 'open', to: 'contained');
    }

    // =========================================================================
    // POST /api/incidents/{id}/resolve   (contained → resolved)
    // =========================================================================
    public function resolve(int $id): JsonResponse
    {
        return $this->transition($id, from: 'contained', to: 'resolved', timestampField: 'resolved_at');
    }

    // =========================================================================
    // POST /api/incidents/{id}/close     (resolved → closed)
    // =========================================================================
    public function close(int $id): JsonResponse
    {
        return $this->transition($id, from: 'resolved', to: 'closed', timestampField: 'closed_at');
    }

    // =========================================================================
    // POST /api/incidents/{id}/reopen    (closed → open)
    // =========================================================================
    public function reopen(int $id): JsonResponse
    {
        $incident = DB::table('incident')->where('id_incident', $id)->whereNull('deleted_at')->first();

        if (!$incident) {
            return response()->json(['success' => false, 'message' => 'Incidente não encontrado.'], 404);
        }
        if ($incident->status !== 'closed') {
            return response()->json(['success' => false, 'message' => 'Apenas incidentes fechados podem ser reabertos.'], 409);
        }

        DB::table('incident')->where('id_incident', $id)->update([
            'status'      => 'open',
            'closed_at'   => null,
            'resolved_at' => null,
            'updated_at'  => now(),
        ]);

        return response()->json(['success' => true, 'status' => 'open']);
    }

    // =========================================================================
    // DELETE /api/incidents/{id}   (soft delete — apenas open)
    // =========================================================================
    public function destroy(int $id): JsonResponse
    {
        $incident = DB::table('incident')->where('id_incident', $id)->whereNull('deleted_at')->first();

        if (!$incident) {
            return response()->json(['success' => false, 'message' => 'Incidente não encontrado.'], 404);
        }
        if ($incident->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas incidentes em aberto podem ser eliminados.',
            ], 409);
        }

        DB::table('incident')->where('id_incident', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // POST /api/incidents/{id}/reports
    // Cria um cncs_report ligado ao incidente, pré-preenchido com os dados
    // do incidente e das configurações da empresa.
    // =========================================================================
    public function createReport(int $id): JsonResponse
    {
        $incident = DB::table('incident')->where('id_incident', $id)->whereNull('deleted_at')->first();

        if (!$incident) {
            return response()->json(['success' => false, 'message' => 'Incidente não encontrado.'], 404);
        }

        $userId = session('tb_user.id') ?? null;

        $reportId = DB::table('cncs_report')->insertGetId([
            'id_incident'        => $id,
            'title'              => 'Notificação 24h — ' . $incident->title,
            'incident_type'      => $incident->incident_type,
            'report_description' => $incident->description,
            'is_urgent'          => $incident->is_urgent,
            'status'             => 'draft',
            'reported_by'        => $userId,
            'created_at'         => now(),
        ], 'id_report');

        return response()->json(['success' => true, 'id_report' => $reportId], 201);
    }

    // =========================================================================
    // GET /api/incidents/company-settings
    // Devolve os dados da empresa para pré-preenchimento.
    // =========================================================================
    public function companySettings(): JsonResponse
    {
        $settings = DB::table('company_settings')->first();

        if (!$settings) {
            return response()->json([]);
        }

        return response()->json([
            'entity_name'  => $settings->entity_name  ?? '',
            'nif'          => $settings->nif           ?? '',
            'address'      => $settings->address       ?? '',
            'postal_code'  => $settings->postal_code   ?? '',
            'city'         => $settings->city          ?? '',
            'sector'       => $settings->sector        ?? '',
            'entity_type'  => $settings->entity_type   ?? '',
            'ciso_name'    => $settings->ciso_name     ?? '',
            'ciso_email'   => $settings->ciso_email    ?? '',
            'ciso_phone'   => $settings->ciso_phone    ?? '',
            'signer_name'  => $settings->signer_name   ?? '',
            'signer_role'  => $settings->signer_role   ?? '',
        ]);
    }

    // =========================================================================
    // PUT /api/incidents/company-settings
    // =========================================================================
    public function updateCompanySettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_name' => ['sometimes', 'string', 'max:255'],
            'nif'         => ['sometimes', 'string', 'max:20'],
            'address'     => ['sometimes', 'nullable', 'string', 'max:500'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'city'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'sector'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'entity_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'ciso_name'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'ciso_email'  => ['sometimes', 'nullable', 'email', 'max:255'],
            'ciso_phone'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'signer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'signer_role' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'Nada a actualizar.'], 422);
        }

        $userId = session('tb_user.id') ?? null;

        $exists = DB::table('company_settings')->exists();

        if ($exists) {
            DB::table('company_settings')->update([...$data, 'updated_by' => $userId, 'updated_at' => now()]);
        } else {
            DB::table('company_settings')->insert([...$data, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Transição genérica de estado com validação de fluxo.
     */
    private function transition(
        int $id,
        string $from,
        string $to,
        ?string $timestampField = null
    ): JsonResponse {
        $incident = DB::table('incident')->where('id_incident', $id)->whereNull('deleted_at')->first();

        if (!$incident) {
            return response()->json(['success' => false, 'message' => 'Incidente não encontrado.'], 404);
        }
        if ($incident->status !== $from) {
            $fromLabel = self::STATUS_LABELS[$from] ?? $from;
            $currentLabel = self::STATUS_LABELS[$incident->status] ?? $incident->status;
            return response()->json([
                'success' => false,
                'message' => "Esta ação requer estado \"{$fromLabel}\". Estado atual: \"{$currentLabel}\".",
            ], 409);
        }

        $updates = ['status' => $to, 'updated_at' => now()];
        if ($timestampField) {
            $updates[$timestampField] = now();
        }

        DB::table('incident')->where('id_incident', $id)->update($updates);

        return response()->json(['success' => true, 'status' => $to]);
    }

    /**
     * Formata um registo de incidente para a resposta JSON.
     */
    private function formatIncident(object $i, bool $full = false): array
    {
        $durationDays = null;
        if ($i->detected_at) {
            $end = $i->resolved_at ?? $i->closed_at ?? now()->toDateTimeString();
            $durationDays = (int) round(
                (strtotime($end) - strtotime($i->detected_at)) / 86400
            );
        }

        $base = [
            'id'            => $i->id_incident,
            'title'         => $i->title,
            'incident_type' => $i->incident_type,
            'type_label'    => self::TYPE_LABELS[$i->incident_type] ?? ($i->incident_type ?? '—'),
            'status'        => $i->status,
            'status_label'  => self::STATUS_LABELS[$i->status] ?? $i->status,
            'severity'      => $i->severity,
            'severity_label'=> self::SEVERITY_LABELS[$i->severity] ?? ($i->severity ?? '—'),
            'is_urgent'     => (bool) $i->is_urgent,
            'detected_at'   => $i->detected_at,
            'started_at'    => $i->started_at  ?? null,
            'resolved_at'   => $i->resolved_at ?? null,
            'closed_at'     => $i->closed_at   ?? null,
            'created_at'    => $i->created_at,
            'duration_days' => $durationDays,
            'reporter'      => $i->reporter_name ?? null,
            'report_count'  => (int) ($i->report_count ?? 0),
        ];

        if ($full) {
            $base = array_merge($base, [
                'description'         => $i->description         ?? null,
                'affected_systems'    => $i->affected_systems    ?? null,
                'affected_users'      => $i->affected_users      ?? null,
                'attack_vector'       => $i->attack_vector       ?? null,
                'personal_data'       => $i->personal_data       ?? null,
                'cross_border'        => $i->cross_border        ?? null,
                'containment_actions' => $i->containment_actions ?? null,
                'recovery_actions'    => $i->recovery_actions    ?? null,
                'operational_impact'  => $i->operational_impact  ?? null,
                'financial_impact'    => $i->financial_impact    ?? null,
                'reporter_email'      => $i->reporter_email      ?? null,
            ]);
        }

        return $base;
    }
}
