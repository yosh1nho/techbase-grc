<?php

namespace App\Services;

class RagChatService
{
    public function __construct(
        private GeminiClient $gemini,
        private PineconeClient $pinecone,
    ) {}

    public function answer(string $question, string $tenantId, array $scope = []): array
    {
        $topK = 8;

        // ✅ Busca semântica via Pinecone "text search" (igual ao script python)
        $hits = $this->pinecone->searchRecordsText(
            text: $this->normalize($question),
            topK: $topK,
            namespace: $tenantId,
            filter: $this->buildFilter($scope),
        );

        $contextBlocks = [];
        $sources = [];

        logger()->info('RAG', ['tenant' => $tenantId, 'hits' => count($hits)]);
        
    foreach ($hits as $h) {
        $fields = $h['fields'] ?? [];

        $get = function (string $k) use ($h, $fields) {
            return $h[$k] ?? $fields[$k] ?? null;
        };

        $text = (string)($get('text') ?? '');
        if ($text === '') continue;

        $docId = (string)($get('doc_id') ?? '');
        $docName = (string)($get('doc_name') ?? $get('doc_title') ?? $docId);

        // id interno (não mostrar ao utilizador)
        $chunkId = (string)($h['_id'] ?? ($docId . ':' . ($get('chunk_index') ?? '')));

        // campos para referência humana
        $controlCode   = (string)($get('control_code') ?? '');
        $controlFamily = (string)($get('control_family') ?? '');
        $articleCode   = (string)($get('article_code') ?? '');
        $chapter       = (string)($get('chapter') ?? '');

        $chunkIndex = $get('chunk_index');
        $chunkIndex = is_numeric($chunkIndex) ? (int)$chunkIndex : null;

        $docTitle = $docName;


        $docUrl = null;
        $t = mb_strtolower($docTitle);

        if (str_contains($t, 'nis2')) {
            $docUrl = url('/mock/frameworks/NIS2.pdf');
        } elseif (str_contains($t, 'qnrcs') || str_contains($t, 'cncs')) {
            $docUrl = url('/mock/frameworks/cncs-qnrcs-2019.pdf');
        }

        // ref humano
        if ($controlCode || $controlFamily) {
            $ref = trim(($controlFamily ? $controlFamily . ' — ' : '') . $controlCode);
        } elseif ($articleCode || $chapter) {
            $ref = trim(($chapter ? "Cap. {$chapter} — " : '') . $articleCode);
        } else {
            $ref = 'Trecho';
        }

        // ✅ label humano completo (o que aparece na resposta)
        $refLabel = trim($docTitle . ' — ' . $ref . ($chunkIndex !== null ? " — chunk {$chunkIndex}" : ''));

        // usa refLabel no contexto (para o Gemini citar bonito)
        $contextBlocks[] = "[Fonte: {$refLabel}]\n" . $text;

        // retorna sources com refLabel (sem mostrar UUID na UI)
        $sources[] = [
            'doc_id' => $docId ?: null,
            'doc_title' => $docTitle ?: null,
            'doc_url' => $docUrl,

            'ref_label' => $refLabel,
            'ref' => $ref,

            'control_code' => $controlCode ?: null,
            'control_family' => $controlFamily ?: null,
            'article_code' => $articleCode ?: null,
            'chapter' => $chapter ?: null,
            'chunk_index' => $chunkIndex,

            // mantém interno para debug/modal se precisar
            'chunk_id' => $chunkId ?: null,

            'snippet' => mb_substr($text, 0, 240),
            'score' => $h['_score'] ?? null,
        ];
    }

        $contextText = $contextBlocks
            ? implode("\n\n---\n\n", $contextBlocks)
            : "Nenhuma evidência relevante foi encontrada na base vetorial.";

        $prompt = $this->buildPrompt($contextText, $question);

        $answer = $this->gemini->generate($prompt);

        return [
            'answer' => $answer,
            'sources' => $sources,
        ];
    }

    private function buildPrompt(string $context, string $question): string
    {
        // IMPORTANT: Use nowdoc (single-quoted heredoc) to avoid PHP interpreting backticks as shell execution
        $prompt = <<<'PROMPT'
Você é um assistente de GRC focado em NIS2 e QNRCS/CNCS.
Regras:
- Responda em PT-PT, direto e prático.
- Use APENAS o contexto fornecido como base factual.
- Se o contexto não suportar, diga claramente o que falta e que evidência/documento seria necessário.
- Cite fontes no formato [Fonte: ...] quando fizer afirmações.

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