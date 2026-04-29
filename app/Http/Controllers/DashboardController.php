<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\GeminiClient;
use App\Services\MemPalaceClient;
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
public function getWazuhAlerts(Request $request): JsonResponse
{
    $page = (int) $request->query('page', 1);
    $search = $request->query('q');
    $severity = $request->query('severity', 'all');
    
    // 1. Receber as novas datas
    $dateFrom = $request->query('dateFrom');
    $dateTo = $request->query('dateTo');
    
    $limit = 50;
    $from = ($page - 1) * $limit;

    try {
        $query = [
            'bool' => [
                'must' => []
            ]
        ];

        // 2. Filtro de Datas no Elasticsearch
        if (!empty($dateFrom) || !empty($dateTo)) {
            $dateRange = [];
            if (!empty($dateFrom)) $dateRange['gte'] = $dateFrom . 'T00:00:00.000Z';
            if (!empty($dateTo)) $dateRange['lte'] = $dateTo . 'T23:59:59.999Z';
            $query['bool']['must'][] = ['range' => ['@timestamp' => $dateRange]];
        }

            // Se houver texto de pesquisa
            if (!empty($search)) {
                $query['bool']['must'][] = [
                    'multi_match' => [
                        'query' => $search,
                        'fields' => ['rule.description', 'agent.name', 'rule.id'],
                        'fuzziness' => 'AUTO'
                    ]
                ];
            }

            // Se houver filtro de severidade (Wazuh levels: Critical >= 10, Medium 5-9, Low < 5)
            if ($severity !== 'all') {
                $range = match($severity) {
                    'critical' => ['gte' => 10],
                    'medium'   => ['gte' => 5, 'lt' => 10],
                    'low'      => ['lt' => 5],
                    default    => null
                };
                if ($range) {
                    $query['bool']['must'][] = ['range' => ['rule.level' => $range]];
                }
            }

            $body = [
                'size' => $limit,
                'from' => $from,
                'sort' => [['@timestamp' => 'desc']],
            ];

            if (!empty($query['bool']['must'])) {
                $body['query'] = $query;
            }

            $response = Http::withOptions(['verify' => false])
                ->withBasicAuth(env('ELASTIC_USER'), env('ELASTIC_PASS'))
                ->post('https://192.168.10.20:9200/wazuh-alerts-*/_search', $body);

            if (!$response->successful()) throw new \Exception("Erro SIEM: " . $response->body());

            $data = $response->json();
            $hits = $data['hits']['hits'] ?? [];
            $total = $data['hits']['total']['value'] ?? 0;

            $alerts = array_map(function($hit) {
                $s = $hit['_source'];
                $rule = $s['rule'] ?? [];
                
                return [
                    'id' => $hit['_id'],
                    'timestamp' => $s['@timestamp'] ?? null,
                    'agent' => $s['agent']['name'] ?? 'Desconhecido',
                    'rule_id' => $rule['id'] ?? null,
                    'description' => $rule['description'] ?? 'Sem descrição',
                    'level' => $rule['level'] ?? 0,

                    // 🎯 MITRE — estrutura real do Wazuh: rule.mitre.tactic / rule.mitre.technique
                    'mitre_tactics'    => $rule['mitre']['tactic']    ?? [],
                    'mitre_techniques' => $rule['mitre']['technique']  ?? [],
                    'mitre_ids'        => $rule['mitre']['id']         ?? [],
                    
                    'compliance' => array_keys(array_filter([
                        'PCI DSS' => isset($rule['pci_dss']),
                        'GDPR' => isset($rule['gdpr']),
                        'NIST' => isset($rule['nist_800_53']),
                        'CIS' => isset($rule['cis']),
                        'SOC 2' => isset($rule['soc_2']),
                    ])),
                    
                    'remediation' => $s['data']['sca']['check']['remediation'] ?? null,
                ];
            }, $hits);
            return response()->json([
                'data' => $alerts,
                'total' => $total,
                'current_page' => $page,
                'last_page' => ceil($total / $limit)
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    // =========================================================================
    // POST /api/dashboard/wazuh-alerts/{id}/analyze
    // Gera análise IA de um alerta c/ RAG Duplo (ElasticSearch + MemPalace)
    // =========================================================================
    public function analyzeWazuhAlert(Request $request, $id, GeminiClient $gemini, MemPalaceClient $memPalace): JsonResponse
    {
        try {
            // 1. Verificar se já existe análise na BD (a menos que seja forçado)
            $force = $request->query('force', false);
            
            if (!$force) {
                $existing = DB::table('wazuh_alert_analysis')->where('wazuh_alert_id', $id)->first();
                if ($existing) {
                    return response()->json([
                        'success' => true, 
                        'text' => $existing->analysis_text, 
                        'cached' => true,
                        'date' => date('d/m/Y H:i', strtotime($existing->created_at))
                    ]);
                }
            } else {
                // Se for force, apagamos o registo antigo para não acumular lixo
                DB::table('wazuh_alert_analysis')->where('wazuh_alert_id', $id)->delete();
            }

            // 2. Buscar os dados deste alerta ao SIEM (Elasticsearch)
            $esQuery = Http::withOptions(['verify' => false])
                ->withBasicAuth(env('ELASTIC_USER'), env('ELASTIC_PASS'))
                ->post('https://192.168.10.20:9200/wazuh-alerts-*/_search', [
                    'query' => ['term' => ['_id' => $id]]
                ]);

            $hit = $esQuery->json('hits.hits.0._source');
            if (!$hit) {
                return response()->json(['message' => 'Alerta não encontrado no SIEM.'], 404);
            }

            // 3. 🎯 O MATCH GRC (Agora com Fallback para Syslog/Firewalls!)
            $agentId = $hit['agent']['id'] ?? null;
            $agentName = $hit['agent']['name'] ?? 'Desconhecido';
            
            $asset = null;
            $assetContext = "Ativo não registado no inventário GRC.";
            $structuredTag = "[HOSTNAME: {$agentName}]";

            // Tentativa A: Pelo ID exato do Agente (Ignora o 000 pois é o Manager genérico para Syslog)
            if ($agentId && $agentId !== '000') {
                $asset = DB::table('asset')
                    ->leftJoin('User as u', 'asset.owner_id', '=', 'u.id_user')
                    ->select('asset.*', 'u.name as owner_name')
                    ->where('id_acronis', $agentId)
                    ->first();
            }

            // Tentativa B (FALLBACK): Pelo Hostname (Crucial para OPNsense, APs e Routers)
            if (!$asset && $agentName !== 'Desconhecido') {
                $asset = DB::table('asset')
                    ->leftJoin('User as u', 'asset.owner_id', '=', 'u.id_user')
                    ->select('asset.*', 'u.name as owner_name')
                    ->where('hostname', $agentName)
                    ->orWhere('display_name', $agentName)
                    ->first();
            }

            if ($asset) {
                // Âncora Estruturada para o MemPalace
                $structuredTag = "[ASSET_ID: {$asset->id_asset}] [HOSTNAME: {$asset->hostname}]";
                
                // Contexto Rico
                $assetContext = "Nome GRC: {$asset->display_name}\n"
                              . "Criticidade para o Negócio: {$asset->criticality}\n"
                              . "Responsável (Owner): " . ($asset->owner_name ?? 'Não atribuído') . "\n"
                              . "IP Interno: {$asset->ip}";
            }

            // EXTRAIR A DATA REAL DO ATAQUE (Passo crucial corrigido)
            $rawTimestamp = $hit['@timestamp'] ?? 'now';
            $alertDate = date('Y-m-d H:i', strtotime($rawTimestamp));

            // 4. 🧠 RECALL MEMPALACE
            $historyQuery = "Busca estrita para {$structuredTag}: incidentes anteriores, alertas críticos, malwares e soluções aplicadas.";
            $historicoMemPalace = $memPalace->recall($historyQuery);

            // 5. Montar o Prompt
            $desc      = $hit['rule']['description'] ?? 'Sem descrição';
            $level     = $hit['rule']['level'] ?? 'N/A';
            $mitreTac  = implode(', ', $hit['rule']['mitre']['tactic']     ?? []);
            $mitreTech = implode(', ', $hit['rule']['mitre']['technique']   ?? []);
            $mitreIds  = implode(', ', $hit['rule']['mitre']['id']          ?? []);
            $remed     = $hit['data']['sca']['check']['remediation'] ?? 'Nenhuma sugerida pelo Wazuh.';

            // Bloco MITRE apenas se houver dados
            $mitreBlock = '';
            if (!empty($mitreTac) || !empty($mitreTech) || !empty($mitreIds)) {
                $mitreBlock = "MAPEAMENTO MITRE ATT&CK:\n"
                            . (!empty($mitreIds)  ? "- IDs das Técnicas : {$mitreIds}\n"  : '')
                            . (!empty($mitreTac)  ? "- Táticas          : {$mitreTac}\n"  : '')
                            . (!empty($mitreTech) ? "- Técnicas         : {$mitreTech}\n" : '');
            } else {
                $mitreBlock = "MAPEAMENTO MITRE ATT&CK: Não disponível para esta regra.\n";
            }

            $prompt = "Atuando como SOC Analyst de Nível 3, analisa o seguinte alerta do SIEM Wazuh.\n\n"
                    . "DADOS DO ATIVO NO INVENTÁRIO:\n{$assetContext}\n\n"
                    . "DADOS DO ALERTA ATUAL:\n"
                    . "- Regra  : {$desc}\n"
                    . "- Severidade Wazuh: {$level}/15\n"
                    . "- Sugestão do Sistema: {$remed}\n\n"
                    . $mitreBlock . "\n"
                    . "CONTEXTO HISTÓRICO (DIÁRIO DE SOC):\n{$historicoMemPalace}\n\n"
                    . "Fornece a resposta usando APENAS HTML simples (<b>, <ul>, <li>, <br>). Sem Markdown. Divide em 3 partes:\n"
                    . "1. <b>Resumo do Risco:</b> Impacto no negócio considerando a criticidade do ativo. Indica se há padrão histórico preocupante no Diário de SOC.\n"
                    . "2. <b>Ameaça MITRE ATT&CK:</b> Explica especificamente as técnicas mapeadas (". ($mitreIds ?: 'sem ID') .") — como o atacante as usa e qual o objetivo tático.\n"
                    . "3. <b>Plano de Mitigação:</b> 3 a 4 passos práticos e concretos para a equipa atuar imediatamente.";

            // 6. Chamar o Gemini
            $aiText = $gemini->generate($prompt);
            if (empty($aiText)) throw new \Exception("A IA devolveu uma resposta vazia.");
            $aiText = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $aiText);

            // 7. Guardar a análise na cache local (MySQL)
            $analysisId = DB::table('wazuh_alert_analysis')->insertGetId([
                'wazuh_alert_id' => $id,
                'analysis_text' => $aiText,
                'created_by' => session('tb_user.id'),
                'created_at' => now()
            ], 'id_analysis');

            // 8. 🧠 MINE MEMPALACE: Guardar com a DATA REAL do Elasticsearch!
            $memoriaLimpa = strip_tags(str_replace(['<br>', '<ul>', '<li>'], ' ', $aiText));
            
            $textoParaGravar = "{$structuredTag} | DATA DO ALERTA: {$alertDate} | "
                             . "Alerta SIEM gerado: '{$desc}' (Severidade: {$level}). "
                             . "A mitigação documentada pelo SOC foi: {$memoriaLimpa}";

            $memPalace->remember("wzh-{$id}", $textoParaGravar);

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
