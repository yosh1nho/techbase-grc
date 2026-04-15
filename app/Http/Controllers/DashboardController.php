<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\GeminiClient;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // =========================================================================
    // GET /api/dashboard
    // KPIs principais: riscos + planos de tratamento + compliance
    // Resposta única para o dashboard carregar tudo num só fetch.
    // =========================================================================
    public function index(): JsonResponse
    {
        return response()->json([
            'risks'      => $this->riskStats(),
            'treatments' => $this->treatmentStats(),
            'compliance' => $this->complianceStats(),
        ]);
    }

    // =========================================================================
    // GET /api/dashboard/risks
    // Breakdown de riscos por score — separado para refresh independente.
    // =========================================================================
    public function risks(): JsonResponse
    {
        return response()->json($this->riskStats());
    }

    // =========================================================================
    // GET /api/dashboard/treatments
    // Breakdown de planos de tratamento por status.
    // =========================================================================
    public function treatments(): JsonResponse
    {
        return response()->json($this->treatmentStats());
    }

    // =========================================================================
    // GET /api/dashboard/compliance
    // Percentagem de conformidade NIS2 e QNRCS (usa a view v_compliance_summary).
    // =========================================================================
    public function compliance(): JsonResponse
    {
        return response()->json($this->complianceStats());
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function riskStats(): array
    {
        // Subquery para o assessment mais recente de cada risco
        $latestAssessment = DB::raw('(
            SELECT DISTINCT ON (id_risk)
                id_risk, probability, impact, score
            FROM riskassessmenthistory
            ORDER BY id_risk, assessedat DESC
        ) AS la');

        $risks = DB::table('risk as r')
            ->leftJoin($latestAssessment, 'la.id_risk', '=', 'r.id_risk')
            ->leftJoin('asset as a', 'a.id_asset', '=', 'r.id_asset')
            ->leftJoin('User as u', 'u.id_user', '=', 'r.risk_owner_id')
            ->select([
                'r.id_risk',
                'r.title',
                'r.status',
                'a.display_name as asset_name',
                'a.hostname     as asset_hostname',
                'u.name         as owner_name',
                DB::raw('COALESCE(la.score, 0) as score'),
                DB::raw('COALESCE(la.probability, 0) as probability'),
                DB::raw('COALESCE(la.impact, 0) as impact'),
            ])
            ->whereNull('r.deleted_at')
            ->get();

        $total  = $risks->count();
        $high   = $risks->filter(fn($r) => $r->score >= 17)->count();
        $medium = $risks->filter(fn($r) => $r->score >= 10 && $r->score < 17)->count();
        $low    = $risks->filter(fn($r) => $r->score < 10)->count();

        // Top risco — maior score, em caso de empate o mais recente (primeiro na query)
        $topRisk = $risks->sortByDesc('score')->first();

        return [
            'total'  => $total,
            'high'   => $high,
            'medium' => $medium,
            'low'    => $low,
            'top_risk' => $topRisk ? [
                'id'         => $topRisk->id_risk,
                'title'      => $topRisk->title,
                'score'      => (int) $topRisk->score,
                'asset'      => $topRisk->asset_name ?? $topRisk->asset_hostname,
                'owner'      => $topRisk->owner_name,
                'status'     => $topRisk->status,
            ] : null,
        ];
    }

    private function treatmentStats(): array
    {
        $plans = DB::table('risktreatmentplan')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*)                                         AS total,
                COUNT(*) FILTER (WHERE status = 'Concluído')    AS done,
                COUNT(*) FILTER (WHERE status = 'Em curso')     AS doing,
                COUNT(*) FILTER (WHERE status = 'To do')        AS todo,
                COUNT(*) FILTER (WHERE status = 'Em atraso')    AS overdue
            ")
            ->first();

        $total   = (int) $plans->total;
        $done    = (int) $plans->done;
        $doing   = (int) $plans->doing;
        $todo    = (int) $plans->todo;
        $overdue = (int) $plans->overdue;

        // Planos em atraso com mais detalhe (para o painel de próximas acções no JS)
        $overdueList = [];
        if ($overdue > 0) {
            $overdueList = DB::table('risktreatmentplan as rtp')
                ->leftJoin('risk as r', 'r.id_risk', '=', 'rtp.id_risk')
                ->select(['rtp.id_plan', 'rtp.due_date', 'r.title as risk_title'])
                ->where('rtp.status', 'Em atraso')
                ->whereNull('rtp.deleted_at')
                ->orderBy('rtp.due_date')
                ->limit(5)
                ->get()
                ->map(fn($p) => [
                    'id'         => $p->id_plan,
                    'risk_title' => $p->risk_title,
                    'due_date'   => $p->due_date,
                ])
                ->toArray();
        }

        return [
            'total'        => $total,
            'done'         => $done,
            'doing'        => $doing,
            'todo'         => $todo,
            'overdue'      => $overdue,
            'overdue_list' => $overdueList,
        ];
    }

    private function complianceStats(): array
    {
        // Usa a view v_compliance_summary que criámos na migration
        $rows = DB::select('SELECT * FROM v_compliance_summary ORDER BY framework_name');

        if (empty($rows)) {
            return [
                'frameworks' => [],
                'nis2'       => null,
                'qnrcs'      => null,
            ];
        }

        $frameworks = array_map(fn($r) => [
            'id'                      => (int) $r->id_framework,
            'name'                    => $r->framework_name,
            'total_controls'          => (int) $r->total_controls,
            'assessed_controls'       => (int) $r->assessed_controls,
            'compliant'               => (int) $r->compliant,
            'partial'                 => (int) $r->partial,
            'non_compliant'           => (int) $r->non_compliant,
            'compliance_pct'          => (float) $r->compliance_pct,
            'compliance_pct_weighted' => (float) $r->compliance_pct_weighted,
        ], $rows);

        // Atalhos directos para NIS2 e QNRCS — o JS usa estes para os mini-cards
        $byName = collect($frameworks)->keyBy('name');

        return [
            'frameworks' => $frameworks,
            'nis2'       => $byName['NIS2']  ?? null,
            'qnrcs'      => $byName['QNRCS'] ?? null,
        ];
    }


    // =========================================================================
    // GET /api/dashboard/wazuh-alerts
    // Vai buscar os últimos alertas ao Elasticsearch
    // =========================================================================
    public function getWazuhAlerts(): JsonResponse
    {
        try {
            $response = Http::withOptions(['verify' => false]) // Ignora SSL auto-assinado
                ->withBasicAuth(env('ELASTIC_USER'), env('ELASTIC_PASS'))
                ->post('https://192.168.10.20:9200/wazuh-alerts-*/_search', [
                    'size' => 50, // Traz os últimos 50
                    'sort' => [['@timestamp' => 'desc']]
                ]);

            if (!$response->successful()) {
                throw new \Exception("Erro ao conectar ao Elasticsearch: " . $response->body());
            }

            $hits = $response->json('hits.hits') ?? [];
            
            // Vamos formatar e limpar os dados para enviar para o Frontend
            $alerts = array_map(function($hit) {
                $source = $hit['_source'];
                $rule = $source['rule'] ?? [];
                
                return [
                    'id' => $hit['_id'],
                    'timestamp' => $source['@timestamp'] ?? null,
                    'agent' => $source['agent']['name'] ?? 'Desconhecido',
                    'rule_id' => $rule['id'] ?? null,
                    'description' => $rule['description'] ?? 'Sem descrição',
                    'level' => $rule['level'] ?? 0,
                    'mitre_tactics' => $rule['mitre_tactics'] ?? [],
                    'mitre_techniques' => $rule['mitre_techniques'] ?? [],
                    'compliance' => array_keys(array_filter([
                        'PCI DSS' => isset($rule['pci_dss']),
                        'GDPR' => isset($rule['gdpr']),
                        'NIST' => isset($rule['nist_800_53']),
                        'CIS' => isset($rule['cis']),
                    ])),
                    'remediation' => $source['data']['sca']['check']['remediation'] ?? null,
                ];
            }, $hits);

            return response()->json($alerts);

        } catch (\Exception $e) {
            \Log::error('Erro ao buscar alertas Wazuh: ' . $e->getMessage());
            return response()->json(['error' => 'Falha ao buscar alertas do SIEM.'], 500);
        }
    }

    // =========================================================================
    // POST /api/dashboard/wazuh-alerts/{id}/analyze
    // Gera ou devolve análise IA de um alerta
    // =========================================================================
    public function analyzeWazuhAlert(Request $request, $id, GeminiClient $gemini): JsonResponse
    {
        try {
            // 1. Verificar se já existe análise na BD (Cache)
            $existing = DB::table('wazuh_alert_analysis')->where('wazuh_alert_id', $id)->first();
            if ($existing) {
                return response()->json([
                    'success' => true, 
                    'text' => $existing->analysis_text, 
                    'cached' => true,
                    'date' => date('d/m/Y H:i', strtotime($existing->created_at))
                ]);
            }

            // 2. Se não existir, vamos buscar os dados deste alerta específico ao ES
            $esQuery = Http::withOptions(['verify' => false])
                ->withBasicAuth(env('ELASTIC_USER'), env('ELASTIC_PASS'))
                ->post('https://192.168.10.20:9200/wazuh-alerts-*/_search', [
                    'query' => ['term' => ['_id' => $id]]
                ]);

            $hit = $esQuery->json('hits.hits.0._source');
            if (!$hit) {
                return response()->json(['message' => 'Alerta não encontrado no SIEM.'], 404);
            }

            // 3. Montar o Prompt super estruturado
            $agent = $hit['agent']['name'] ?? 'Desconhecido';
            $desc = $hit['rule']['description'] ?? 'Sem descrição';
            $level = $hit['rule']['level'] ?? 'N/A';
            $mitreTac = implode(', ', $hit['rule']['mitre_tactics'] ?? ['Nenhuma']);
            $mitreTech = implode(', ', $hit['rule']['mitre_techniques'] ?? ['Nenhuma']);
            $remed = $hit['data']['sca']['check']['remediation'] ?? 'Nenhuma sugerida pelo Wazuh.';

            $prompt = "Atuando como SOC Analyst de Nível 3 (Especialista em Cibersegurança), analisa o seguinte alerta do SIEM Wazuh e cria um plano de ação executivo e técnico.\n\n"
                    . "DADOS DO ALERTA:\n"
                    . "- Ativo: {$agent}\n"
                    . "- Regra: {$desc} (Nível/Severidade: {$level})\n"
                    . "- Táticas MITRE ATT&CK: {$mitreTac}\n"
                    . "- Técnicas MITRE ATT&CK: {$mitreTech}\n"
                    . "- Sugestão do Sistema: {$remed}\n\n"
                    . "Fornece a tua resposta usando APENAS HTML simples (<b>, <ul>, <li>, <br>). Sem Markdown. A tua resposta deve estar dividida em 3 partes curtas e diretas:\n"
                    . "1. <b>Resumo do Risco:</b> O que isto significa para o negócio (1 parágrafo).\n"
                    . "2. <b>Ameaça (MITRE Context):</b> Como um atacante pode explorar estas táticas/técnicas (1 parágrafo curto).\n"
                    . "3. <b>Plano de Mitigação:</b> 2 a 3 passos práticos para resolver o problema baseados na sugestão do sistema e boas práticas.";

            // 4. Chamar a API do Gemini
            $aiText = $gemini->generate($prompt);
            if (empty($aiText)) throw new \Exception("A IA devolveu uma resposta vazia.");
$aiText = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $aiText);
            // 5. Guardar na Base de Dados
            $analysisId = DB::table('wazuh_alert_analysis')->insertGetId([
                'wazuh_alert_id' => $id,
                'analysis_text' => $aiText,
                'created_by' => session('tb_user.id'),
                'created_at' => now()
            ], 'id_analysis');

            return response()->json([
                'success' => true, 
                'text' => $aiText, 
                'cached' => false,
                'date' => now()->format('d/m/Y H:i')
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar análise IA do alerta: ' . $e->getMessage());
            return response()->json(['message' => 'Falha ao analisar alerta: ' . $e->getMessage()], 500);
        }
    }
}
