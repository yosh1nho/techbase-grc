<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\IngestDocumentJob;

class DocumentController extends Controller
{
    private const INGESTABLE_EXTS = ['pdf', 'txt', 'md', 'docx'];

    // =========================================================================
    // GET /api/documents
    // Inclui agora: is_signed, signature_valid, non_compliant, sha256
    // =========================================================================
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('document as d')
            ->leftJoin('User as uploader', 'uploader.id_user', '=', 'd.uploaded_by')
            ->leftJoin('User as approver', 'approver.id_user', '=', 'd.approved_by')
            ->leftJoin('attachment as a',  'a.id_attachment',  '=', 'd.id_attachment')
            ->leftJoin('treatmentcomment as tc', 'tc.id_comment', '=', 'd.promoted_from_comment')
            ->leftJoin('treatmenttask as tt',    'tt.id_task',    '=', 'tc.id_task')
            ->leftJoin('risktreatmentplan as rtp', 'rtp.id_plan', '=', 'tt.id_plan')
            ->leftJoin('risk as r',               'r.id_risk',    '=', 'rtp.id_risk')
            ->select([
                'd.id_doc', 'd.title', 'd.type', 'd.status', 'd.version',
                'd.file_path', 'd.created_at', 'd.approved_at',
                'd.id_attachment', 'd.promoted_from_comment',
                'd.indexed_at', 'd.chunk_count', 'd.ingest_error',
                // Novos campos de assinatura e conformidade
                DB::raw("COALESCE(d.is_signed, false)      as is_signed"),
                DB::raw("d.signature_valid"),
                DB::raw("d.signature_info"),
                DB::raw("COALESCE(d.non_compliant, false)  as non_compliant"),
                DB::raw("d.rejection_reason"),
                DB::raw("d.sha256"),
                DB::raw("d.replaces_doc_id"),
                'a.file_name', 'a.original_name', 'a.mime_type', 'a.file_size',
                'a.file_path as attach_path',
                'a.sha256    as attach_sha256',
                'uploader.name  as uploader_name',
                'uploader.email as uploader_email',
                'approver.name  as approver_name',
                'r.id_risk', 'r.title as risk_title',
                'tt.id_task', 'tt.title as task_title',
            ])
            ->whereNull('d.deleted_at');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('d.status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('d.type', $request->type);
        }

        $docs = $query->orderByDesc('d.created_at')->get();
        return response()->json($docs->map(fn($d) => $this->formatDoc($d)));
    }

    // =========================================================================
    // POST /api/documents/upload
    // Verifica assinatura digital em PDFs. Marca non_compliant se aprovado sem assinatura.
    // =========================================================================
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file'    => ['required', 'file', 'max:51200'],
            'title'   => ['required', 'string', 'max:255'],
            'type'    => ['required', 'string', 'in:evidence,policy,procedure,report,framework'],
            'version' => ['nullable', 'string', 'max:50'],
            'date'    => ['nullable', 'date'],
            'source'  => ['nullable', 'string', 'max:100'],
            'notes'   => ['nullable', 'string'],
        ]);

        $file        = $request->file('file');
        $userId      = session('tb_user.id') ?? null;
        $isFramework = $request->input('type') === 'framework';
        $ext         = strtolower($file->getClientOriginalExtension());

        // SHA-256 do ficheiro
        $sha256 = hash_file('sha256', $file->getRealPath());

        // Verificar assinatura digital (PDFs)
        $signatureResult = $this->checkDigitalSignature($file, $ext);

        // Guardar ficheiro
        $storedName = Str::uuid() . '.' . $ext;
        $yearMonth  = now()->format('Y/m');
        $path       = $file->storeAs($yearMonth, $storedName, 'attachments');

        // Attachment
        $attachmentId = DB::table('attachment')->insertGetId([
            'file_name'     => $file->getClientOriginalName(),
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $storedName,
            'file_path'     => $path,
            'mime_type'     => $file->getMimeType() ?? 'application/octet-stream',
            'file_size'     => $file->getSize(),
            'sha256'        => $sha256,
            'uploaded_by'   => $userId,
            'created_at'    => now(),
        ], 'id_attachment');

        $status = $isFramework ? 'approved' : 'pending';

        $docId = DB::table('document')->insertGetId([
            'id_attachment'  => $attachmentId,
            'title'          => $request->input('title'),
            'type'           => $request->input('type'),
            'status'         => $status,
            'version'        => $request->input('version', '1.0'),
            'file_path'      => $path,
            'sha256'         => $sha256,
            'is_signed'      => $signatureResult['is_signed'],
            'signature_valid'=> $signatureResult['signature_valid'],
            'signature_info' => $signatureResult['signature_info'],
            // Se framework aprovado automaticamente sem assinatura → non_compliant
            'non_compliant'  => ($isFramework && !$signatureResult['is_signed']),
            'uploaded_by'    => $userId,
            'approved_by'    => $isFramework ? $userId : null,
            'approved_at'    => $isFramework ? now() : null,
            'created_at'     => now(),
        ], 'id_doc');

        $ingestResult = 'skipped';
        if (in_array($ext, self::INGESTABLE_EXTS) && $isFramework) {
            IngestDocumentJob::dispatch($docId, $path, $request->input('title'), 'auto');
            $ingestResult = 'queued';
        }

        // Preparar aviso de assinatura para o frontend
        $signatureWarning = null;
        if (!$signatureResult['is_signed']) {
            $signatureWarning = 'Este documento não contém assinatura digital. '
                . ($isFramework
                    ? 'Foi aprovado automaticamente mas marcado como não conforme.'
                    : 'Ao ser aprovado sem assinatura ficará marcado como não conforme.');
        } elseif ($signatureResult['is_signed'] && !$signatureResult['signature_valid']) {
            $signatureWarning = 'O documento contém uma assinatura digital mas não foi possível validá-la. '
                . 'Verifique antes de aprovar.';
        }

        return response()->json([
            'success'           => true,
            'doc_id'            => $docId,
            'status'            => $status,
            'ingest'            => $ingestResult,
            'is_signed'         => $signatureResult['is_signed'],
            'signature_valid'   => $signatureResult['signature_valid'],
            'signature_warning' => $signatureWarning,
            'message'           => $isFramework
                ? 'Framework guardado e a indexar no Pinecone.'
                : 'Documento enviado. Aguarda aprovação.',
        ], 201);
    }

    // =========================================================================
    // POST /api/documents/{id}/approve
    // Aprovação com suporte a non_compliant (aprovado sem assinatura).
    // Body opcional: { force: true } — aprova mesmo sem assinatura
    // =========================================================================
    public function approve(Request $request, int $id): JsonResponse
    {
        $doc = DB::table('document as d')
            ->leftJoin('attachment as a', 'a.id_attachment', '=', 'd.id_attachment')
            ->select(['d.*', 'a.file_path as attach_path', 'a.original_name', 'a.file_name'])
            ->where('d.id_doc', $id)
            ->first();

        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Documento não encontrado.'], 404);
        }
        if ($doc->status === 'approved') {
            return response()->json(['success' => false, 'message' => 'Documento já aprovado.'], 409);
        }

        $isSigned = (bool) ($doc->is_signed ?? false);
        $force    = (bool) $request->input('force', false);

        // Se não está assinado e não veio force=true, avisa o frontend mas não bloqueia
        // O admin decide — se confirmar, marca como non_compliant
        $nonCompliant = !$isSigned;

        $userId = session('tb_user.id') ?? null;

        DB::table('document')->where('id_doc', $id)->update([
            'status'        => 'approved',
            'approved_by'   => $userId,
            'approved_at'   => now(),
            'non_compliant' => $nonCompliant,
        ]);

        // Ingest Pinecone
        $filePath = $doc->attach_path ?? $doc->file_path ?? null;
        $ext      = $filePath ? strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) : null;
        $ingest   = 'skipped';

        if ($filePath && in_array($ext, self::INGESTABLE_EXTS)) {
            $docName = $doc->original_name ?? $doc->file_name ?? $doc->title ?? 'documento';
            IngestDocumentJob::dispatch($id, $filePath, $docName, 'auto');
            $ingest = 'queued';
        }

        return response()->json([
            'success'       => true,
            'non_compliant' => $nonCompliant,
            'message'       => $nonCompliant
                ? 'Documento aprovado mas marcado como não conforme (sem assinatura digital).'
                : 'Documento aprovado.',
            'ingest'        => $ingest,
        ]);
    }

    // =========================================================================
    // POST /api/documents/{id}/reject
    // Aceita agora motivo de rejeição.
    // Body: { reason?: string }
    // =========================================================================
    public function reject(Request $request, int $id): JsonResponse
    {
        $doc = DB::table('document')->where('id_doc', $id)->first();
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Documento não encontrado.'], 404);
        }
        if ($doc->status === 'rejected') {
            return response()->json(['success' => false, 'message' => 'Documento já rejeitado.'], 409);
        }

        DB::table('document')->where('id_doc', $id)->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->input('reason'),
        ]);

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // POST /api/documents/{id}/obsolete
    // Marca como obsoleto e limpa Pinecone.
    // =========================================================================
    public function obsolete(int $id): JsonResponse
    {
        $doc = DB::table('document')->where('id_doc', $id)->first();
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Documento não encontrado.'], 404);
        }

        DB::table('document')->where('id_doc', $id)->update([
            'status' => 'obsolete',
        ]);

        $pythonBin = env('PYTHON_BIN', 'python');
        $scriptPath = base_path('rag/purge_doc.py');
        if (file_exists($scriptPath)) {
            $cmd = escapeshellarg($pythonBin) . ' ' . escapeshellarg($scriptPath) . ' --tenant "default" --doc-id ' . escapeshellarg((string)$id);
            $envVars = array_merge(getenv(), [
                'PINECONE_API_KEY' => env('PINECONE_API_KEY', ''),
                'PINECONE_INDEX'   => env('PINECONE_INDEX', ''),
            ]);
            $process = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $envVars);
            if (is_resource($process)) {
                fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
                proc_close($process);
            }
        }

        return response()->json(['success' => true, 'message' => 'Marcado como obsoleto e removido da pesquisa.']);
    }

    // =========================================================================
    // POST /api/documents/{id}/delete
    // Soft delete — só permite apagar documentos não aprovados.
    // Documentos aprovados não devem ser eliminados (auditoria).
    // =========================================================================
    public function delete(int $id): JsonResponse
    {
        $doc = DB::table('document')->where('id_doc', $id)->whereNull('deleted_at')->first();

        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Documento não encontrado.'], 404);
        }

        if ($doc->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Documentos aprovados não podem ser eliminados. Rejeite primeiro se necessário.',
            ], 409);
        }

        DB::table('document')->where('id_doc', $id)->update(['deleted_at' => now()]);

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // POST /api/documents/{id}/re-upload
    // Re-upload de um documento (ex: após assinar digitalmente).
    // Cria um novo documento ligado ao anterior via replaces_doc_id.
    // O documento anterior NÃO é apagado — fica em histórico.
    // =========================================================================
    public function reUpload(Request $request, int $id): JsonResponse
    {
        $oldDoc = DB::table('document')->where('id_doc', $id)->whereNull('deleted_at')->first();
        if (!$oldDoc) {
            return response()->json(['success' => false, 'message' => 'Documento não encontrado.'], 404);
        }

        $request->validate([
            'file'    => ['required', 'file', 'max:51200'],
            'version' => ['nullable', 'string', 'max:50'],
        ]);

        $file   = $request->file('file');
        $userId = session('tb_user.id') ?? null;
        $ext    = strtolower($file->getClientOriginalExtension());
        $sha256 = hash_file('sha256', $file->getRealPath());

        // Verificar assinatura do novo ficheiro
        $signatureResult = $this->checkDigitalSignature($file, $ext);

        // Guardar novo ficheiro
        $storedName = Str::uuid() . '.' . $ext;
        $yearMonth  = now()->format('Y/m');
        $path       = $file->storeAs($yearMonth, $storedName, 'attachments');

        $attachmentId = DB::table('attachment')->insertGetId([
            'file_name'     => $file->getClientOriginalName(),
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $storedName,
            'file_path'     => $path,
            'mime_type'     => $file->getMimeType() ?? 'application/octet-stream',
            'file_size'     => $file->getSize(),
            'sha256'        => $sha256,
            'uploaded_by'   => $userId,
            'created_at'    => now(),
        ], 'id_attachment');

        // Criar novo documento ligado ao anterior
        $newVersion = $request->input('version') ?? $this->bumpVersion($oldDoc->version ?? '1.0');
        $isFramework = ($oldDoc->type === 'framework');
        $status = $isFramework ? 'approved' : 'pending';

        $newDocId = DB::table('document')->insertGetId([
            'id_attachment'  => $attachmentId,
            'title'          => $oldDoc->title,
            'type'           => $oldDoc->type,
            'status'         => $status,
            'version'        => $newVersion,
            'file_path'      => $path,
            'sha256'         => $sha256,
            'is_signed'      => $signatureResult['is_signed'],
            'signature_valid'=> $signatureResult['signature_valid'],
            'signature_info' => $signatureResult['signature_info'],
            'non_compliant'  => false,
            'replaces_doc_id'=> $id,         // liga ao anterior
            'uploaded_by'    => $userId,
            'approved_by'    => $isFramework ? $userId : null,
            'approved_at'    => $isFramework ? now() : null,
            'created_at'     => now(),
        ], 'id_doc');

        if ($isFramework) {
            // Frameworks são logo ingeridos e o antigo fica obsoleto
            if (in_array($ext, self::INGESTABLE_EXTS)) {
                IngestDocumentJob::dispatch($newDocId, $path, $oldDoc->title, 'auto');
            }
            $this->obsolete($id); // limpa do pinecone e marca obsoleto
            $msg = 'Nova versão (v' . $newVersion . ') carregada e indexada. Versão antiga obsoleta.';
        } else {
            $msg = $signatureResult['is_signed']
                ? 'Nova versão carregada com assinatura digital. Aguarda aprovação.'
                : 'Nova versão carregada sem assinatura digital. Aguarda aprovação.';
        }

        return response()->json([
            'success'         => true,
            'new_doc_id'      => $newDocId,
            'replaces_doc_id' => $id,
            'version'         => $newVersion,
            'is_signed'       => $signatureResult['is_signed'],
            'signature_valid' => $signatureResult['signature_valid'],
            'message'         => $msg,
        ], 201);
    }

    // =========================================================================
    // GET /api/documents/{id}/download
    // =========================================================================
    public function download(int $id)
    {
        $doc = DB::table('document as d')
            ->leftJoin('attachment as a', 'a.id_attachment', '=', 'd.id_attachment')
            ->select(['d.id_doc', 'd.title', 'd.file_path', 'a.file_path as attach_path', 'a.original_name', 'a.file_name'])
            ->where('d.id_doc', $id)
            ->first();

        if (!$doc) abort(404, 'Documento não encontrado.');

        $path        = $doc->attach_path ?? $doc->file_path;
        $displayName = $doc->original_name ?? $doc->file_name ?? $doc->title ?? 'documento';

        if (!$path || !Storage::disk('attachments')->exists($path)) {
            abort(404, 'Ficheiro não encontrado no servidor.');
        }

        return Storage::disk('attachments')->download($path, $displayName);
    }

    // =========================================================================
    // GET /api/documents/{id}/preview
    // Serve o ficheiro com Content-Disposition: inline — para visualização
    // directa no browser (object tag, img, pre). Não dispara download.
    // =========================================================================
    public function preview(int $id)
    {
        $doc = DB::table('document as d')
            ->leftJoin('attachment as a', 'a.id_attachment', '=', 'd.id_attachment')
            ->select([
                'd.id_doc', 'd.title', 'd.file_path',
                'a.file_path as attach_path', 'a.original_name',
                'a.file_name', 'a.mime_type',
            ])
            ->where('d.id_doc', $id)
            ->first();

        if (!$doc) abort(404, 'Documento não encontrado.');

        $path        = $doc->attach_path ?? $doc->file_path;
        $displayName = $doc->original_name ?? $doc->file_name ?? $doc->title ?? 'documento';
        $mime        = $doc->mime_type ?? 'application/octet-stream';

        if (!$path || !Storage::disk('attachments')->exists($path)) {
            abort(404, 'Ficheiro não encontrado no servidor.');
        }

        $fullPath = Storage::disk('attachments')->path($path);

        return response()->file($fullPath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . rawurlencode($displayName) . '"',
            'X-Frame-Options'     => 'SAMEORIGIN',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    // =========================================================================
    // Verificação de assinatura digital
    // Suporte actual: PDFs (via iconv + parsing básico)
    // Extensível: adicionar suporte a PKCS#7, docx, etc.
    // =========================================================================
    private function checkDigitalSignature($file, string $ext): array
    {
        $result = [
            'is_signed'      => false,
            'signature_valid' => null,   // null = não verificado; true/false = verificado
            'signature_info'  => null,
        ];

        if ($ext !== 'pdf') {
            return $result; // Apenas PDFs por agora
        }

        try {
            $content = file_get_contents($file->getRealPath());
            if ($content === false) return $result;

            // Procurar marcadores de assinatura digital em PDFs:
            // /Sig — campo de assinatura, /ByteRange — área de assinatura, /Contents — dados PKCS#7
            $hasSigField   = str_contains($content, '/Sig')       || str_contains($content, '/ByteRange');
            $hasSigContents= str_contains($content, '/Contents ') && str_contains($content, '/ByteRange');
            $hasAcroSig    = str_contains($content, '/adbe.pkcs7') || str_contains($content, '/Adobe.PPKLite');

            if (!$hasSigField && !$hasSigContents && !$hasAcroSig) {
                return $result; // Sem assinatura
            }

            $result['is_signed'] = true;

            // Tentar extrair informação básica do certificado
            // (Validação completa exigiria openssl_pkcs7_verify ou biblioteca externa)
            $sigInfo = [];

            if (preg_match('/\/Name\s*\(([^)]+)\)/', $content, $m)) {
                $sigInfo[] = 'Signatário: ' . $this->sanitizePdfString($m[1]);
            }
            if (preg_match('/\/M\s*\(D:(\d{14})/', $content, $m)) {
                $sigInfo[] = 'Data: ' . substr($m[1], 0, 8) . ' ' . substr($m[1], 8, 6);
            }
            if (preg_match('/\/Reason\s*\(([^)]+)\)/', $content, $m)) {
                $sigInfo[] = 'Motivo: ' . $this->sanitizePdfString($m[1]);
            }

            $result['signature_info']  = $sigInfo ? implode(' · ', $sigInfo) : 'Assinatura detectada';
            // Marcar como não validado (null) — validação completa requer openssl
            $result['signature_valid'] = null;

        } catch (\Exception $e) {
            Log::warning('Erro ao verificar assinatura digital', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    private function sanitizePdfString(string $s): string
    {
        // Remove caracteres não-ASCII e controlo que aparecem em strings PDF
        return trim(preg_replace('/[^\x20-\x7E]/', '', $s));
    }

    private function bumpVersion(string $version): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', $version, $m)) {
            return $m[1] . '.' . ((int) $m[2] + 1);
        }
        return $version . '.1';
    }

    private function formatDoc($d): array
    {
        $fileName = $d->original_name ?? $d->file_name ?? $d->title ?? '—';
        return [
            'id'               => $d->id_doc,
            'title'            => $d->title ?? $fileName,
            'type'             => $d->type   ?? 'evidence',
            'status'           => $d->status ?? 'pending',
            'version'          => $d->version ?? '1.0',
            'file_name'        => $fileName,
            'mime_type'        => $d->mime_type ?? null,
            'file_size'        => $d->file_size ?? null,
            'created_at'       => $d->created_at,
            'approved_at'      => $d->approved_at,
            'indexed_at'       => $d->indexed_at ?? null,
            'chunk_count'      => $d->chunk_count ?? null,
            'ingest_error'     => $d->ingest_error ?? null,
            // Novos campos de assinatura
            'is_signed'        => (bool) ($d->is_signed ?? false),
            'signature_valid'  => isset($d->signature_valid) ? (bool) $d->signature_valid : null,
            'signature_info'   => $d->signature_info ?? null,
            'non_compliant'    => (bool) ($d->non_compliant ?? false),
            'rejection_reason' => $d->rejection_reason ?? null,
            'sha256'           => $d->sha256 ?? $d->attach_sha256 ?? null,
            'replaces_doc_id'  => $d->replaces_doc_id ?? null,
            // Relações
            'uploader'         => $d->uploader_name ?? $d->uploader_email ?? 'Utilizador',
            'approver'         => $d->approver_name ?? null,
            'origin'           => $d->risk_title
                ? "Risco: {$d->risk_title}" . ($d->task_title ? " > {$d->task_title}" : '')
                : null,
            'has_file'         => !empty($d->attach_path ?? $d->file_path),
        ];
    }


// =========================================================================
// POST /api/documents/gemini-analyse
// Recebe texto extraído do frontend e envia ao Gemini para análise.
// Body: { text: string, doc_id?: int }
// =========================================================================
    public function geminiAnalyse(\Illuminate\Http\Request $request, \App\Services\GeminiClient $gemini)
    {
        // Validação básica dos dados recebidos do frontend
        $request->validate([
            'text' => 'required|string',
            'doc_id' => 'required'
        ]);

        try {
            $text = $request->input('text');

            // 1. Definição do Prompt com a estrutura solicitada
            $prompt = "És um Auditor de Segurança da Informação rigoroso (ISO 27001 e NIS2). "
                    . "Analisa o documento abaixo exclusivamente do ponto de vista de Governance, Risk e Compliance (GRC). "
                    . "REGRA 1: Não incluas frases introdutórias (ex: 'Aqui está a análise'). Começa imediatamente no primeiro título '## Resumo'. "
                    . "REGRA 2: Se o documento NÃO for uma política de segurança, procedimento de TI, evidência técnica, relatório de auditoria ou documento corporativo relacionado com Cibersegurança/GRC (por exemplo, se for um contrato de estágio, uma fatura, ou um texto aleatório), deves RESPONDER APENAS a seguinte frase: "
                    . "'O documento fornecido não parece ser uma evidência ou política de Segurança da Informação válida para análise no contexto GRC.' Não escrevas mais nada. "
                    . "Se o documento for válido para GRC, estrutura a tua resposta exatamente assim:\n\n"
                    . "## Resumo\nO que este documento cobre (2-3 frases).\n\n"
                    . "## Pontos Fortes\nO que está bem definido e coberto do ponto de vista de segurança.\n\n"
                    . "## Lacunas Identificadas\nO que falta, está incompleto ou representa um risco técnico.\n\n"
                    . "## Alinhamento NIS2 / QNRCS\nQue controlos este documento potencialmente cobre e quais ficam por cobrir.\n\n"
                    . "## Recomendações\n3 a 5 melhorias técnicas concretas e acionáveis para melhorar a postura de segurança.\n\n"
                    . "---\nDocumento a analisar:\n"
                    . $text;

            // 2. Chamada ao serviço Gemini existente no projeto
            $respostaIA = $gemini->generate($prompt);

            // 3. Retorno da resposta estruturada para o frontend
            return response()->json([
                'success' => true,
                'analysis' => $respostaIA
            ]);

        } catch (\Exception $e) {
            // Registo do erro para diagnóstico no laravel.log
            \Log::error("Erro na Análise Gemini do Documento ID {$request->doc_id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar a análise: ' . $e->getMessage()
            ], 500);
        }
    }

// =========================================================================
// GET /api/documents/{id}/extract-text
// Extrai texto puro do ficheiro para enviar ao Gemini.
// Suporta: PDF (via smalot/pdfparser), TXT, MD
// =========================================================================
public function extractText(int $id): JsonResponse
{
    $doc = DB::table('document as d')
        ->leftJoin('attachment as a', 'a.id_attachment', '=', 'd.id_attachment')
        ->select(['d.id_doc', 'd.title', 'a.file_path as attach_path', 'a.mime_type', 'a.original_name'])
        ->where('d.id_doc', $id)
        ->whereNull('d.deleted_at')
        ->first();

    if (!$doc) {
        return response()->json(['success' => false, 'message' => 'Documento não encontrado.'], 404);
    }

    $filePath = $doc->attach_path ?? null;
    if (!$filePath || !Storage::disk('attachments')->exists($filePath)) {
        return response()->json(['success' => false, 'message' => 'Ficheiro não encontrado.'], 404);
    }

    $absolutePath = Storage::disk('attachments')->path($filePath);
    $mime         = strtolower($doc->mime_type ?? '');
    $name         = strtolower($doc->original_name ?? '');

    try {
        // PDF
        if ($mime === 'application/pdf' || str_ends_with($name, '.pdf')) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($absolutePath);
            $text   = $pdf->getText();

        // TXT / MD
        } elseif (in_array($mime, ['text/plain', 'text/markdown']) || preg_match('/\.(txt|md)$/', $name)) {
            $text = file_get_contents($absolutePath);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de ficheiro não suportado para extração de texto.',
                'text'    => '',
            ]);
        }

        return response()->json([
            'success' => true,
            'text'    => $text ?: '',
            'length'  => strlen($text ?? ''),
        ]);

    } catch (\Exception $e) {
        Log::error('extractText falhou', ['doc_id' => $id, 'error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'Erro ao extrair texto: ' . $e->getMessage(), 'text' => ''], 500);
    }
}

    // =========================================================================
    // POST /api/documents/cyberplan
    // Recebe o payload do Cyberplanner e guarda como um documento JSON.
    // =========================================================================
    public function storeCyberPlan(Request $request): JsonResponse
    {
        $request->validate([
            'type'   => ['required', 'string'],
            'meta'   => ['nullable', 'array'],
            'score'  => ['nullable', 'array'],
            'stats'  => ['nullable', 'array'],
            'answers'=> ['nullable', 'array'],
        ]);

        $payload = $request->all();
        $jsonContent = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $userId = session('tb_user.id') ?? null;
        $company = $payload['meta']['company'] ?? 'Empresa';
        $companySlug = Str::slug($company);
        $date = now()->format('Y-m-d_His');

        $fileName = "cyberplan_{$companySlug}_{$date}.json";
        $yearMonth = now()->format('Y/m');
        $path = "{$yearMonth}/{$fileName}";

        // Guardar no disco
        Storage::disk('attachments')->put($path, $jsonContent);

        // SHA-256 do conteúdo
        $sha256 = hash('sha256', $jsonContent);

        // Inserir Attachment
        $attachmentId = DB::table('attachment')->insertGetId([
            'file_name'     => $fileName,
            'original_name' => $fileName,
            'stored_name'   => $fileName,
            'file_path'     => $path,
            'mime_type'     => 'application/json',
            'file_size'     => strlen($jsonContent),
            'sha256'        => $sha256,
            'uploaded_by'   => $userId,
            'created_at'    => now(),
        ], 'id_attachment');

        // Inserir Document
        $docId = DB::table('document')->insertGetId([
            'id_attachment'  => $attachmentId,
            'title'          => "Plano de Segurança: " . ($payload['meta']['company'] ?? 'S/ Nome'),
            'type'           => 'report',
            'status'         => 'approved', // Gerado pelo sistema
            'version'        => '1.0',
            'file_path'      => $path,
            'sha256'         => $sha256,
            'uploaded_by'    => $userId,
            'approved_by'    => $userId,
            'approved_at'    => now(),
            'created_at'     => now(),
        ], 'id_doc');

        return response()->json([
            'success' => true,
            'doc_id'  => $docId,
            'message' => 'Plano de segurança guardado como evidência com sucesso.',
        ], 201);
    }
}