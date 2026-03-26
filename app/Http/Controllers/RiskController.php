<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiskController extends Controller
{
    // =========================================================================
    // Subquery reutilizável — assessment mais recente por risco
    // =========================================================================
    private function latestAssessmentSubquery(): \Illuminate\Database\Query\Expression
    {
        return DB::raw('(
            SELECT DISTINCT ON (id_risk)
                id_risk, probability, impact, score, assessedat, id_asset as assessed_asset_id
            FROM riskassessmenthistory
            ORDER BY id_risk, assessedat DESC
        ) AS la');
    }

    // =========================================================================
    // GET /api/risks
    // =========================================================================
    public function index()
    {
        $risks = DB::table('risk as r')
            ->leftJoin('asset as a',    'a.id_asset',  '=', 'r.id_asset')
            ->leftJoin('User as u',     'u.id_user',   '=', 'r.risk_owner_id')
            ->leftJoin($this->latestAssessmentSubquery(), 'la.id_risk', '=', 'r.id_risk')
            ->select([
                'r.id_risk',
                'r.id_asset',
                'r.title',
                'r.description',
                'r.status',
                'r.origin',
                'r.threat',
                'r.vulnerability',
                'r.risk_owner_id',
                'r.actions',
                'r.due',
                'r.createdat',
                'a.hostname',
                'a.display_name  as asset_name',
                'a.criticality   as asset_criticality',
                'u.id_user       as owner_id',
                'u.name          as owner_name',
                'u.email         as owner_email',
                'la.probability',
                'la.impact',
                'la.score',
            ])
            ->whereNull('r.deleted_at')
            ->orderByDesc('la.score')
            ->get()
            ->map(fn($r) => $this->formatRisk($r));

        return response()->json($risks);
    }

    // =========================================================================
    // GET /api/risks/{id}
    // =========================================================================
    public function show($id)
    {
        $risk = DB::table('risk as r')
            ->leftJoin('asset as a',  'a.id_asset', '=', 'r.id_asset')
            ->leftJoin('User as u',   'u.id_user',  '=', 'r.risk_owner_id')
            ->leftJoin($this->latestAssessmentSubquery(), 'la.id_risk', '=', 'r.id_risk')
            ->where('r.id_risk', $id)
            ->select([
                'r.id_risk', 'r.id_asset', 'r.title', 'r.description',
                'r.threat', 'r.vulnerability', 'r.risk_owner_id', 'r.actions',
                'r.due', 'r.status', 'r.origin', 'r.createdat',
                'a.hostname', 'a.display_name as asset_name', 'a.criticality as asset_criticality',
                'u.id_user as owner_id', 'u.name as owner_name', 'u.email as owner_email',
                'la.probability', 'la.impact', 'la.score',
            ])
            ->first();

        if (!$risk) {
            return response()->json(['error' => 'Risk not found'], 404);
        }

        return response()->json($this->formatRisk($risk));
    }

    // =========================================================================
    // POST /api/risks
    // Cria risco a partir de um asset (id_asset obrigatório).
    // O asset pode ser passado por id_asset OU por hostname (legacy).
    // =========================================================================
    public function store(Request $request)
    {
        $data = $request->all();

        // Resolver o asset — aceita id_asset (novo) ou hostname (legacy)
        if (!empty($data['id_asset'])) {
            $asset = DB::table('asset')->where('id_asset', $data['id_asset'])->first();
        } elseif (!empty($data['asset'])) {
            // Legacy: o frontend antigo enviava o hostname em 'asset'
            $asset = DB::table('asset')->where('hostname', $data['asset'])->first();
        } else {
            return response()->json(['error' => 'Asset obrigatório (id_asset ou hostname).'], 422);
        }

        if (!$asset) {
            return response()->json(['error' => 'Asset não encontrado.'], 404);
        }

        // Resolver risk_owner — aceita id numérico (novo) ou texto (migração)
        $ownerId = $this->resolveOwnerId($data['risk_owner'] ?? $data['risk_owner_id'] ?? null);

        DB::beginTransaction();
        try {
            $riskId = DB::table('risk')->insertGetId([
                'id_asset'       => $asset->id_asset,
                'title'          => $data['title'],
                'description'    => $data['description'],
                'threat'         => $data['threat']         ?? null,
                'vulnerability'  => $data['vulnerability']  ?? null,
                'risk_owner_id'  => $ownerId,
                'actions'        => $data['actions']        ?? null,
                'due'            => $data['due']            ?? null,
                'status'         => $data['status']         ?? 'Aberto',
                'origin'         => $data['origin']         ?? 'manual',
                'createdby'      => session('tb_user.id')   ?? 1,
            ], 'id_risk');

            $probability = (int) ($data['probability'] ?? 3);
            $impact      = (int) ($data['impact']      ?? 3);

            DB::table('riskassessmenthistory')->insert([
                'id_risk'     => $riskId,
                'id_asset'    => $asset->id_asset,  // FK directa ao asset (nova coluna)
                'probability' => $probability,
                'impact'      => $impact,
                'score'       => $probability * $impact,
                'assessed_by' => session('tb_user.id') ?? 1,
                'assessedat'  => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'id_risk' => $riskId]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PUT /api/risks/{id}
    // =========================================================================
    public function update(Request $request, $id)
    {
        $data = $request->all();

        $riskFields      = [];
        $assessmentFields = [];

        // Campos de risco — incluindo risk_owner_id (novo) e risk_owner (legacy)
        foreach (['title', 'description', 'threat', 'vulnerability', 'actions', 'due', 'status', 'origin'] as $field) {
            if (array_key_exists($field, $data)) {
                $riskFields[$field] = $data[$field];
            }
        }

        // Resolver owner
        if (array_key_exists('risk_owner_id', $data) || array_key_exists('risk_owner', $data)) {
            $riskFields['risk_owner_id'] = $this->resolveOwnerId(
                $data['risk_owner_id'] ?? $data['risk_owner'] ?? null
            );
        }

        // Assessment
        if (isset($data['probability']) || isset($data['impact'])) {
            $probability = (int) ($data['probability'] ?? 3);
            $impact      = (int) ($data['impact']      ?? 3);

            // Buscar id_asset do risco para o novo campo do histórico
            $riskAsset = DB::table('risk')->where('id_risk', $id)->value('id_asset');

            $assessmentFields = [
                'id_risk'     => $id,
                'id_asset'    => $riskAsset,
                'probability' => $probability,
                'impact'      => $impact,
                'score'       => $probability * $impact,
                'assessed_by' => session('tb_user.id') ?? 1,
                'assessedat'  => now(),
            ];
        }

        DB::beginTransaction();
        try {
            if (!empty($riskFields)) {
                DB::table('risk')->where('id_risk', $id)->update($riskFields);
            }
            if (!empty($assessmentFields)) {
                DB::table('riskassessmenthistory')->insert($assessmentFields);
            }
            DB::commit();
            return response()->json(['message' => 'Risk updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // DELETE /api/risks/{id}
    // Soft delete se a coluna existir, hard delete como fallback.
    // =========================================================================
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            try {
                DB::table('risk')->where('id_risk', $id)->update(['deleted_at' => now()]);
            } catch (\Exception) {
                // Coluna deleted_at ainda não existe — hard delete
                DB::table('riskassessmenthistory')->where('id_risk', $id)->delete();
                DB::table('risk')->where('id_risk', $id)->delete();
            }
            DB::commit();
            return response()->json(['message' => 'Risk deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    /**
     * Resolve o owner: aceita int (id_user), string numérica, ou email/nome (legacy).
     */
    private function resolveOwnerId(mixed $value): ?int
    {
        if (empty($value)) return null;

        // Já é um id numérico
        if (is_numeric($value)) return (int) $value;

        // Tentar resolver por email ou nome
        $user = DB::table('User')
            ->where('email', $value)
            ->orWhere('name', $value)
            ->first();

        return $user?->id_user;
    }

    /**
     * Formata um risco para resposta — normaliza os campos do owner e asset.
     * Mantém 'risk_owner' como string para não quebrar o JS existente.
     */
    private function formatRisk($r): array
    {
        return [
            'id_risk'           => $r->id_risk,
            'id_asset'          => $r->id_asset,
            'title'             => $r->title,
            'description'       => $r->description,
            'status'            => $r->status,
            'origin'            => $r->origin,
            'threat'            => $r->threat,
            'vulnerability'     => $r->vulnerability,
            'actions'           => $r->actions,
            'due'               => $r->due,
            'createdat'         => $r->createdat,
            // Asset
            'hostname'          => $r->hostname,
            'asset_name'        => $r->asset_name ?? $r->hostname,
            'asset_criticality' => $r->asset_criticality ?? null,
            // Owner — devolve tanto o id como o nome
            // 'risk_owner' como string mantém compatibilidade com o risks.js existente
            'risk_owner_id'     => $r->owner_id ?? null,
            'risk_owner'        => $r->owner_name ?? $r->owner_email ?? null,
            'owner_name'        => $r->owner_name ?? null,
            'owner_email'       => $r->owner_email ?? null,
            // Assessment
            'probability'       => $r->probability ?? null,
            'impact'            => $r->impact      ?? null,
            'score'             => $r->score        ?? null,
        ];
    }
}