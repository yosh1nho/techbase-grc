<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RagChatService
{
    public function __construct(
        private GeminiClient $gemini,
        private PineconeClient $pinecone,
    ) {}

    public function answer(string $question, string $tenantId, array $scope = []): array
    {
        $topK = 6;

        // 1. Busca nas FRAMEWORKS (NIS2 e QNRCS)
        $frameworkHits = $this->pinecone->searchRecordsText(
            text: $this->normalize($question),
            topK: $topK,
            namespace: $tenantId,
            filter: $this->buildFilter($scope),
        );

        // 2. Busca nas POLÍTICAS INTERNAS
        $internalPoliciesHits = $this->pinecone->searchRecordsText(
            text: $this->normalize($question),
            topK: $topK,
            namespace: 'default',
            filter: $this->buildFilter($scope),
        );

        // 3. Fundir e re-ordenar por score
        $allHits = array_merge($frameworkHits, $internalPoliciesHits);
        usort($allHits, function ($a, $b) {
            $scoreA = $a['_score'] ?? $a['score'] ?? 0;
            $scoreB = $b['_score'] ?? $b['score'] ?? 0;
            return $scoreB <=> $scoreA;
        });
        $hits = array_slice($allHits, 0, 8);

        $contextBlocks = [];
        $sources       = [];

        logger()->info('RAG hits', ['tenant' => $tenantId, 'total' => count($hits)]);

        foreach ($hits as $h) {
            $fields = $h['fields'] ?? [];

            // Helper: procura o campo em fields primeiro, depois na raiz do hit
            $get = function (string $k) use ($h, $fields) {
                return $fields[$k] ?? $h[$k] ?? null;
            };

            $text = (string)($get('text') ?? '');
            if ($text === '') continue;

            // ─── Resolver doc_id ─────────────────────────────────────────────
            // O Pinecone guarda nos metadados (fields) como 'doc_id'.
            // O _id do vector tem formato "31:3" (doc_id:chunk_index).
            // Usamos fields.doc_id como fonte primária; se vazio, extraímos do _id.
            $rawDocId = (string)($get('doc_id') ?? '');

            if ($rawDocId === '' && isset($h['_id'])) {
                // _id tem formato "31:3" → extrair a parte antes dos ":"
                $parts    = explode(':', (string)$h['_id'], 2);
                $rawDocId = $parts[0] ?? '';
            }

            // Confirma que é numérico (id_doc da BD) e não um UUID de framework
            $isNumericDocId = $rawDocId !== '' && ctype_digit($rawDocId);

            // ─── doc_name / título ────────────────────────────────────────────
            $docName  = (string)($get('doc_name') ?? $get('doc_title') ?? $rawDocId);
            $docTitle = $docName;

            // ─── chunk_id legível ─────────────────────────────────────────────
            $chunkId = (string)($h['_id'] ?? ($rawDocId . ':' . ($get('chunk_index') ?? '')));

            // ─── campos de referência ─────────────────────────────────────────
            $controlCode   = (string)($get('control_code')   ?? '');
            $controlFamily = (string)($get('control_family') ?? '');
            $articleCode   = (string)($get('article_code')   ?? '');
            $chapter       = (string)($get('chapter')        ?? '');

            $chunkIndex = $get('chunk_index');
            $chunkIndex = is_numeric($chunkIndex) ? (int)$chunkIndex : null;

            $pageNumber = $get('page_number');
            $pageNumber = is_numeric($pageNumber) ? (int)$pageNumber : null;

            // ─── doc_url ──────────────────────────────────────────────────────
            $docUrl = null;
            $t      = mb_strtolower($docTitle);

            if (str_contains($t, 'nis2')) {
                // Framework NIS2 — ficheiro público
                $docUrl = url('/mock/frameworks/NIS2.pdf');

            } elseif (str_contains($t, 'qnrcs') || str_contains($t, 'cncs')) {
                // Framework QNRCS — ficheiro público
                $docUrl = url('/mock/frameworks/cncs-qnrcs-2019.pdf');

            } elseif ($isNumericDocId) {
                // Documento interno — servir via rota segura usando o id_doc real
                $docUrl = url('/documents/view/' . $rawDocId);
            }

            logger()->debug('RAG source', [
                '_id'      => $h['_id'] ?? null,
                'doc_id'   => $rawDocId,
                'doc_name' => $docTitle,
                'doc_url'  => $docUrl,
            ]);

            // ─── ref humano ───────────────────────────────────────────────────
            if ($controlCode || $controlFamily) {
                $ref = trim(($controlFamily ? $controlFamily . ' — ' : '') . $controlCode);
            } elseif ($articleCode || $chapter) {
                $ref = trim(($chapter ? "Cap. {$chapter} — " : '') . $articleCode);
            } else {
                $ref = 'Trecho';
            }

            $refLabel = trim($docTitle . ' — ' . $ref . ($chunkIndex !== null ? " — chunk {$chunkIndex}" : ''));

            $contextBlocks[] = "[{$refLabel}]\n" . $text;

            $sources[] = [
                'doc_id'        => $rawDocId  ?: null,
                'doc_title'     => $docTitle  ?: null,
                'doc_url'       => $docUrl,
                'ref_label'     => $refLabel,
                'ref'           => $ref,
                'control_code'  => $controlCode  ?: null,
                'control_family'=> $controlFamily ?: null,
                'article_code'  => $articleCode  ?: null,
                'chapter'       => $chapter      ?: null,
                'chunk_index'   => $chunkIndex,
                'page_number'   => $pageNumber,
                'chunk_id'      => $chunkId      ?: null,
                'snippet'       => mb_substr($text, 0, 240),
                'score'         => $h['_score']  ?? null,
            ];
        }

        $contextText = $contextBlocks
            ? implode("\n\n---\n\n", $contextBlocks)
            : "Nenhuma evidência relevante foi encontrada na base vetorial.";

        $answer = $this->gemini->generate(
            $this->buildPrompt($contextText, $question)
        );

        return [
            'answer'  => $answer,
            'sources' => $sources,
        ];
    }

    private function buildPrompt(string $context, string $question): string
    {
        $prompt = <<<'PROMPT'
Você é um assistente de GRC focado em NIS2 e QNRCS/CNCS.
Regras:
- Responda em PT-PT, direto e prático.
- Use APENAS o contexto fornecido como base factual.
- Se o contexto não suportar, diga claramente o que falta e que evidência/documento seria necessário.
- NÃO coloque fontes dentro das frases ou bullets.
- Liste todas as fontes apenas na secção final "Fontes usadas".
- NUNCA repita fontes dentro de cada bullet.
- Cada bullet deve conter apenas a ação ou requisito.

Estrutura da resposta:
1. Resumo (2-3 linhas).
2. Checklist com 6-10 bullets curtos.
3. [OPCIONAL] Tabela de campos - inclui APENAS se a pergunta pedir campos/atributos mínimos de inventário de ativos. Para perguntas gerais, NÃO incluas tabela.
   Se incluíres tabela, usa EXATAMENTE este formato dentro de um bloco ```md```:
   - 4 colunas: Campo | Ativo de Rede (Geral) | Ativo de Rede (Externo) | Ponto de Rede
   - Cada campo numa LINHA SEPARADA. NUNCA multiplos campos na mesma linha.
   - Valores das celulas: apenas "Sim" ou "Nao aplicavel". NUNCA texto longo numa celula.
   - Exemplo:
     | Campo                | Ativo de Rede (Geral) | Ativo de Rede (Externo) | Ponto de Rede |
     |----------------------|-----------------------|-------------------------|---------------|
     | Numero de inventario | Sim                   | Sim                     | Nao aplicavel |
     | Nome do equipamento  | Sim                   | Nao aplicavel           | Nao aplicavel |
4. [OPCIONAL] Evidencias (3-6 bullets) - so se disponivel no contexto.
5. Fontes usadas (lista simples, sem repetir [Fonte...]).

CONTEXTO (RAG):
PROMPT;

        $prompt .= $context;
        $prompt .= <<<'PROMPT'


PERGUNTA:
PROMPT;

        $prompt .= $question;
        $prompt .= "\n\nRESPONDA:";

        return $prompt;
    }

    private function buildFilter(array $scope): ?array
    {
        return null;
    }

    private function normalize(string $s): string
    {
        $s = trim($s);
        if (mb_strlen($s) > 8000) $s = mb_substr($s, 0, 8000);
        return $s;
    }
}