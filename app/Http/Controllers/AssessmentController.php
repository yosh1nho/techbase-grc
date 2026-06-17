<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\GeminiClient;
use App\Services\PineconeClient;
use App\Services\MemPalaceClient;

class AssessmentController extends Controller
{
    public function __construct(
        private GeminiClient  $gemini,
        private PineconeClient $pinecone,
    ) {}

    // =========================================================================
    // GET /api/assessments
    // Lista histórico de avaliações (para a tabela de histórico)
    // =========================================================================
    public function index(): JsonResponse
    {
        $rows = DB::table('assessment as a')
            ->leftJoin('User as u', 'u.id_user', '=', 'a.created_by')
            ->select([
                'a.id_assessment',
                'a.period',
                'a.scopetype',
                'a.scope_label',
                'a.maturity_pct',
                'a.status',
                'a.closed_at',
                'a.createdat',
                'u.name as created_by_name',
            ])
            ->orderByDesc('a.createdat')
            ->get();

        $result = $rows->map(function ($a) {
            // Buscar frameworks ligados
            $frameworks = DB::table('assessmentframework as af')
                ->join('framework as f', 'f.id_framework', '=', 'af.id_framework')
                ->where('af.id_assessment', $a->id_assessment)
                ->pluck('f.name')
                ->toArray();

            // Buscar resultados por ativo (para o scope label e score médio)
            $results = DB::table('assessmentresult')
                ->where('id_assessment', $a->id_assessment)
                ->select(['id_asset', 'status', 'maturity_pct'])
                ->get();

            return [
                'id'          => $a->id_assessment,
                'period'      => $a->period ?? '—',
                'scope'       => $a->scope_label ?? $a->scopetype ?? '—',
                'scopetype'   => $a->scopetype,
                'frameworks'  => $frameworks,
                'maturity'    => $a->maturity_pct ?? 0,
                'status'      => $a->status ?? 'open',
                'closed_at'   => $a->closed_at,
                'created_at'  => $a->createdat,
                'created_by'  => $a->created_by_name,
                'result_count'=> $results->count(),
            ];
        });

        return response()->json($result);
    }

    // =========================================================================
    // GET /api/assessments/{id}
    // Detalhes de uma avaliação (para o modal de histórico)
    // =========================================================================
    public function show(int $id): JsonResponse
    {
        $assessment = DB::table('assessment')->where('id_assessment', $id)->first();
        if (!$assessment) {
            return response()->json(['error' => 'Não encontrado.'], 404);
        }

        $frameworks = DB::table('assessmentframework as af')
            ->join('framework as f', 'f.id_framework', '=', 'af.id_framework')
            ->where('af.id_assessment', $id)
            ->pluck('f.name')
            ->toArray();

        $results = DB::table('assessmentresult as ar')
            ->leftJoin('asset as a', 'a.id_asset', '=', 'ar.id_asset')
            ->where('ar.id_assessment', $id)
            ->select([
                'ar.id_asset',
                'ar.status',
                'ar.score',
                'ar.maturity_pct',
                'ar.summary',
                'ar.ai_analysis',
                'ar.domains_json',
                'ar.period',
                'a.hostname',
                'a.display_name',
            ])
            ->get()
            ->map(function ($r) {
                $domains = null;
                if ($r->domains_json) {
                    $decoded = json_decode($r->domains_json, true);
                    $domains = is_array($decoded) ? $decoded : null;
                }
                return [
                    'asset_id'    => $r->id_asset,
                    'asset_name'  => $r->display_name ?? $r->hostname ?? 'Ativo #' . $r->id_asset,
                    'status'      => $r->status,
                    'score'       => $r->score,
                    'maturity'    => $r->maturity_pct,
                    'summary'     => $r->summary,
                    'ai_analysis' => $r->ai_analysis,
                    'domains'     => $domains,
                ];
            });

        return response()->json([
            'id'         => $assessment->id_assessment,
            'period'     => $assessment->period,
            'scope'      => $assessment->scope_label ?? $assessment->scopetype,
            'scopetype'  => $assessment->scopetype,
            'frameworks' => $frameworks,
            'maturity'   => $assessment->maturity_pct,
            'status'     => $assessment->status,
            'closed_at'  => $assessment->closed_at,
            'created_at' => $assessment->createdat,
            'results'    => $results,
        ]);
    }

    // =========================================================================
    // POST /api/assessments
    // Cria e executa uma nova avaliação com análise Gemini
    //
    // Body:
    //   scopetype     string   'org' | 'asset'
    //   asset_id      int?     obrigatório se scopetype = 'asset'
    //   framework_ids int[]    IDs dos frameworks
    //   period        string   ex: 'Q1 2026'
    // =========================================================================
    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scopetype'     => ['required', 'in:org,asset'],
            'asset_id'      => ['nullable', 'integer'],
            'framework_ids' => ['required', 'array', 'min:1'],
            'framework_ids.*' => ['integer'],
            'period'        => ['required', 'string', 'max:20'],
        ]);

        if ($data['scopetype'] === 'asset' && empty($data['asset_id'])) {
            return response()->json(['error' => 'asset_id obrigatório quando scopetype = asset.'], 422);
        }

        $userId = session('tb_user.id') ?? null;

        // ── 1. Determinar quais ativos analisar ──────────────────────────────
        if ($data['scopetype'] === 'asset') {
            $assets = DB::table('asset')
                ->where('id_asset', $data['asset_id'])
                //->whereNull('deleted_at')
                ->get();
        } else {
            $assets = DB::table('asset')
                //->whereNull('deleted_at')
                ->get();
        }

        if ($assets->isEmpty()) {
            return response()->json(['error' => 'Nenhum ativo encontrado.'], 404);
        }

        // ── 2. Buscar frameworks ─────────────────────────────────────────────
        $frameworks = DB::table('framework')
            ->whereIn('id_framework', $data['framework_ids'])
            ->get();

        if ($frameworks->isEmpty()) {
            return response()->json(['error' => 'Nenhum framework encontrado.'], 404);
        }

// ── 3. Criar registo de avaliação ────────────────────────────────────
        $scopeLabel = $data['scopetype'] === 'asset'
            ? ($assets->first()->display_name ?? $assets->first()->hostname)
            : 'Organização';

        DB::beginTransaction();
        try {
            $assessmentId = DB::table('assessment')->insertGetId([
                'created_by'  => $userId,
                'scopetype'   => $data['scopetype'],
                'scope_label' => $scopeLabel,
                'period'      => $data['period'],
                'status'      => 'running',
                'maturity_pct'=> 0,
                'createdat'   => now(),
            ], 'id_assessment');

            // Ligar frameworks
            $frameworksToInsert = [];
            foreach ($data['framework_ids'] as $fwId) {
                $frameworksToInsert[] = [
                    'id_assessment' => $assessmentId,
                    'id_framework'  => $fwId,
                ];
            }
            if (!empty($frameworksToInsert)) {
                DB::table('assessmentframework')->insert($frameworksToInsert);
            }

            DB::commit();
        } catch (\Exception $e) {
                \Log::error('Assessment: análise falhou para ativo ' . $asset->id_asset, [
                    'error' => $e->getMessage(),
                ]);
                // Inserir resultado com erro para não bloquear os outros ativos
                DB::table('assessmentresult')->insert([
                    'id_assessment' => $assessmentId,
                    'id_asset'      => $asset->id_asset,
                    'status'        => 'error',
                    'score'         => 0,
                    'maturity_pct'  => 0,
                    'summary'       => mb_substr('Erro na análise: ' . $e->getMessage(), 0, 250),
                    'ai_analysis'   => null,
                    'createdat'     => now(),
                ]);
        }

        // ── 4. Executar análise IA por ativo ──────────────────────────────────
        $totalMaturity = 0;
        $resultsToInsert = [];

        foreach ($assets as $asset) {
            try {
                $result = $this->analyseAsset($asset, $frameworks, $data['period'], $userId);

                $resultsToInsert[] = [
                    'id_assessment' => $assessmentId,
                    'id_asset'      => $asset->id_asset,
                    'status'        => $result['status'],
                    'score'         => $result['score'],
                    'maturity_pct'  => $result['maturity'],
                    'summary'       => mb_substr($result['summary'], 0, 255),
                    'ai_analysis'   => $result['ai_analysis'],
                    'domains_json'  => json_encode($result['domains']),
                    'period'        => $data['period'],
                    'createdat'     => now(),
                ];

                $totalMaturity += $result['maturity'];

            } catch (\Exception $e) {
                \Log::error('Assessment: análise falhou para ativo ' . $asset->id_asset, [
                    'error' => $e->getMessage(),
                ]);
                // Inserir resultado com erro para não bloquear os outros ativos
                $resultsToInsert[] = [
                    'id_assessment' => $assessmentId,
                    'id_asset'      => $asset->id_asset,
                    'status'        => 'error',
                    'score'         => 0,
                    'maturity_pct'  => 0,
                    'summary'       => 'Erro na análise: ' . $e->getMessage(),
                    'ai_analysis'   => null,
                    'createdat'     => now(),
                ];
            }
        }

        if (!empty($resultsToInsert)) {
            DB::table('assessmentresult')->insert($resultsToInsert);
        }

        // ── 5. Calcular maturidade global e marcar como completo ──────────────
        $assetCount   = $assets->count();
        $globalMaturity = $assetCount > 0 ? (int) round($totalMaturity / $assetCount) : 0;

        DB::table('assessment')->where('id_assessment', $assessmentId)->update([
            'maturity_pct' => $globalMaturity,
            'status'       => 'open',
        ]);

        return response()->json([
            'success'      => true,
            'assessment_id'=> $assessmentId,
            'maturity'     => $globalMaturity,
            'assets_count' => $assetCount,
            'period'       => $data['period'],
        ], 201);
    }

    // =========================================================================
    // PATCH /api/assessments/{id}/close
    // Fechar uma avaliação
    // =========================================================================
    public function close(int $id): JsonResponse
    {
        $assessment = DB::table('assessment')->where('id_assessment', $id)->first();
        if (!$assessment) {
            return response()->json(['error' => 'Não encontrado.'], 404);
        }

        DB::table('assessment')->where('id_assessment', $id)->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // GET /api/assessments/kpis
    // KPIs para os cards do topo (última avaliação activa)
    // =========================================================================
    public function kpis(): JsonResponse
    {
        // Estatísticas globais solicitadas
        $totalAssessments = DB::table('assessment')->count();
        $openAssessments = DB::table('assessment')->where('status', 'open')->count();
        $closedAssessments = DB::table('assessment')->where('status', 'closed')->count();
        $avgMaturityGlobal = DB::table('assessment')->where('status', 'closed')->avg('maturity_pct') ?? 0;

        // Última avaliação aberta ou fechada mais recente (Para o Frontend original)
        $latest = DB::table('assessment')
            ->orderByDesc('createdat')
            ->first();

        if (!$latest) {
            return response()->json([
                'maturity'   => 0,
                'covered'    => 0,
                'partial'    => 0,
                'gap'        => 0,
                'period'     => '—',
                'frameworks' => [],
                'total'      => $totalAssessments,
                'open'       => $openAssessments,
                'closed'     => $closedAssessments,
                'avgMaturity'=> round($avgMaturityGlobal)
            ]);
        }

        // Contar resultados do compliance_assessment (mais granular que assessmentresult)
        $complianceCounts = DB::table('compliance_assessment as ca')
            ->join('framework_control as fc', 'fc.id_control', '=', 'ca.id_control')
            ->select('ca.status', DB::raw('COUNT(*) as cnt'))
            ->where(function ($q) use ($latest) {
                // Usar os assessments mais recentes por controlo
                $q->whereRaw("ca.assessed_at >= ?", [$latest->createdat ?? now()->subMonths(3)]);
            })
            ->groupBy('ca.status')
            ->pluck('cnt', 'status')
            ->toArray();

        $covered = (int) ($complianceCounts['compliant'] ?? 0);
        $partial  = (int) ($complianceCounts['partial']   ?? 0);
        $gap      = (int) ($complianceCounts['non_compliant'] ?? 0);
        $total    = $covered + $partial + $gap;
        $maturity = $total > 0 ? (int) round((($covered + $partial * 0.5) / $total) * 100) : 0;

        $frameworks = DB::table('assessmentframework as af')
            ->join('framework as f', 'f.id_framework', '=', 'af.id_framework')
            ->where('af.id_assessment', $latest->id_assessment)
            ->pluck('f.name')
            ->toArray();

        return response()->json([
            'maturity'        => $latest->maturity_pct ?? $maturity,
            'covered'         => $covered,
            'partial'         => $partial,
            'gap'             => $gap,
            'period'          => $latest->period ?? '—',
            'frameworks'      => $frameworks,
            'assessment_id'   => $latest->id_assessment,
            'assessment_status' => $latest->status ?? 'open',
            // Novos campos merging:
            'total'           => $totalAssessments,
            'open'            => $openAssessments,
            'closed'          => $closedAssessments,
            'avgMaturity'     => round($avgMaturityGlobal)
        ]);
    }

    // =========================================================================
    // Análise IA de um ativo — core do módulo
    // =========================================================================
    private function analyseAsset(
        object $asset,
        $frameworks,
        string $period,
        ?int   $userId
    ): array {
        $assetId   = $asset->id_asset;
        $assetName = $asset->display_name ?? $asset->hostname ?? "Ativo #{$assetId}";

        // ── Recolher contexto ────────────────────────────────────────────────

        // Riscos associados ao ativo
        $risks = DB::table('risk')
            ->where('id_asset', $assetId)
            ->whereNull('deleted_at')
            ->select(['id_risk', 'title', 'description', 'threat', 'vulnerability', 'status'])
            ->leftJoin(DB::raw('(
                SELECT DISTINCT ON (id_risk) id_risk as h_id_risk, probability, impact, score
                FROM riskassessmenthistory ORDER BY id_risk, assessedat DESC
            ) rh'), 'rh.h_id_risk', '=', 'risk.id_risk')
            ->addSelect(['rh.probability', 'rh.impact', 'rh.score'])
            ->get();

        // Documentos aprovados associados ao ativo (via compliance_evidence ou todos os aprovados)
        $documents = DB::table('document')
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->select(['id_doc', 'title', 'type', 'version', 'approved_at'])
            ->orderByDesc('approved_at')
            ->limit(10)
            ->get();

        // Estado actual de compliance por controlo (últimas avaliações)
$complianceState = DB::table('compliance_assessment as ca')
            ->join('framework_control as fc', 'fc.id_control', '=', 'ca.id_control')
            ->join('framework_group as fg', 'fg.id_group', '=', 'fc.id_group')
            ->join('framework as f', 'f.id_framework', '=', 'fg.id_framework')
            ->whereIn('f.id_framework', $frameworks->pluck('id_framework'))
            ->select([
                DB::raw("DISTINCT ON (ca.id_control) ca.id_control"),
                'fc.control_code',
                'fc.description',
                'fg.name as group_name',
                'f.name as framework_name',
                'ca.status',
                'ca.notes',
                'ca.assessed_at',
            ])
            ->orderByRaw('ca.id_control, ca.assessed_at DESC')
            ->get();

        // Planos de tratamento activos

// Planos de tratamento activos
        $treatments = DB::table('risktreatmentplan as tp')
            ->leftJoin('risk as r', 'r.id_risk', '=', 'tp.id_risk')
            ->where('r.id_asset', $assetId)
            ->select([
                'tp.id_plan', 
                'r.description as description', // Puxa a descrição do Risco associado
                'tp.strategy', 
                'tp.status', 
                'tp.due_date as due' // 👈 Lemos a tua coluna 'due_date' mas chamamos-lhe 'due' para a IA
            ])
            ->whereNotIn('tp.status', ['Concluído', 'Cancelado'])
            ->get();

        // ── Contexto RAG (Pinecone) ──────────────────────────────────────────
        $tenantId = (string) (session('tb_user.tenant') ?? '102');
        $ragHits  = [];
        try {
            $query = "avaliação de conformidade {$assetName} {$asset->type} {$frameworks->pluck('name')->join(' ')}";
            $ragHits = $this->pinecone->searchRecordsText(
                text:      $query,
                topK:      6,
                namespace: $tenantId,
            );
        } catch (\Exception $e) {
            \Log::warning('Assessment RAG falhou', ['error' => $e->getMessage()]);
        }

        $ragContext = collect($ragHits)->map(function ($h) {
            $fields = $h['fields'] ?? [];
            $text   = $h['text'] ?? $fields['text'] ?? '';
            $ref    = $fields['control_code'] ?? $fields['article_code'] ?? '';
            return $ref ? "[{$ref}] {$text}" : $text;
        })->filter()->take(4)->join("\n\n---\n\n");

        //RECALL MEMPALACE (O que o SOC registou sobre esta máquina?)
        $memPalace = new MemPalaceClient();
        $structuredTag = "[ASSET_ID: {$assetId}] [HOSTNAME: {$assetName}]";
        $historyQuery = "Busca estrita para {$structuredTag}: incidentes reais de cibersegurança, alertas críticos do SIEM e tempo de mitigação.";
        $historicoMemPalace = $memPalace->recall($historyQuery);

        // ── Construir prompt ─────────────────────────────────────────────────
        $prompt = $this->buildAnalysisPrompt(
            asset:              $asset,
            risks:              $risks,
            documents:          $documents,
            compliance:         $complianceState,
            treatments:         $treatments,
            frameworks:         $frameworks,
            period:             $period,
            ragContext:         $ragContext,
            historicoMemPalace: $historicoMemPalace 
        );

        // ── Chamar Gemini ────────────────────────────────────────────────────
        \Log::info('====== PROMPT ENVIADO PARA A IA (Ativo ' . $asset->id_asset . ') ======');
\Log::info($prompt);
\Log::info('======================================================================');
        $rawResponse = $this->gemini->generate($prompt);

        // ── Parsear resposta ─────────────────────────────────────────────────
        return $this->parseAnalysisResponse($rawResponse);
    }

    // =========================================================================
    // Construir prompt de análise
    // =========================================================================
    private function buildAnalysisPrompt(
        object $asset,
        $risks,
        $documents,
        $compliance,
        $treatments,
        $frameworks,
        string $period,
        string $ragContext,
        string $historicoMemPalace
    ): string {
        $assetName = $asset->display_name ?? $asset->hostname ?? 'Ativo';
        $fwNames   = $frameworks->pluck('name')->join(', ');
        $assetType = $asset->type ?? '—';
        $assetIp = $asset->ip_address ?? '—';
        $assetCriticality = $asset->criticality ?? '—';

        // Riscos
        $risksText = $risks->isEmpty()
            ? "Sem riscos registados."
            : $risks->map(fn($r) =>
                "- [{$r->id_risk}] {$r->title} | Ameaça: {$r->threat} | Vuln: {$r->vulnerability} | Score: " . (($r->probability ?? 1) * ($r->impact ?? 1)) . " | Status: {$r->status}"
            )->join("\n");

        // Documentos
        $docsText = $documents->isEmpty()
            ? "Sem documentos aprovados."
            : $documents->map(fn($d) =>
                "- {$d->title} (v{$d->version}, {$d->type}, aprovado: " . ($d->approved_at ? substr($d->approved_at, 0, 10) : '—') . ")"
            )->join("\n");

        // Compliance actual
        $compText = $compliance->isEmpty()
            ? "Sem avaliações de controlos anteriores."
            : $compliance->map(fn($c) =>
                "- [{$c->framework_name}] {$c->control_code}: {$c->status}" . ($c->notes ? " | Notas: " . mb_substr($c->notes, 0, 80) : "")
            )->join("\n");

        // Tratamentos
        $treatText = $treatments->isEmpty()
            ? "Sem planos de tratamento activos."
            : $treatments->map(fn($t) =>
                "- {$t->description} | Estratégia: {$t->strategy} | Status: {$t->status}"
            )->join("\n");

        return <<<PROMPT
Você é um especialista em GRC (Governança, Risco e Conformidade) focado em NIS2 e QNRCS/CNCS.
Execute uma avaliação formal de conformidade para o seguinte ativo.

ATIVO: {$assetName}
Tipo: {$assetType}
IP: {$assetIp}
Criticidade: {$assetCriticality}
Período: {$period}
Frameworks: {$fwNames}

CONTEXTO HISTÓRICO REAL (DIÁRIO DE SOC):
Atenção Auditor: Se existirem incidentes recentes graves abaixo, deves refletir isso com GAPs (Não Conformidades) nos controlos relacionados com proteção e deteção, e reduzir drasticamente a percentagem de maturidade (maturity).
{$historicoMemPalace}

RISCOS ASSOCIADOS:
{$risksText}

DOCUMENTOS E EVIDÊNCIAS APROVADOS:
{$docsText}

ESTADO ACTUAL DE CONTROLOS (compliance_assessment):
{$compText}

PLANOS DE TRATAMENTO ACTIVOS:
{$treatText}

CONTEXTO NORMATIVO (RAG):
{$ragContext}

INSTRUÇÕES:
Analisa todos os dados e devolve EXACTAMENTE o seguinte JSON (sem markdown, sem ```):

{
  "maturity": <inteiro 0-100>,
  "status": "<COVERED|PARTIAL|GAP>",
  "score": <inteiro 1-25>,
  "summary": "<resumo em PT-PT, máx 200 chars>",
  "domains": [
    {"domain": "Governança",    "value": <0-100>},
    {"domain": "Identificação", "value": <0-100>},
    {"domain": "Proteção",      "value": <0-100>},
    {"domain": "Detecção",      "value": <0-100>},
    {"domain": "Resposta",      "value": <0-100>},
    {"domain": "Recuperação",   "value": <0-100>}
  ],
  "controls": [
    {
      "code": "<código do controlo>",
      "framework": "<nome do framework>",
      "status": "<COVERED|PARTIAL|GAP>",
      "confidence": <0.0-1.0>,
      "rationale": "<justificação em PT-PT, máx 300 chars>",
      "risks_linked": ["<id_risk>"],
      "evidence_used": "<evidências usadas>"
    }
  ],
  "recommendations": [
    "<acção prioritária 1>",
    "<acção prioritária 2>",
    "<acção prioritária 3>"
  ],
  "ai_analysis": "<análise narrativa completa em PT-PT, sem limite de tamanho>"
}

Regras:
- maturity: percentagem ponderada (compliant=1.0, partial=0.5, gap=0.0)
- status: status global do ativo (o pior dos controlos tem mais peso)
- score: 1=muito baixo risco, 25=risco crítico (baseado nos riscos + compliance)
- Responde APENAS com JSON válido, sem texto antes ou depois
PROMPT;
    }

    // =========================================================================
    // Parsear resposta do Gemini
    // =========================================================================
    private function parseAnalysisResponse(string $raw): array
    {
        // Limpar possíveis backticks de markdown
        $clean = preg_replace('/```(?:json)?\s*|\s*```/', '', trim($raw));

        // Tentar parsear JSON
        $parsed = json_decode($clean, true);

        if (!$parsed || !isset($parsed['maturity'])) {
            // Fallback se Gemini não devolveu JSON limpo — extrair com regex
            \Log::warning('Assessment: resposta Gemini não é JSON válido', ['raw' => substr($raw, 0, 500)]);

            return [
                'maturity'    => 0,
                'status'      => 'GAP',
                'score'       => 5,
                'summary'     => 'Análise inconclusiva — revisar logs.',
                'domains'     => [],
                'controls'    => [],
                'recommendations' => [],
                'ai_analysis' => $raw, // guardar o raw para debug
            ];
        }

        return [
            'maturity'        => (int)   ($parsed['maturity']  ?? 0),
            'status'          => (string)($parsed['status']    ?? 'GAP'),
            'score'           => (int)   ($parsed['score']     ?? 1),
            'summary'         => (string)($parsed['summary']   ?? ''),
            'domains'         => (array) ($parsed['domains']   ?? []),
            'controls'        => (array) ($parsed['controls']  ?? []),
            'recommendations' => (array) ($parsed['recommendations'] ?? []),
            'ai_analysis'     => (string)($parsed['ai_analysis'] ?? $raw),
        ];
    }


}