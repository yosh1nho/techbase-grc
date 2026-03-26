<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ComplianceController extends Controller
{
    private const STATUSES = ['compliant', 'partial', 'non_compliant'];

    // =========================================================================
    // GET /api/compliance
    // Devolve todos os frameworks com os seus grupos e controlos,
    // incluindo o assessment mais recente de cada controlo.
    //
    // Query params:
    //   ?framework=NIS2|QNRCS          → filtrar por framework (nome)
    //   ?framework_id=1                → filtrar por id
    //   ?group=ID.GA                   → filtrar por código de grupo
    //   ?status=compliant|partial|...  → filtrar controlos por status
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        // ── 1. Buscar frameworks (com filtro opcional) ────────────────────────
        $fwQuery = DB::table('framework as f')
            ->select('f.id_framework', 'f.name', 'f.version', 'f.source_url');

        if ($request->filled('framework')) {
            $fwQuery->where('f.name', $request->framework);
        }
        if ($request->filled('framework_id')) {
            $fwQuery->where('f.id_framework', $request->framework_id);
        }

        $frameworks = $fwQuery->orderBy('f.name')->get();

        if ($frameworks->isEmpty()) {
            return response()->json([]);
        }

        $frameworkIds = $frameworks->pluck('id_framework')->toArray();

        // ── 2. Buscar todos os grupos dos frameworks filtrados ─────────────────
        $groupQuery = DB::table('framework_group as fg')
            ->whereIn('fg.id_framework', $frameworkIds)
            ->orderBy('fg.sort_order');

        if ($request->filled('group')) {
            $groupQuery->where('fg.code', $request->group);
        }

        $groups = $groupQuery->get();
        $groupIds = $groups->pluck('id_group')->toArray();

        if (empty($groupIds)) {
            return response()->json($frameworks->map(fn($f) => [
                'id'         => $f->id_framework,
                'name'       => $f->name,
                'version'    => $f->version,
                'source_url' => $f->source_url,
                'groups'     => [],
                'summary'    => $this->emptySummary(),
            ]));
        }

        // ── 3. Buscar todos os controlos dos grupos ────────────────────────────
        $controlQuery = DB::table('framework_control as fc')
            ->whereIn('fc.id_group', $groupIds)
            ->orderBy('fc.sort_order');

        $controls = $controlQuery->get();
        $controlIds = $controls->pluck('id_control')->toArray();

        // ── 4. Assessment mais recente por controlo (DISTINCT ON) ─────────────
        // Uma única query para todos os controlos — evita N+1
        $latestAssessments = DB::table(DB::raw('(
            SELECT DISTINCT ON (ca.id_control)
                ca.id_control,
                ca.id_assessment,
                ca.status,
                ca.notes,
                ca.evidence_link,
                ca.assessed_by,
                ca.assessed_at
            FROM compliance_assessment ca
            WHERE ca.id_control = ANY(ARRAY[' . implode(',', $controlIds ?: [0]) . ']::int[])
            ORDER BY ca.id_control, ca.assessed_at DESC
        ) AS latest'))
            ->get()
            ->keyBy('id_control');

        // ── 5. Assessors (nomes dos utilizadores que avaliaram) ────────────────
        $assessorIds = $latestAssessments->pluck('assessed_by')->filter()->unique()->toArray();
        $assessors = [];
        if (!empty($assessorIds)) {
            $assessors = DB::table('User')
                ->whereIn('id_user', $assessorIds)
                ->pluck('name', 'id_user')
                ->toArray();
        }

        // ── 6. Evidências (documentos ligados) por controlo ───────────────────
        $evidences = DB::table('compliance_evidence as ce')
            ->join('document as d', 'd.id_doc', '=', 'ce.id_doc')
            ->leftJoin('attachment as a', 'a.id_attachment', '=', 'd.id_attachment')
            ->select([
                'ce.id_control',
                'd.id_doc',
                'd.title',
                'd.type',
                'd.status as doc_status',
                'a.original_name',
                'a.mime_type',
            ])
            ->whereIn('ce.id_control', $controlIds)
            ->whereNull('d.deleted_at')
            ->get()
            ->groupBy('id_control');

        // ── 7. Filtro por status (pós-join, para não complicar a query) ────────
        $filterStatus = $request->input('status');

        // ── 8. Montar estrutura hierárquica: framework → grupos → controlos ────
        $groupsByFramework = $groups->groupBy('id_framework');
        $controlsByGroup   = $controls->groupBy('id_group');

        $result = $frameworks->map(function ($fw) use (
            $groupsByFramework, $controlsByGroup,
            $latestAssessments, $assessors, $evidences, $filterStatus
        ) {
            $fwGroups = $groupsByFramework[$fw->id_framework] ?? collect();

            $groupsData = $fwGroups->map(function ($group) use (
                $controlsByGroup, $latestAssessments, $assessors, $evidences, $filterStatus
            ) {
                $groupControls = $controlsByGroup[$group->id_group] ?? collect();

                $controlsData = $groupControls->map(function ($ctrl) use (
                    $latestAssessments, $assessors, $evidences, $filterStatus
                ) {
                    $assessment = $latestAssessments[$ctrl->id_control] ?? null;
                    $status     = $assessment?->status ?? 'non_compliant';

                    // Aplicar filtro de status se pedido
                    if ($filterStatus && $status !== $filterStatus) {
                        return null;
                    }

                    $ctrlEvidences = ($evidences[$ctrl->id_control] ?? collect())
                        ->map(fn($e) => [
                            'id'        => $e->id_doc,
                            'title'     => $e->title,
                            'type'      => $e->type,
                            'status'    => $e->doc_status,
                            'file_name' => $e->original_name,
                            'mime_type' => $e->mime_type,
                        ])->values();

                    return [
                        'id'            => $ctrl->id_control,
                        'code'          => $ctrl->control_code,
                        'description'   => $ctrl->description,
                        'guidance'      => $ctrl->guidance,
                        'status'        => $status,
                        'assessment_id' => $assessment?->id_assessment,
                        'notes'         => $assessment?->notes,
                        'evidence_link' => $assessment?->evidence_link,
                        'assessed_by'   => $assessment?->assessed_by
                            ? ($assessors[$assessment->assessed_by] ?? null)
                            : null,
                        'assessed_at'   => $assessment?->assessed_at,
                        'evidences'     => $ctrlEvidences,
                    ];
                })->filter()->values(); // remover nulls do filtro de status

                $summary = $this->calcSummary($controlsData);

                return [
                    'id'         => $group->id_group,
                    'code'       => $group->code,
                    'name'       => $group->name,
                    'sort_order' => $group->sort_order,
                    'controls'   => $controlsData,
                    'summary'    => $summary,
                ];
            })->values();

            // Summary do framework (agrega todos os grupos)
            $allControls = $groupsData->flatMap(fn($g) => $g['controls']);
            $fwSummary   = $this->calcSummary($allControls);

            return [
                'id'         => $fw->id_framework,
                'name'       => $fw->name,
                'version'    => $fw->version,
                'source_url' => $fw->source_url,
                'groups'     => $groupsData,
                'summary'    => $fwSummary,
            ];
        });

        return response()->json($result);
    }

    // =========================================================================
    // GET /api/compliance/summary
    // Devolve apenas os KPIs por framework — usado pelo dashboard.
    // Muito mais leve que o index completo.
    // =========================================================================
    public function summary(): JsonResponse
    {
        $rows = DB::select('SELECT * FROM v_compliance_summary ORDER BY framework_name');

        return response()->json(array_map(fn($r) => [
            'framework_id'            => $r->id_framework,
            'framework_name'          => $r->framework_name,
            'total_controls'          => (int) $r->total_controls,
            'assessed_controls'       => (int) $r->assessed_controls,
            'compliant'               => (int) $r->compliant,
            'partial'                 => (int) $r->partial,
            'non_compliant'           => (int) $r->non_compliant,
            'compliance_pct'          => (float) $r->compliance_pct,
            'compliance_pct_weighted' => (float) $r->compliance_pct_weighted,
        ], $rows));
    }

    // =========================================================================
    // POST /api/compliance/assess
    // Cria ou actualiza a avaliação de um controlo.
    // Guarda sempre um registo de histórico para auditoria.
    //
    // Body:
    //   control_id    integer  required
    //   status        string   required (compliant|partial|non_compliant)
    //   notes         string   nullable
    //   evidence_link string   nullable
    // =========================================================================
    public function assess(Request $request): JsonResponse
    {
        $data = $request->validate([
            'control_id'    => ['required', 'integer', 'exists:framework_control,id_control'],
            'status'        => ['required', Rule::in(self::STATUSES)],
            'notes'         => ['nullable', 'string', 'max:5000'],
            'evidence_link' => ['nullable', 'url', 'max:500'],
        ]);

        $userId = session('tb_user.id') ?? null;
        $now    = now();

        // Buscar assessment actual (para histórico)
        $current = DB::table('compliance_assessment')
            ->where('id_control', $data['control_id'])
            ->orderByDesc('assessed_at')
            ->first();

        DB::beginTransaction();
        try {
            // ── Inserir novo assessment (histórico acumulativo) ────────────────
            // Modelo: cada avaliação é um novo registo — o mais recente é o válido
            // (DISTINCT ON na query do index). Assim nunca se perde histórico.
            $assessmentId = DB::table('compliance_assessment')->insertGetId([
                'id_control'    => $data['control_id'],
                'status'        => $data['status'],
                'notes'         => $data['notes'] ?? null,
                'evidence_link' => $data['evidence_link'] ?? null,
                'assessed_by'   => $userId,
                'assessed_at'   => $now,
                'updated_at'    => $now,
            ], 'id_assessment');

            // ── Guardar registo de histórico de alterações ────────────────────
            DB::table('compliance_assessment_history')->insert([
                'id_control'      => $data['control_id'],
                'previous_status' => $current?->status,
                'new_status'      => $data['status'],
                'notes'           => $data['notes'] ?? null,
                'evidence_link'   => $data['evidence_link'] ?? null,
                'changed_by'      => $userId,
                'changed_at'      => $now,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erro ao gravar assessment de compliance', [
                'control_id' => $data['control_id'],
                'error'      => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gravar avaliação.',
            ], 500);
        }

        // Buscar nome do assessor para devolver
        $assessorName = $userId
            ? DB::table('User')->where('id_user', $userId)->value('name')
            : null;

        return response()->json([
            'success'       => true,
            'assessment_id' => $assessmentId,
            'control_id'    => $data['control_id'],
            'status'        => $data['status'],
            'notes'         => $data['notes'] ?? null,
            'evidence_link' => $data['evidence_link'] ?? null,
            'assessed_by'   => $assessorName,
            'assessed_at'   => $now->toISOString(),
        ], 201);
    }

    // =========================================================================
    // GET /api/compliance/{controlId}/history
    // Devolve o histórico completo de avaliações de um controlo.
    // =========================================================================
    public function history(int $controlId): JsonResponse
    {
        $control = DB::table('framework_control')->where('id_control', $controlId)->first();
        if (!$control) {
            return response()->json(['success' => false, 'message' => 'Controlo não encontrado.'], 404);
        }

        $history = DB::table('compliance_assessment_history as h')
            ->leftJoin('User as u', 'u.id_user', '=', 'h.changed_by')
            ->select([
                'h.id_history',
                'h.previous_status',
                'h.new_status',
                'h.notes',
                'h.evidence_link',
                'h.changed_at',
                'u.name  as changed_by_name',
                'u.email as changed_by_email',
            ])
            ->where('h.id_control', $controlId)
            ->orderByDesc('h.changed_at')
            ->get()
            ->map(fn($h) => [
                'id'              => $h->id_history,
                'previous_status' => $h->previous_status,
                'new_status'      => $h->new_status,
                'notes'           => $h->notes,
                'evidence_link'   => $h->evidence_link,
                'changed_by'      => $h->changed_by_name ?? $h->changed_by_email,
                'changed_at'      => $h->changed_at,
            ]);

        return response()->json([
            'control_id'   => $controlId,
            'control_code' => $control->control_code,
            'history'      => $history,
        ]);
    }

    // =========================================================================
    // POST /api/compliance/{controlId}/link-doc
    // Liga um documento existente (de evidências) a um controlo.
    //
    // Body:
    //   doc_id  integer  required
    // =========================================================================
    public function linkDoc(Request $request, int $controlId): JsonResponse
    {
        $control = DB::table('framework_control')->where('id_control', $controlId)->first();
        if (!$control) {
            return response()->json(['success' => false, 'message' => 'Controlo não encontrado.'], 404);
        }

        $data = $request->validate([
            'doc_id' => ['required', 'integer', 'exists:document,id_doc'],
        ]);

        $userId = session('tb_user.id') ?? null;

        // Verificar se já está ligado
        $alreadyLinked = DB::table('compliance_evidence')
            ->where('id_control', $controlId)
            ->where('id_doc', $data['doc_id'])
            ->exists();

        if ($alreadyLinked) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento já está associado a este controlo.',
            ], 409);
        }

        // Verificar se o documento está aprovado
        $doc = DB::table('document')
            ->leftJoin('attachment as a', 'a.id_attachment', '=', 'document.id_attachment')
            ->select(['document.id_doc', 'document.title', 'document.status', 'document.type', 'a.original_name', 'a.mime_type'])
            ->where('document.id_doc', $data['doc_id'])
            ->whereNull('document.deleted_at')
            ->first();

        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Documento não encontrado.'], 404);
        }

        DB::table('compliance_evidence')->insert([
            'id_control' => $controlId,
            'id_doc'     => $data['doc_id'],
            'linked_at'  => now(),
            'linked_by'  => $userId,
        ]);

        return response()->json([
            'success'    => true,
            'control_id' => $controlId,
            'document'   => [
                'id'        => $doc->id_doc,
                'title'     => $doc->title,
                'type'      => $doc->type,
                'status'    => $doc->status,
                'file_name' => $doc->original_name,
                'mime_type' => $doc->mime_type,
            ],
        ], 201);
    }

    // =========================================================================
    // DELETE /api/compliance/{controlId}/link-doc/{docId}
    // Remove a ligação entre um documento e um controlo.
    // =========================================================================
    public function unlinkDoc(int $controlId, int $docId): JsonResponse
    {
        $deleted = DB::table('compliance_evidence')
            ->where('id_control', $controlId)
            ->where('id_doc', $docId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Ligação não encontrada.',
            ], 404);
        }

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // GET /api/compliance/{controlId}/evidences
    // Lista todos os documentos ligados a um controlo específico.
    // =========================================================================
    public function evidences(int $controlId): JsonResponse
    {
        $control = DB::table('framework_control')->where('id_control', $controlId)->first();
        if (!$control) {
            return response()->json(['success' => false, 'message' => 'Controlo não encontrado.'], 404);
        }

        $docs = DB::table('compliance_evidence as ce')
            ->join('document as d', 'd.id_doc', '=', 'ce.id_doc')
            ->leftJoin('attachment as a', 'a.id_attachment', '=', 'd.id_attachment')
            ->leftJoin('User as u', 'u.id_user', '=', 'ce.linked_by')
            ->select([
                'd.id_doc',
                'd.title',
                'd.type',
                'd.status as doc_status',
                'd.version',
                'd.approved_at',
                'a.original_name',
                'a.mime_type',
                'a.file_size',
                'ce.linked_at',
                'u.name as linked_by_name',
            ])
            ->where('ce.id_control', $controlId)
            ->whereNull('d.deleted_at')
            ->orderByDesc('ce.linked_at')
            ->get()
            ->map(fn($e) => [
                'id'           => $e->id_doc,
                'title'        => $e->title,
                'type'         => $e->type,
                'status'       => $e->doc_status,
                'version'      => $e->version,
                'file_name'    => $e->original_name,
                'mime_type'    => $e->mime_type,
                'file_size'    => $e->file_size,
                'approved_at'  => $e->approved_at,
                'linked_at'    => $e->linked_at,
                'linked_by'    => $e->linked_by_name,
            ]);

        return response()->json($docs);
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    /**
     * Calcula o summary de conformidade a partir de uma colecção de controlos.
     * Usado tanto ao nível do grupo como do framework.
     */
    private function calcSummary($controls): array
    {
        $total        = count($controls);
        $compliant    = collect($controls)->where('status', 'compliant')->count();
        $partial      = collect($controls)->where('status', 'partial')->count();
        $nonCompliant = collect($controls)->where('status', 'non_compliant')->count();
        $notAssessed  = collect($controls)->whereNull('assessment_id')->count();

        return [
            'total'          => $total,
            'compliant'      => $compliant,
            'partial'        => $partial,
            'non_compliant'  => $nonCompliant,
            'not_assessed'   => $notAssessed,
            // Percentagem estrita: só compliant conta
            'pct'            => $total > 0
                ? round(($compliant / $total) * 100, 1)
                : 0,
            // Percentagem ponderada: partial conta 0.5
            'pct_weighted'   => $total > 0
                ? round((($compliant + $partial * 0.5) / $total) * 100, 1)
                : 0,
        ];
    }

    private function emptySummary(): array
    {
        return [
            'total' => 0, 'compliant' => 0, 'partial' => 0,
            'non_compliant' => 0, 'not_assessed' => 0,
            'pct' => 0, 'pct_weighted' => 0,
        ];
    }
}