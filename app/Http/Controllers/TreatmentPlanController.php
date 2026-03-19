<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TreatmentPlanController extends Controller
{
    // ─── Valores permitidos para status e strategy ───────────────────────────
    private const STATUSES  = ['To do', 'Em curso', 'Concluído', 'Em atraso'];
    private const STRATEGIES = ['Mitigar', 'Aceitar', 'Transferir', 'Evitar'];

    // =========================================================================
    // GET /api/treatment-plans
    // Devolve todos os planos com dados do risco, ativo e owner agregados.
    // Query params opcionais: ?status=Em+curso  ?risk_id=5
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('risktreatmentplan as rtp')
            // Dados do risco associado
            ->leftJoin('risk as r', 'r.id_risk', '=', 'rtp.id_risk')
            // Dados do ativo do risco
            ->leftJoin('asset as a', 'a.id_asset', '=', 'r.id_asset')
            // Nome do owner (utilizador responsável pelo plano)
            ->leftJoin('User as u', 'u.id_user', '=', 'rtp.owner_id')
            ->select([
                // Plano
                'rtp.id_plan',
                'rtp.id_risk',
                'rtp.strategy',
                'rtp.due_date',
                'rtp.status',
                'rtp.created_at',
                'rtp.owner_id',
                // Risco
                'r.title      as risk_title',
                'r.description as risk_description',
                'r.origin     as risk_origin',
                // Ativo
                'a.id_asset',
                'a.display_name as asset_name',
                'a.hostname     as asset_hostname',
                'a.type         as asset_type',
                // Owner
                'u.name  as owner_name',
                'u.email as owner_email',
            ]);

        // Filtros opcionais
        if ($request->filled('status')) {
            $query->where('rtp.status', $request->status);
        }
        if ($request->filled('risk_id')) {
            $query->where('rtp.id_risk', $request->risk_id);
        }
        if ($request->filled('owner_id')) {
            $query->where('rtp.owner_id', $request->owner_id);
        }

        $plans = $query->orderByDesc('rtp.created_at')->get();

        // Para cada plano, agregar contagem de tarefas por status
        // Evita N+1: uma única query para todos os planos
        $planIds = $plans->pluck('id_plan')->toArray();

        $taskCounts = [];
        if (!empty($planIds)) {
            $rows = DB::table('treatmenttask')
                ->selectRaw('id_plan, status, COUNT(*) as total')
                ->whereIn('id_plan', $planIds)
                ->whereNull('deleted_at')   // respeita soft delete se já existir
                ->groupBy('id_plan', 'status')
                ->get();

            foreach ($rows as $row) {
                $taskCounts[$row->id_plan][$row->status] = (int) $row->total;
            }
        }

        // Formatar resposta — enriquecer cada plano com task_summary
        $result = $plans->map(function ($plan) use ($taskCounts) {
            $counts = $taskCounts[$plan->id_plan] ?? [];
            $total  = array_sum($counts);
            $done   = $counts['Concluído'] ?? 0;

            return [
                'id'               => $plan->id_plan,
                'id_risk'          => $plan->id_risk,
                'strategy'         => $plan->strategy,
                'due_date'         => $plan->due_date,
                'status'           => $plan->status,
                'created_at'       => $plan->created_at,
                'owner_id'         => $plan->owner_id,
                'owner_name'       => $plan->owner_name,
                'owner_email'      => $plan->owner_email,
                'risk_title'       => $plan->risk_title,
                'risk_description' => $plan->risk_description,
                'risk_origin'      => $plan->risk_origin,
                'asset_id'         => $plan->id_asset,
                'asset_name'       => $plan->asset_name ?? $plan->asset_hostname,
                'asset_type'       => $plan->asset_type,
                'task_summary'     => [
                    'total'     => $total,
                    'todo'      => $counts['To do']    ?? 0,
                    'doing'     => $counts['Em curso'] ?? 0,
                    'done'      => $done,
                    'overdue'   => $counts['Em atraso'] ?? 0,
                    'progress'  => $total > 0 ? round(($done / $total) * 100) : 0,
                ],
            ];
        });

        return response()->json($result);
    }

    // =========================================================================
    // POST /api/treatment-plans
    // Cria um novo plano de tratamento.
    // =========================================================================
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'risk_id'  => ['required', 'integer', 'exists:risk,id_risk'],
            'strategy' => ['required', Rule::in(self::STRATEGIES)],
            'owner'    => ['required', 'integer', 'exists:User,id_user'],
            'due'      => ['required', 'date', 'after_or_equal:today'],
        ]);

        // Verificar que não existe já um plano activo para este risco
        // (regra de negócio GRC: um risco tem um plano activo de cada vez)
        $existing = DB::table('risktreatmentplan')
            ->where('id_risk', $data['risk_id'])
            ->whereNotIn('status', ['Concluído'])
            ->whereNull('deleted_at')
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Já existe um plano de tratamento activo para este risco.',
            ], 409);
        }

        $id = DB::table('risktreatmentplan')->insertGetId([
            'id_risk'    => $data['risk_id'],
            'strategy'   => $data['strategy'],
            'owner_id'   => $data['owner'],
            'due_date'   => $data['due'],
            'status'     => 'To do',
            'created_at' => now(),
        ], 'id_plan');

        // Devolver o plano completo (reutiliza a lógica do index para um único plano)
        $plan = $this->findPlanWithDetails($id);

        return response()->json([
            'success' => true,
            'plan'    => $plan,
        ], 201);
    }

    // =========================================================================
    // PUT /api/treatment-plans/{id}
    // Actualiza status, strategy, owner ou due_date de um plano.
    // =========================================================================
    public function update(Request $request, int $id): JsonResponse
    {
        $plan = DB::table('risktreatmentplan')->where('id_plan', $id)->first();

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plano não encontrado.'], 404);
        }

        $data = $request->validate([
            'status'   => ['sometimes', Rule::in(self::STATUSES)],
            'strategy' => ['sometimes', Rule::in(self::STRATEGIES)],
            'owner'    => ['sometimes', 'integer', 'exists:User,id_user'],
            'due'      => ['sometimes', 'date'],
        ]);

        // Mapear campos do request para colunas da BD
        $updates = [];
        if (isset($data['status']))   $updates['status']   = $data['status'];
        if (isset($data['strategy'])) $updates['strategy'] = $data['strategy'];
        if (isset($data['owner']))    $updates['owner_id'] = $data['owner'];
        if (isset($data['due']))      $updates['due_date'] = $data['due'];

        if (empty($updates)) {
            return response()->json(['success' => false, 'message' => 'Nenhum campo para actualizar.'], 422);
        }

        DB::table('risktreatmentplan')
            ->where('id_plan', $id)
            ->update($updates);

        $updated = $this->findPlanWithDetails($id);

        return response()->json([
            'success' => true,
            'plan'    => $updated,
        ]);
    }

    // =========================================================================
    // DELETE /api/treatment-plans/{id}
    // Soft delete — marca deleted_at se a coluna existir, caso contrário apaga.
    // =========================================================================
    public function destroy(int $id): JsonResponse
    {
        $plan = DB::table('risktreatmentplan')->where('id_plan', $id)->first();

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plano não encontrado.'], 404);
        }

        // Verificar se o plano tem tarefas em curso — bloquear delete nesse caso
        $activeTasks = DB::table('treatmenttask')
            ->where('id_plan', $id)
            ->where('status', 'Em curso')
            ->whereNull('deleted_at')
            ->count();

        if ($activeTasks > 0) {
            return response()->json([
                'success' => false,
                'message' => "Não é possível eliminar um plano com {$activeTasks} tarefa(s) em curso.",
            ], 409);
        }

        // Tentar soft delete (coluna deleted_at pode ainda não existir na BD)
        try {
            DB::table('risktreatmentplan')
                ->where('id_plan', $id)
                ->update(['deleted_at' => now()]);
        } catch (\Exception $e) {
            // Fallback: hard delete enquanto a migration não for aplicada
            DB::table('risktreatmentplan')->where('id_plan', $id)->delete();
        }

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // Helper privado — buscar um único plano com todos os joins
    // Reutilizado por store() e update() para devolver o objecto completo
    // =========================================================================
    private function findPlanWithDetails(int $id): ?array
    {
        $plan = DB::table('risktreatmentplan as rtp')
            ->leftJoin('risk as r',  'r.id_risk',   '=', 'rtp.id_risk')
            ->leftJoin('asset as a', 'a.id_asset',  '=', 'r.id_asset')
            ->leftJoin('User as u',  'u.id_user',   '=', 'rtp.owner_id')
            ->select([
                'rtp.id_plan', 'rtp.id_risk', 'rtp.strategy',
                'rtp.due_date', 'rtp.status', 'rtp.created_at', 'rtp.owner_id',
                'r.title as risk_title', 'r.description as risk_description', 'r.origin as risk_origin',
                'a.id_asset', 'a.display_name as asset_name', 'a.hostname as asset_hostname', 'a.type as asset_type',
                'u.name as owner_name', 'u.email as owner_email',
            ])
            ->where('rtp.id_plan', $id)
            ->first();

        if (!$plan) return null;

        $counts = DB::table('treatmenttask')
            ->selectRaw('status, COUNT(*) as total')
            ->where('id_plan', $id)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $total = array_sum($counts);
        $done  = $counts['Concluído'] ?? 0;

        return [
            'id'               => $plan->id_plan,
            'id_risk'          => $plan->id_risk,
            'strategy'         => $plan->strategy,
            'due_date'         => $plan->due_date,
            'status'           => $plan->status,
            'created_at'       => $plan->created_at,
            'owner_id'         => $plan->owner_id,
            'owner_name'       => $plan->owner_name,
            'owner_email'      => $plan->owner_email,
            'risk_title'       => $plan->risk_title,
            'risk_description' => $plan->risk_description,
            'risk_origin'      => $plan->risk_origin,
            'asset_id'         => $plan->id_asset,
            'asset_name'       => $plan->asset_name ?? $plan->asset_hostname,
            'asset_type'       => $plan->asset_type,
            'task_summary'     => [
                'total'    => $total,
                'todo'     => (int)($counts['To do']    ?? 0),
                'doing'    => (int)($counts['Em curso'] ?? 0),
                'done'     => (int)$done,
                'overdue'  => (int)($counts['Em atraso'] ?? 0),
                'progress' => $total > 0 ? round(($done / $total) * 100) : 0,
            ],
        ];
    }
}
