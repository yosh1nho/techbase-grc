<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\MemPalaceClient;

class TreatmentCommentController extends Controller
{
    // MIME types permitidos para upload
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/webp',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'text/csv',
    ];

    // =========================================================================
    // GET /api/tasks/{taskId}/comments
    // Devolve todos os comentários de uma tarefa com os seus anexos.
    // =========================================================================
    public function index(int $taskId): JsonResponse
    {
        $task = DB::table('treatmenttask')->where('id_task', $taskId)->first();
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Tarefa não encontrada.'], 404);
        }

        try {
            $comments = DB::table('treatmentcomment as tc')
                ->leftJoin('User as u', 'u.id_user', '=', 'tc.user_id')
                ->select([
                    'tc.id_comment',
                    'tc.id_task',
                    'tc.content',
                    'tc.createdat',
                    'u.name  as author_name',
                    'u.email as author_email',
                ])
                ->where('tc.id_task', $taskId)
                ->whereNull('tc.deleted_at')
                ->orderBy('tc.createdat')
                ->get();
        } catch (\Exception $e) {
            // Coluna deleted_at ainda não existe na BD — query sem o filtro
            $comments = DB::table('treatmentcomment as tc')
                ->leftJoin('User as u', 'u.id_user', '=', 'tc.user_id')
                ->select([
                    'tc.id_comment',
                    'tc.id_task',
                    'tc.content',
                    'tc.createdat',
                    'u.name  as author_name',
                    'u.email as author_email',
                ])
                ->where('tc.id_task', $taskId)
                ->orderBy('tc.createdat')
                ->get();
        }

        if ($comments->isEmpty()) {
            return response()->json([]);
        }

        // Buscar todos os anexos dos comentários desta tarefa — uma única query
        $commentIds = $comments->pluck('id_comment')->toArray();

        try {
            $attachments = DB::table('commentattachment as ca')
                ->join('attachment as a', 'a.id_attachment', '=', 'ca.id_attachment')
                ->select([
                    'ca.id_comment',
                    'a.id_attachment',
                    'a.original_name',
                    'a.file_name',
                    'a.mime_type',
                    'a.file_size',
                    'a.created_at',
                ])
                ->whereIn('ca.id_comment', $commentIds)
                ->whereNull('a.deleted_at')
                ->get()
                ->groupBy('id_comment');
        } catch (\Exception $e) {
            // Coluna deleted_at ainda não existe em attachment
            $attachments = DB::table('commentattachment as ca')
                ->join('attachment as a', 'a.id_attachment', '=', 'ca.id_attachment')
                ->select([
                    'ca.id_comment',
                    'a.id_attachment',
                    'a.original_name',
                    'a.file_name',
                    'a.mime_type',
                    'a.file_size',
                    'a.created_at',
                ])
                ->whereIn('ca.id_comment', $commentIds)
                ->get()
                ->groupBy('id_comment');
        }

        // Verificar quais anexos já foram promovidos a documento
        $promotedAttachmentIds = DB::table('document')
            ->whereNotNull('id_attachment')
            ->pluck('id_attachment')
            ->flip(); // virar para lookup O(1)

        $result = $comments->map(function ($c) use ($attachments, $promotedAttachmentIds) {
            $commentAttachments = ($attachments[$c->id_comment] ?? collect())->map(function ($a) use ($promotedAttachmentIds) {
                return [
                    'id'         => $a->id_attachment,
                    'name'       => $a->original_name ?? $a->file_name,
                    'mime'       => $a->mime_type,
                    'size'       => $a->file_size,
                    'sentToDocs' => isset($promotedAttachmentIds[$a->id_attachment]),
                ];
            })->values();

            return [
                'id'          => $c->id_comment,
                'taskId'      => $c->id_task,
                'author'      => $c->author_name ?? $c->author_email ?? 'Utilizador',
                'content'     => $c->content,
                'attachments' => $commentAttachments,
                'createdAt'   => $c->createdat,
            ];
        });

        return response()->json($result);
    }

// =========================================================================
    // POST /api/tasks/{taskId}/comments
    // Cria um comentário para a tarefa (e guarda no MemPalace)
    // =========================================================================
    public function store(Request $request, int $taskId): JsonResponse
    {
        $task = DB::table('treatmenttask')->where('id_task', $taskId)->first();
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Tarefa não encontrada.'], 404);
        }

        $request->validate([
            'content' => ['required', 'string', 'max:2000']
        ]);

        $userId = session('tb_user.id');
        $now = now();

        $commentId = DB::table('treatmentcomment')->insertGetId([
            'id_task'   => $taskId,
            'user_id'   => $userId,
            'content'   => $request->content,
            'createdat' => $now
        ], 'id_comment');

        // =====================================================================
        // 🧠 MINE MEMPALACE — grava comentário no diário SOC
        // =====================================================================
        \Log::channel('single')->info("🔥 [DEBUG MEMPALACE] A iniciar gravação do comentário #{$commentId}");
        
        try {
            $plan = DB::table('risktreatmentplan')->where('id_plan', $task->id_plan)->first();
            
            $assets = DB::table('asset')->get();
            $tags = [];
            foreach ($assets as $asset) {
                // 👇 ALTERADO AQUI: Agora procura na description do plano em vez de no title 👇
                if (stripos($request->content, $asset->hostname) !== false || 
                    stripos($task->title, $asset->hostname) !== false ||
                    ($plan && stripos($plan->description ?? '', $asset->hostname) !== false)) {
                    $tags[] = "[ASSET_ID: {$asset->id_asset}] [HOSTNAME: {$asset->hostname}]";
                }
            }
            
            $tagString = empty($tags) ? "[GERAL_SOC]" : implode(" ", $tags);
            $userName = DB::table('User')->where('id_user', $userId)->value('name') ?? 'Analista SOC';
            
            // 👇 ALTERADO AQUI: O plano agora chama-se pelo seu ID 👇
            $planTitle = $plan ? "TP-" . $plan->id_plan : 'Sem plano'; 
            
            $textoParaGravar = "{$tagString} | DATA: " . $now->format('Y-m-d H:i') . " | "
                             . "{$userName} comentou no plano '{$planTitle}': " . strip_tags($request->content);

            \Log::channel('single')->info("🚀 [DEBUG MEMPALACE] Texto montado. A instanciar cliente e enviar para o Python API...");
            
            $memPalace = new \App\Services\MemPalaceClient();
            $memPalace->remember("comment-{$commentId}-" . time(), $textoParaGravar);
            
            \Log::channel('single')->info("✅ [DEBUG MEMPALACE] Sucesso! A API Python aceitou a memória.");
            
        } catch (\Exception $e) {
            \Log::channel('single')->error("❌ [DEBUG MEMPALACE ERRO FATAL]: " . $e->getMessage() . " | Linha: " . $e->getLine());
        }        
        // =====================================================================

        // Retornar o comentário recém-criado para a UI (igual ao teu código original)
        $newComment = DB::table('treatmentcomment as tc')
            ->leftJoin('User as u', 'u.id_user', '=', 'tc.user_id')
            ->select([
                'tc.id_comment',
                'tc.id_task',
                'tc.content',
                'tc.createdat',
                'u.name as user_name',
                'u.email as user_email'
            ])
            ->where('tc.id_comment', $commentId)
            ->first();

        // Garantir que a UI não quebra
        $newComment->attachments = [];

        return response()->json(['success' => true, 'comment' => $newComment]);
    }

    // =========================================================================
    // DELETE /api/tasks/{taskId}/comments/{commentId}
    // Soft delete do comentário (mantém para auditoria).
    // =========================================================================
    public function destroy(int $taskId, int $commentId): JsonResponse
    {
        $comment = DB::table('treatmentcomment')
            ->where('id_comment', $commentId)
            ->where('id_task', $taskId)
            ->first();

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Comentário não encontrado.'], 404);
        }

        try {
            DB::table('treatmentcomment')
                ->where('id_comment', $commentId)
                ->update(['deleted_at' => now()]);
        } catch (\Exception $e) {
            DB::table('treatmentcomment')->where('id_comment', $commentId)->delete();
        }

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // DELETE /api/attachments/{attachmentId}
    // Soft delete de um anexo individual (não apaga o ficheiro do disco).
    // =========================================================================
    public function destroyAttachment(int $attachmentId): JsonResponse
    {
        $attachment = DB::table('attachment')
            ->where('id_attachment', $attachmentId)
            ->whereNull('deleted_at')
            ->first();

        if (!$attachment) {
            return response()->json(['success' => false, 'message' => 'Anexo não encontrado.'], 404);
        }

        // Não permitir apagar se já foi promovido a documento
        $isPromoted = DB::table('document')
            ->where('id_attachment', $attachmentId)
            ->exists();

        if ($isPromoted) {
            return response()->json([
                'success' => false,
                'message' => 'Este anexo já foi promovido a documento e não pode ser eliminado.',
            ], 409);
        }

        try {
            DB::table('attachment')
                ->where('id_attachment', $attachmentId)
                ->update(['deleted_at' => now()]);
        } catch (\Exception $e) {
            DB::table('attachment')->where('id_attachment', $attachmentId)->delete();
        }

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // GET /api/attachments/{attachmentId}/download
    // Serve o ficheiro para download — passa pelo controller para validar acesso.
    // =========================================================================
    public function download(int $attachmentId)
    {
        $attachment = DB::table('attachment')
            ->where('id_attachment', $attachmentId)
            ->whereNull('deleted_at')
            ->first();

        if (!$attachment) {
            abort(404, 'Anexo não encontrado.');
        }

        // Usar stored_name se existir, senão file_path legacy
        $path = $attachment->file_path;

        if (!Storage::disk('attachments')->exists($path)) {
            abort(404, 'Ficheiro não encontrado no servidor.');
        }

        $displayName = $attachment->original_name ?? $attachment->file_name ?? 'download';

        return Storage::disk('attachments')->download($path, $displayName);
    }

    // =========================================================================
    // POST /api/attachments/{attachmentId}/promote
    // Promove um anexo a documento pendente de aprovação.
    // =========================================================================
    public function promote(Request $request, int $attachmentId): JsonResponse
    {
        $attachment = DB::table('attachment')
            ->where('id_attachment', $attachmentId)
            ->whereNull('deleted_at')
            ->first();

        if (!$attachment) {
            return response()->json(['success' => false, 'message' => 'Anexo não encontrado.'], 404);
        }

        // Verificar promoção dupla
        $alreadyPromoted = DB::table('document')
            ->where('id_attachment', $attachmentId)
            ->exists();

        if ($alreadyPromoted) {
            return response()->json([
                'success' => false,
                'message' => 'Este anexo já foi enviado para documentos.',
            ], 409);
        }

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'type'  => ['nullable', 'string', 'max:100'],
        ]);

        $userId = session('tb_user.id') ?? null;

        // Buscar o id_comment para rastreabilidade
        $commentAttachment = DB::table('commentattachment')
            ->where('id_attachment', $attachmentId)
            ->first();

        $docId = DB::table('document')->insertGetId([
            'id_attachment'           => $attachmentId,
            'promoted_from_comment'   => $commentAttachment?->id_comment,
            'title'                   => $request->input('title', $attachment->original_name ?? $attachment->file_name),
            'type'                    => $request->input('type', 'evidence'),
            'status'                  => 'pending',
            'version'                 => '1.0',
            'file_path'               => $attachment->file_path, // mantido por compatibilidade
            'uploaded_by'             => $userId,
            'created_at'              => now(),
        ], 'id_doc');

        return response()->json([
            'success' => true,
            'doc_id'  => $docId,
            'message' => 'Ficheiro enviado para aprovação.',
        ], 201);
    }

    // =========================================================================
    // Helper privado — validar e guardar um ficheiro no disco
    // =========================================================================
    private function saveAttachment($file, int $commentId, ?int $userId): ?array
    {
        // Validação por extensão — fallback para quando php_fileinfo não está activa.
        // Quando activares a extensão, substitui por $file->getMimeType()
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['pdf','png','jpg','jpeg','webp','xlsx','xls','docx','doc','txt','csv'];
        if (!in_array($extension, $allowedExtensions)) {
            return null;
        }
        // MIME derivado da extensão para guardar na BD
        $mimeMap = [
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls'  => 'application/vnd.ms-excel',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc'  => 'application/msword',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
        ];
        $realMime = $mimeMap[$extension] ?? 'application/octet-stream';

        if (!in_array($realMime, self::ALLOWED_MIMES)) {
            return null; // ficheiro ignorado silenciosamente (validação já feita no validate())
        }

        $originalName = $file->getClientOriginalName();
        $extension    = $file->getClientOriginalExtension();
        $storedName   = Str::uuid() . ($extension ? ".{$extension}" : '');
        $yearMonth    = now()->format('Y/m');

        // Guardar em storage/app/private/attachments/YYYY/MM/
        $path = $file->storeAs($yearMonth, $storedName, 'attachments');

        if (!$path) return null;

        // Calcular SHA-256 para integridade (crítico para evidências GRC)
        $sha256 = hash_file('sha256', $file->getRealPath());

        // Inserir na tabela attachment
        // Usa array_filter para não inserir colunas que possam não existir ainda na BD
        $attachmentData = array_filter([
            'file_name'     => $originalName,       // coluna original sempre existe
            'original_name' => $originalName,       // nova coluna (migration pendente)
            'stored_name'   => $storedName,
            'file_path'     => $path,
            'mime_type'     => $realMime,
            'file_size'     => $file->getSize(),
            'sha256'        => $sha256,
            'uploaded_by'   => $userId,
            'created_at'    => now(),
        ], fn($v) => $v !== null);

        try {
            $attachmentId = DB::table('attachment')->insertGetId($attachmentData, 'id_attachment');
        } catch (\Exception $e) {
            // Fallback: inserir apenas as colunas que existem na BD actual
            $attachmentId = DB::table('attachment')->insertGetId([
                'file_name'  => $originalName,
                'file_path'  => $path,
                'mime_type'  => $realMime,
                'created_at' => now(),
            ], 'id_attachment');
        }

        // Pivot comentário ↔ anexo
        DB::table('commentattachment')->insert([
            'id_comment'    => $commentId,
            'id_attachment' => $attachmentId,
        ]);

        return [
            'id'         => $attachmentId,
            'name'       => $originalName,
            'mime'       => $realMime,
            'size'       => $file->getSize(),
            'sentToDocs' => false,
        ];
    }
}