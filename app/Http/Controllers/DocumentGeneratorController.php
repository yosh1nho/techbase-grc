<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\DocumentGeneratorService;

class DocumentGeneratorController extends Controller
{
    // =========================================================================
    // GET /api/document-generator/templates
    // Lista os tipos de documento disponíveis para o select do frontend
    // =========================================================================
    public function templates(DocumentGeneratorService $svc): JsonResponse
    {
        return response()->json($svc->templates());
    }

    // =========================================================================
    // POST /api/document-generator/generate
    // Gera um documento a partir de instrução + tipo + contexto RAG
    //
    // Body:
    //   instruction  string   required  — instrução livre do utilizador
    //   doc_type     string   optional  — chave do template (password_policy, etc.)
    //   entity_name  string   optional  — nome da organização para o cabeçalho
    // =========================================================================
    public function generate(Request $request, DocumentGeneratorService $svc): JsonResponse
    {
        $data = $request->validate([
            'instruction' => ['required', 'string', 'max:4000'],
            'doc_type'    => ['nullable', 'string', 'max:100'],
            'entity_name' => ['nullable', 'string', 'max:255'],
        ]);

        // Tenant do mock auth (mesma lógica do ChatController)
        $tbUser   = session('tb_user', []);
        $tenantId = (string) ($tbUser['tenant'] ?? $tbUser['tenant_id'] ?? '102');

        try {
            $result = $svc->generate(
                instruction: $data['instruction'],
                tenantId:    $tenantId,
                docType:     $data['doc_type']    ?? 'custom',
                entityName:  $data['entity_name'] ?? null,
            );

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('DocumentGenerator falhou', [
                'error'    => $e->getMessage(),
                'tenantId' => $tenantId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar documento: ' . $e->getMessage(),
            ], 500);
        }
    }
}
