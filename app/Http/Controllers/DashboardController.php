<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // =========================================================================
    // GET /api/dashboard
    // KPIs principais: riscos + planos de tratamento + compliance
    // Resposta única para o dashboard carregar tudo num só fetch.
    // =========================================================================
    public function index(): JsonResponse
    {
        return response()->json([
            'risks'      => $this->riskStats(),
            'treatments' => $this->treatmentStats(),
            'compliance' => $this->complianceStats(),
        ]);
    }

    // =========================================================================
    // GET /api/dashboard/risks
    // Breakdown de riscos por score — separado para refresh independente.
    // =========================================================================
    public function risks(): JsonResponse
    {
        return response()->json($this->riskStats());
    }

    // =========================================================================
    // GET /api/dashboard/treatments
    // Breakdown de planos de tratamento por status.
    // =========================================================================
    public function treatments(): JsonResponse
    {
        return response()->json($this->treatmentStats());
    }

    // =========================================================================
    // GET /api/dashboard/compliance
    // Percentagem de conformidade NIS2 e QNRCS (usa a view v_compliance_summary).
    // =========================================================================
    public function compliance(): JsonResponse
    {
        return response()->json($this->complianceStats());
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function riskStats(): array
    {
        // Subquery para o assessment mais recente de cada risco
        $latestAssessment = DB::raw('(
            SELECT DISTINCT ON (id_risk)
                id_risk, probability, impact, score
            FROM riskassessmenthistory
            ORDER BY id_risk, assessedat DESC
        ) AS la');

        $risks = DB::table('risk as r')
            ->leftJoin($latestAssessment, 'la.id_risk', '=', 'r.id_risk')
            ->leftJoin('asset as a', 'a.id_asset', '=', 'r.id_asset')
            ->leftJoin('User as u', 'u.id_user', '=', 'r.risk_owner_id')
            ->select([
                'r.id_risk',
                'r.title',
                'r.status',
                'a.display_name as asset_name',
                'a.hostname     as asset_hostname',
                'u.name         as owner_name',
                DB::raw('COALESCE(la.score, 0) as score'),
                DB::raw('COALESCE(la.probability, 0) as probability'),
                DB::raw('COALESCE(la.impact, 0) as impact'),
            ])
            ->whereNull('r.deleted_at')
            ->get();

        $total  = $risks->count();
        $high   = $risks->filter(fn($r) => $r->score >= 17)->count();
        $medium = $risks->filter(fn($r) => $r->score >= 10 && $r->score < 17)->count();
        $low    = $risks->filter(fn($r) => $r->score < 10)->count();

        // Top risco — maior score, em caso de empate o mais recente (primeiro na query)
        $topRisk = $risks->sortByDesc('score')->first();

        return [
            'total'  => $total,
            'high'   => $high,
            'medium' => $medium,
            'low'    => $low,
            'top_risk' => $topRisk ? [
                'id'         => $topRisk->id_risk,
                'title'      => $topRisk->title,
                'score'      => (int) $topRisk->score,
                'asset'      => $topRisk->asset_name ?? $topRisk->asset_hostname,
                'owner'      => $topRisk->owner_name,
                'status'     => $topRisk->status,
            ] : null,
        ];
    }

    private function treatmentStats(): array
    {
        $plans = DB::table('risktreatmentplan')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*)                                         AS total,
                COUNT(*) FILTER (WHERE status = 'Concluído')    AS done,
                COUNT(*) FILTER (WHERE status = 'Em curso')     AS doing,
                COUNT(*) FILTER (WHERE status = 'To do')        AS todo,
                COUNT(*) FILTER (WHERE status = 'Em atraso')    AS overdue
            ")
            ->first();

        $total   = (int) $plans->total;
        $done    = (int) $plans->done;
        $doing   = (int) $plans->doing;
        $todo    = (int) $plans->todo;
        $overdue = (int) $plans->overdue;

        // Planos em atraso com mais detalhe (para o painel de próximas acções no JS)
        $overdueList = [];
        if ($overdue > 0) {
            $overdueList = DB::table('risktreatmentplan as rtp')
                ->leftJoin('risk as r', 'r.id_risk', '=', 'rtp.id_risk')
                ->select(['rtp.id_plan', 'rtp.due_date', 'r.title as risk_title'])
                ->where('rtp.status', 'Em atraso')
                ->whereNull('rtp.deleted_at')
                ->orderBy('rtp.due_date')
                ->limit(5)
                ->get()
                ->map(fn($p) => [
                    'id'         => $p->id_plan,
                    'risk_title' => $p->risk_title,
                    'due_date'   => $p->due_date,
                ])
                ->toArray();
        }

        return [
            'total'        => $total,
            'done'         => $done,
            'doing'        => $doing,
            'todo'         => $todo,
            'overdue'      => $overdue,
            'overdue_list' => $overdueList,
        ];
    }

    private function complianceStats(): array
    {
        // Usa a view v_compliance_summary que criámos na migration
        $rows = DB::select('SELECT * FROM v_compliance_summary ORDER BY framework_name');

        if (empty($rows)) {
            return [
                'frameworks' => [],
                'nis2'       => null,
                'qnrcs'      => null,
            ];
        }

        $frameworks = array_map(fn($r) => [
            'id'                      => (int) $r->id_framework,
            'name'                    => $r->framework_name,
            'total_controls'          => (int) $r->total_controls,
            'assessed_controls'       => (int) $r->assessed_controls,
            'compliant'               => (int) $r->compliant,
            'partial'                 => (int) $r->partial,
            'non_compliant'           => (int) $r->non_compliant,
            'compliance_pct'          => (float) $r->compliance_pct,
            'compliance_pct_weighted' => (float) $r->compliance_pct_weighted,
        ], $rows);

        // Atalhos directos para NIS2 e QNRCS — o JS usa estes para os mini-cards
        $byName = collect($frameworks)->keyBy('name');

        return [
            'frameworks' => $frameworks,
            'nis2'       => $byName['NIS2']  ?? null,
            'qnrcs'      => $byName['QNRCS'] ?? null,
        ];
    }
}
