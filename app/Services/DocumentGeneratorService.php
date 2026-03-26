<?php

namespace App\Services;

class DocumentGeneratorService
{
    // Tipos de documento que o sistema sabe gerar
    // Cada um tem um prompt base que guia o Gemini para o formato correcto
    private const TEMPLATES = [
        'password_policy' => [
            'label'    => 'Política de Gestão de Passwords',
            'controls' => ['PR.GA-7', 'PR.GA-6', 'PR.GA-1'],
            'hint'     => 'Política formal de gestão de palavras-passe para sistemas de informação.',
        ],
        'backup_procedure' => [
            'label'    => 'Procedimento de Backup e Recuperação',
            'controls' => ['PR.PI-4', 'RC.PR-1', 'RC.ME-1'],
            'hint'     => 'Procedimento operacional para cópias de segurança e recuperação de dados.',
        ],
        'access_control_policy' => [
            'label'    => 'Política de Controlo de Acessos',
            'controls' => ['PR.GA-4', 'PR.GA-1', 'PR.GA-3'],
            'hint'     => 'Política de controlo de acessos baseada no princípio do menor privilégio.',
        ],
        'incident_response' => [
            'label'    => 'Plano de Resposta a Incidentes',
            'controls' => ['RS.PR-1', 'RS.CO-1', 'RS.AN-1', 'DE.AE-5'],
            'hint'     => 'Plano formal de resposta a incidentes de segurança.',
        ],
        'asset_inventory' => [
            'label'    => 'Procedimento de Inventário de Ativos',
            'controls' => ['ID.GA-1', 'ID.GA-2', 'ID.GA-5'],
            'hint'     => 'Procedimento para inventariação e classificação de ativos de informação.',
        ],
        'vulnerability_management' => [
            'label'    => 'Política de Gestão de Vulnerabilidades',
            'controls' => ['PR.PI-12', 'DE.MC-8', 'RS.MI-3'],
            'hint'     => 'Política para identificação, avaliação e mitigação de vulnerabilidades.',
        ],
        'custom' => [
            'label'    => 'Documento personalizado',
            'controls' => [],
            'hint'     => 'Documento de segurança personalizado.',
        ],
    ];

    public function __construct(
        private GeminiClient  $gemini,
        private PineconeClient $pinecone,
    ) {}

    // =========================================================================
    // Gerar documento a partir de template ou instrução livre
    // =========================================================================
    public function generate(
        string  $instruction,
        string  $tenantId,
        string  $docType    = 'custom',
        ?string $entityName = null,
    ): array {
        $template = self::TEMPLATES[$docType] ?? self::TEMPLATES['custom'];

        // 1. Buscar contexto relevante no Pinecone (controlos relacionados)
        $searchQuery = $this->buildSearchQuery($instruction, $template);
        $hits = $this->pinecone->searchRecordsText(
            text:      $searchQuery,
            topK:      10,
            namespace: $tenantId,
        );

        // 2. Construir blocos de contexto
        $contextBlocks = [];
        $sources       = [];

        foreach ($hits as $h) {
            $fields = $h['fields'] ?? [];
            $get    = fn($k) => $h[$k] ?? $fields[$k] ?? null;
            $text   = (string) ($get('text') ?? '');
            if ($text === '') continue;

            $docName     = (string) ($get('doc_name') ?? $get('doc_title') ?? '');
            $controlCode = (string) ($get('control_code') ?? '');
            $articleCode = (string) ($get('article_code') ?? '');
            $chapter     = (string) ($get('chapter') ?? '');

            $ref = $controlCode ?: ($articleCode ? "Art. {$articleCode}" : ($chapter ? "Cap. {$chapter}" : 'Referência'));
            $label = trim($docName . ($ref ? ' — ' . $ref : ''));

            $contextBlocks[] = "[{$label}]\n" . $text;
            $sources[] = [
                'ref_label'    => $label,
                'control_code' => $controlCode ?: null,
                'article_code' => $articleCode ?: null,
                'snippet'      => mb_substr($text, 0, 180),
            ];
        }

        $contextText = $contextBlocks
            ? implode("\n\n---\n\n", $contextBlocks)
            : "Sem contexto específico encontrado. Gera o documento com base nas boas práticas gerais de GRC.";

        // 3. Prompt específico para geração de documentos
        $prompt = $this->buildDocumentPrompt(
            context:     $contextText,
            instruction: $instruction,
            template:    $template,
            entityName:  $entityName,
        );

        // 4. Gerar com Gemini
        $content = $this->gemini->generate($prompt);

        return [
            'content'   => $content,
            'doc_type'  => $docType,
            'template'  => $template['label'],
            'controls'  => $template['controls'],
            'sources'   => $sources,
            'meta' => [
                'entity_name'  => $entityName,
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    // =========================================================================
    // Listar templates disponíveis
    // =========================================================================
    public function templates(): array
    {
        return collect(self::TEMPLATES)->map(fn($t, $k) => [
            'key'      => $k,
            'label'    => $t['label'],
            'controls' => $t['controls'],
            'hint'     => $t['hint'],
        ])->values()->toArray();
    }

    // =========================================================================
    // Prompts privados
    // =========================================================================

    private function buildSearchQuery(string $instruction, array $template): string
    {
        $controls = implode(' ', $template['controls']);
        return trim("{$instruction} {$controls} " . $template['hint']);
    }

    private function buildDocumentPrompt(
        string  $context,
        string  $instruction,
        array   $template,
        ?string $entityName,
    ): string {
        $entity  = $entityName ? "Organização: {$entityName}" : '';
        $label   = $template['label'];
        $controls = $template['controls']
            ? 'Controlos de referência: ' . implode(', ', $template['controls'])
            : '';

        return <<<PROMPT
Você é um especialista em GRC (Governança, Risco e Conformidade) focado em NIS2 e QNRCS/CNCS.
A sua tarefa é GERAR um documento formal de segurança da informação.

{$entity}
Tipo de documento: {$label}
{$controls}

REGRAS OBRIGATÓRIAS:
- Escreva em PT-PT formal e profissional.
- Use APENAS o contexto RAG como base normativa — cite os controlos relevantes.
- O documento deve ser COMPLETO e PRONTO A USAR — não deixe secções em branco.
- Estrutura obrigatória:
  1. Cabeçalho (Título, Versão: 1.0, Data: {$this->today()}, Classificação: Confidencial)
  2. Objetivo e Âmbito
  3. Definições (termos chave)
  4. Responsabilidades
  5. Política / Procedimento (corpo principal — seja específico e detalhado)
  6. Controlos e Referências Normativas (citar controlos QNRCS/NIS2 relevantes)
  7. Revisão e Aprovação (periodicidade anual)
- Use formatação clara: títulos numerados, bullets para requisitos.
- Cada requisito deve ser concreto e verificável — evite linguagem vaga.
- NÃO inclua secção de "Fontes usadas" — as referências vão em "Controlos e Referências Normativas".

INSTRUÇÃO DO UTILIZADOR:
{$instruction}

CONTEXTO NORMATIVO (RAG):
{$context}

GERE O DOCUMENTO COMPLETO:
PROMPT;
    }

    private function today(): string
    {
        return now()->format('d/m/Y');
    }
}
