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
    // Agora cruza com a BD para injetar a descrição oficial do controlo!
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
            // 1. CHAMA O PINECONE (via Serviço)
            $result = $svc->analyse(
                filePath: $filePath,
                tenantId: $tenantId,
                mimeType: $mime,
            );

            // 2. INTERCETA AS SUGESTÕES
            $suggestions = $result['suggestions'] ?? [];

            if (!empty($suggestions)) {
                // Pega em todos os control_codes devolvidos pelo Pinecone (ex: 'ID.AO-5')
                $controlCodes = array_column($suggestions, 'control_code');

                // Faz um JOIN na base de dados para buscar os textos originais
                $dbControls = DB::table('grc.framework_control as fc')
                    ->join('grc.framework_group as fg', 'fg.id_group', '=', 'fc.id_group')
                    ->join('grc.framework as f',        'f.id_framework', '=', 'fg.id_framework')
                    ->whereIn('fc.control_code', $controlCodes)
                    ->select(
                        'fc.control_code',
                        'fc.description',
                        'fc.guidance',
                        'fg.name  as group_name',
                        'f.name   as framework_name'
                    )
                    ->get()
                    ->keyBy('control_code'); // Fica indexado pelo código para ser rápido a procurar

                // Reconstrói o array de sugestões
                $suggestions = array_map(function ($hit) use ($dbControls) {
                    $code  = $hit['control_code'];
                    $dbRow = $dbControls->get($code); // Tenta encontrar na BD

                    return [
                        'control_code'   => $code,
                        'control_family' => $dbRow?->group_name   ?? $hit['control_family'] ?? null,
                        'framework'      => $dbRow?->framework_name ?? $hit['framework']    ?? null,
                        'score'          => (float) $hit['score'],
                        'coverage'       => $hit['coverage'] ?? 'low',
                        
                        // Formatação rica: Oficial + Snippet do ficheiro
                        'justification'  => $dbRow 
                            ? "Norma Oficial: " . $dbRow->description . "\n\nTrecho Encontrado no Doc: " . ($hit['top_snippet'] ?? '')
                            : ($hit['justification'] ?? $hit['top_snippet'] ?? '—')
                    ];
                }, $suggestions);
            }

            return response()->json([
                'success'     => true,
                'doc_id'      => $id,
                'suggestions' => $suggestions, // Array reconstruído!
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