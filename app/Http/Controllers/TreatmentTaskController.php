<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\MemPalaceClient;

class TreatmentTaskController extends Controller
{
    private const STATUSES = ['To do', 'Em curso', 'Concluído', 'Em atraso'];

    // =========================================================================
    // GET /api/treatment-plans/{planId}/tasks
    // Devolve todas as tarefas de um plano, com dados do assignee e contagem
    // de comentários e anexos (para os chips da task-row no frontend).
    // =========================================================================
    public function index(int $planId): JsonResponse
    {
        // Garantir que o plano existe
        $plan = DB::table('risktreatmentplan')->where('id_plan', $planId)->first();
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plano não encontrado.'], 404);
        }

        $tasks = DB::table('treatmenttask as t')
            ->leftJoin('User as u', 'u.id_user', '=', 't.assigned_to')
            ->select([
                't.id_task',
                't.id_plan',
                't.title',
                't.description',
                't.status',
                't.due_date',
                't.assigned_to',
                't.created_by',
                't.created_at',
                'u.name  as assigned_name',
                'u.email as assigned_email',
            ])
            ->where('t.id_plan', $planId)
            ->whereNull('t.deleted_at')
            ->orderBy('t.created_at')
            ->get();

        if ($tasks->isEmpty()) {
            return response()->json([]);
        }

        // Agregar comentários e anexos numa única query cada — evita N+1
        $taskIds = $tasks->pluck('id_task')->toArray();

        // Contagem de comentários por tarefa
        $commentCounts = DB::table('treatmentcomment')
            ->selectRaw('id_task, COUNT(*) as total')
            ->whereIn('id_task', $taskIds)
            ->whereNull('deleted_at')
            ->groupBy('id_task')
            ->pluck('total', 'id_task');

        // Contagem de anexos por tarefa (via comentários)
        $attachCounts = DB::table('treatmentcomment as tc')
            ->join('commentattachment as ca', 'ca.id_comment', '=', 'tc.id_comment')
            ->selectRaw('tc.id_task, COUNT(*) as total')
            ->whereIn('tc.id_task', $taskIds)
            ->groupBy('tc.id_task')
            ->pluck('total', 'id_task');

        $result = $tasks->map(fn($t) => $this->formatTask($t, $commentCounts, $attachCounts));

        return response()->json($result);
    }

    // =========================================================================
    // POST /api/treatment-plans/{planId}/tasks
    // Cria uma nova tarefa dentro de um plano.
    // =========================================================================
    public function store(Request $request, int $planId): JsonResponse
    {
        $plan = DB::table('risktreatmentplan')->where('id_plan', $planId)->first();
        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plano não encontrado.'], 404);
        }

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', Rule::in(self::STATUSES)],
            'assigned_to' => ['nullable', 'integer', 'exists:User,id_user'],
            'due_date'    => ['nullable', 'date'],
        ]);

        // O utilizador em sessão é o criador
        $createdBy = session('tb_user.id') ?? null;

        $id = DB::table('treatmenttask')->insertGetId([
            'id_plan'     => $planId,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'To do',
            'assigned_to' => $data['assigned_to'] ?? null,
            'due_date'    => $data['due_date'] ?? null,
            'created_by'  => $createdBy,
            'created_at'  => now(),
        ], 'id_task');

        $task = $this->findTaskWithDetails($id);

        return response()->json(['success' => true, 'task' => $task], 201);
    }

    // =========================================================================
    // PUT /api/treatment-plans/{planId}/tasks/{taskId}
    // Actualiza uma tarefa — suporta update parcial (PATCH semântico via PUT).
    // Usado tanto para editar meta como para mudar status.
    // =========================================================================
    public function update(Request $request, int $planId, int $taskId): JsonResponse
    {
        $task = DB::table('treatmenttask')
            ->where('id_task', $taskId)
            ->where('id_plan', $planId)
            ->whereNull('deleted_at')
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Tarefa não encontrada.'], 404);
        }

        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status'      => ['sometimes', Rule::in(self::STATUSES)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:User,id_user'],
            'due_date'    => ['sometimes', 'nullable', 'date'],
        ]);

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'Nenhum campo para actualizar.'], 422);
        }

        // Mapear para colunas da BD
        $updates = array_filter([
            'title'       => $data['title']       ?? null,
            'description' => $data['description'] ?? null,
            'status'      => $data['status']      ?? null,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : null,
            'due_date'    => array_key_exists('due_date', $data)    ? $data['due_date']    : null,
        ], fn($v) => $v !== null);

        // 1. Atualizar a base de dados relacional
        DB::table('treatmenttask')->where('id_task', $taskId)->update($updates);

        // =====================================================================
        // 🧠 2. MINE MEMPALACE — grava quando tarefa é concluída
        // =====================================================================
if (isset($updates['status']) && in_array($updates['status'], ['Concluído', 'Concluida', 'Done'])) {
            try {
                $updatedTask = DB::table('treatmenttask')->where('id_task', $taskId)->first();
                $plan = DB::table('risktreatmentplan')->where('id_plan', $planId)->first(); 
                
                // 🎯 Dynamic Tagging
                $assets = DB::table('asset')->get();
                $tags = [];
                foreach ($assets as $asset) {
                    // 👇 ALTERADO AQUI TAMBÉM 👇
                    if (stripos($updatedTask->title, $asset->hostname) !== false || 
                        stripos($updatedTask->description ?? '', $asset->hostname) !== false ||
                        ($plan && stripos($plan->description ?? '', $asset->hostname) !== false)) {
                        $tags[] = "[ASSET_ID: {$asset->id_asset}] [HOSTNAME: {$asset->hostname}]";
                    }
                }
                
                $tagString = empty($tags) ? "[GERAL_SOC]" : implode(" ", $tags);
                $userName = DB::table('User')->where('id_user', session('tb_user.id'))->value('name') ?? 'Analista SOC';
                
                // 👇 ALTERADO AQUI TAMBÉM 👇
                $planTitle = $plan ? "TP-" . $plan->id_plan : 'Sem plano';
                
                $textoParaGravar = "{$tagString} | DATA DO TRATAMENTO: " . now()->format('Y-m-d') . " | "
                                 . "Tarefa Concluída por {$userName}: '{$updatedTask->title}'. "
                                 . "Detalhes: " . strip_tags($updatedTask->description ?? 'Sem descrição') . ". "
                                 . "Plano Pai: '{$planTitle}'. Ação de mitigação aplicada com sucesso.";

                $memPalace = new \App\Services\MemPalaceClient();
                $memPalace->remember("task-{$taskId}-" . time(), $textoParaGravar);
                
                \Log::info("✅ [MemPalace] Tarefa guardada com sucesso: " . $textoParaGravar);

            } catch (\Exception $e) {
                \Log::error("❌ [MemPalace ERRO Task]: " . $e->getMessage());
            }
        }
        // =====================================================================

        $updated = $this->findTaskWithDetails($taskId);

        return response()->json(['success' => true, 'task' => $updated]);
    }

    // =========================================================================
    // DELETE /api/treatment-plans/{planId}/tasks/{taskId}
    // Soft delete da tarefa. Não apaga comentários — ficam auditáveis.
    // =========================================================================
    public function destroy(int $planId, int $taskId): JsonResponse
    {
        $task = DB::table('treatmenttask')
            ->where('id_task', $taskId)
            ->where('id_plan', $planId)
            ->whereNull('deleted_at')
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Tarefa não encontrada.'], 404);
        }

        // Soft delete com fallback para hard delete enquanto a coluna não existir
        try {
            DB::table('treatmenttask')
                ->where('id_task', $taskId)
                ->update(['deleted_at' => now()]);
        } catch (\Exception $e) {
            DB::table('treatmenttask')->where('id_task', $taskId)->delete();
        }

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // Helper — buscar uma tarefa individual com join ao user
    // =========================================================================
    private function findTaskWithDetails(int $taskId): ?array
    {
        $t = DB::table('treatmenttask as t')
            ->leftJoin('User as u', 'u.id_user', '=', 't.assigned_to')
            ->select([
                't.id_task', 't.id_plan', 't.title', 't.description',
                't.status', 't.due_date', 't.assigned_to', 't.created_by', 't.created_at',
                'u.name as assigned_name', 'u.email as assigned_email',
            ])
            ->where('t.id_task', $taskId)
            ->first();

        if (!$t) return null;

        $commentCount = DB::table('treatmentcomment')
            ->where('id_task', $taskId)
            ->whereNull('deleted_at')
            ->count();

        $attachCount = DB::table('treatmentcomment as tc')
            ->join('commentattachment as ca', 'ca.id_comment', '=', 'tc.id_comment')
            ->where('tc.id_task', $taskId)
            ->count();

        return $this->formatTask($t, collect([$taskId => $commentCount]), collect([$taskId => $attachCount]));
    }

    // =========================================================================
    // Helper — formatar objecto de tarefa para o frontend
    // Mantém os mesmos nomes de campo que o mock do localStorage usava,
    // para facilitar a migração do treatment.js.
    // =========================================================================
    private function formatTask($t, $commentCounts, $attachCounts): array
    {
        return [
            'id'             => $t->id_task,
            'planId'         => $t->id_plan,
            'title'          => $t->title,
            'description'    => $t->description,
            'status'         => $t->status,
            'due'            => $t->due_date,          // 'due' para compatibilidade com o JS
            'assignedTo'     => $t->assigned_name ?? $t->assigned_email, // string para o frontend
            'assigned_to_id' => $t->assigned_to,       // id numérico para updates
            'createdAt'      => $t->created_at,
            'comment_count'  => (int)($commentCounts[$t->id_task] ?? 0),
            'attach_count'   => (int)($attachCounts[$t->id_task]  ?? 0),
        ];
    }
}