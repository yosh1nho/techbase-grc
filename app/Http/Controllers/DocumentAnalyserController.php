<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\DocumentAnalyserService;

class DocumentAnalyserController extends Controller
{
    // =========================================================================
    // POST /api/documents/{id}/analyse
    // Analisa o documento e devolve sugestões de controlos cobertos.
    // Pode ser chamado após upload (automático) ou manualmente pelo utilizador.
    // =========================================================================
    public function analyse(int $id, DocumentAnalyserService $svc): JsonResponse
    {
        // Buscar documento + attachment
        $doc = DB::table('document as d')
            ->leftJoin('attachment as a', 'a.id_attachment', '=', 'd.id_attachment')
            ->select([
                'd.id_doc', 'd.title', 'd.status',
                'a.file_path as attach_path',
                'a.original_name', 'a.file_name',
                'a.mime_type',
            ])
            ->where('d.id_doc', $id)
            ->whereNull('d.deleted_at')
            ->first();

        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Documento não encontrado.'], 404);
        }

        $filePath = $doc->attach_path ?? null;
        $mime     = $doc->mime_type   ?? 'application/pdf';

        if (!$filePath || !Storage::disk('attachments')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Ficheiro não encontrado no servidor.',
            ], 404);
        }

        // Tenant do mock auth
        $tbUser   = session('tb_user', []);
        $tenantId = (string) ($tbUser['tenant'] ?? $tbUser['tenant_id'] ?? '102');

        try {
            $result = $svc->analyse(
                filePath: $filePath,
                tenantId: $tenantId,
                mimeType: $mime,
            );

            return response()->json([
                'success'     => true,
                'doc_id'      => $id,
                'suggestions' => $result['suggestions'] ?? [],
                'meta' => [
                    'text_length' => $result['text_length'] ?? 0,
                    'chunks_sent' => $result['chunks_sent'] ?? 0,
                    'total_hits'  => $result['total_hits']  ?? 0,
                    'error'       => $result['error']       ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('DocumentAnalyser falhou', [
                'doc_id' => $id,
                'error'  => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao analisar documento: ' . $e->getMessage(),
            ], 500);
        }
    }
}