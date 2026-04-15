<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\GeminiClient;

class AssetController extends Controller
{
    private const CRITICALITIES = ['low', 'medium', 'high', 'critical'];

    // =========================================================================
    // GET /api/assets
    // Inclui tags e criticidade. Mantém todos os campos anteriores.
    // =========================================================================
    public function index(): JsonResponse
    {
        $assets = DB::table('asset as a')
            ->select([
                'a.id_asset', 'a.source', 'a.id_acronis', 'a.hostname',
                'a.display_name', 'a.ip', 'a.mac_address', 'a.os_arch',
                'a.os_name', 'a.os_version', 'a.os_build', 'a.os_patch_level',
                'a.agent_status', 'a.agent_version', 'a.backup_enabled',
                'a.antimalware_enabled', 'a.patch_mgmt_enabled', 'a.type',
                'a.status', 'a.description', 'a.domain', 'a.acronis_tenant_id',
                'a.created_at', 'a.updatedat',
                // Nova coluna — criticality (pode não existir ainda se a migration não correu)
                DB::raw("COALESCE(a.criticality, 'medium') as criticality"),
            ])
            ->get();

        if ($assets->isEmpty()) {
            return response()->json([]);
        }

        // Buscar todas as tags dos assets de uma vez — evita N+1
        $assetIds = $assets->pluck('id_asset')->toArray();

        $tagsMap = DB::table('asset_tag_map as m')
            ->join('asset_tag as t', 't.id_tag', '=', 'm.id_tag')
            ->select(['m.id_asset', 't.id_tag', 't.name', 't.color'])
            ->whereIn('m.id_asset', $assetIds)
            ->get()
            ->groupBy('id_asset');

        $result = $assets->map(function ($a) use ($tagsMap) {
            $tags = ($tagsMap[$a->id_asset] ?? collect())
                ->map(fn($t) => ['id' => $t->id_tag, 'name' => $t->name, 'color' => $t->color])
                ->values();

            return array_merge((array) $a, ['tags' => $tags]);
        });

        return response()->json($result);
    }

    // =========================================================================
    // GET /api/asset-tags
    // Lista todas as tags disponíveis para o select do formulário.
    // =========================================================================
    public function tags(): JsonResponse
    {
        $tags = DB::table('asset_tag')->orderBy('name')->get();
        return response()->json($tags);
    }

    // =========================================================================
    // POST /api/assets/{id}/tags
    // Associa uma ou mais tags a um asset. Ignora duplicados.
    // Body: { tag_ids: [1, 2, 3] }  OU  { tag_names: ["production", "cloud"] }
    // =========================================================================
    public function addTags(Request $request, int $assetId): JsonResponse
    {
        $asset = DB::table('asset')->where('id_asset', $assetId)->first();
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Ativo não encontrado.'], 404);
        }

        $data = $request->validate([
            'tag_ids'   => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:asset_tag,id_tag'],
            'tag_names'   => ['sometimes', 'array'],
            'tag_names.*' => ['string', 'max:100'],
        ]);

        $tagIds = collect($data['tag_ids'] ?? []);

        // Resolver nomes → ids (criar a tag se não existir)
        if (!empty($data['tag_names'])) {
            foreach ($data['tag_names'] as $name) {
                $name = strtolower(trim($name));
                $tag  = DB::table('asset_tag')->where('name', $name)->first();
                if (!$tag) {
                    $id = DB::table('asset_tag')->insertGetId(['name' => $name], 'id_tag');
                } else {
                    $id = $tag->id_tag;
                }
                $tagIds->push($id);
            }
        }

        $tagIds = $tagIds->unique()->values();

        if ($tagIds->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Nenhuma tag fornecida.'], 422);
        }

        // Inserir apenas as que ainda não existem (ON CONFLICT DO NOTHING equivalente)
        $existing = DB::table('asset_tag_map')
            ->where('id_asset', $assetId)
            ->whereIn('id_tag', $tagIds)
            ->pluck('id_tag')
            ->toArray();

        $toInsert = $tagIds->reject(fn($id) => in_array($id, $existing));

        foreach ($toInsert as $tagId) {
            DB::table('asset_tag_map')->insert(['id_asset' => $assetId, 'id_tag' => $tagId]);
        }

        // Devolver tags actuais do asset
        $currentTags = DB::table('asset_tag_map as m')
            ->join('asset_tag as t', 't.id_tag', '=', 'm.id_tag')
            ->select(['t.id_tag', 't.name', 't.color'])
            ->where('m.id_asset', $assetId)
            ->get();

        return response()->json(['success' => true, 'tags' => $currentTags]);
    }

    // =========================================================================
    // DELETE /api/assets/{id}/tags/{tagId}
    // Remove uma tag de um asset.
    // =========================================================================
    public function removeTag(int $assetId, int $tagId): JsonResponse
    {
        $deleted = DB::table('asset_tag_map')
            ->where('id_asset', $assetId)
            ->where('id_tag', $tagId)
            ->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Associação não encontrada.'], 404);
        }

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // PATCH /api/assets/{id}/criticality
    // Actualiza só a criticidade de um asset.
    // Body: { criticality: "low"|"medium"|"high"|"critical" }
    // =========================================================================
    public function updateCriticality(Request $request, int $assetId): JsonResponse
    {
        $asset = DB::table('asset')->where('id_asset', $assetId)->first();
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Ativo não encontrado.'], 404);
        }

        $data = $request->validate([
            'criticality' => ['required', Rule::in(self::CRITICALITIES)],
        ]);

        DB::table('asset')->where('id_asset', $assetId)->update([
            'criticality' => $data['criticality'],
            'updatedat'   => now(),
        ]);

        return response()->json(['success' => true, 'criticality' => $data['criticality']]);
    }

// =========================================================================
    // POST /api/assets/sync-wazuh
    // Sincroniza ativos usando a API do Wazuh
    // =========================================================================
    public function syncWazuh(): JsonResponse
    {
        try {
            // 1. Obter o Token do Wazuh (ignora a verificação SSL com verify => false)
            $authResponse = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
                ->withBasicAuth(env('WAZUH_API_USER'), env('WAZUH_API_PASS'))
                ->post('https://192.168.10.6:55000/security/user/authenticate');

            if (!$authResponse->ok()) {
                return response()->json([
                    'message' => 'Erro ao autenticar na API do Wazuh',
                    'status'  => $authResponse->status(),
                ], 401);
            }

            $token = $authResponse->json('data.token');

            // 2. Buscar a lista de Agentes
            $agentsResponse = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
                ->withToken($token)
                ->get('https://192.168.10.6:55000/agents?pretty=true&sort=-ip,name');

            if (!$agentsResponse->ok()) {
                return response()->json([
                    'message' => 'Erro ao buscar agentes no Wazuh',
                    'status'  => $agentsResponse->status(),
                ], 500);
            }

            $agents = $agentsResponse->json('data.affected_items') ?? [];

            // 3. Atualizar a Base de Dados
            foreach ($agents as $agent) {
                
                // Ignorar agentes que não têm IP ou Nome útil (ex: o próprio manager se não o quiseres listar, opcional)
                // if ($agent['id'] === '000') continue; 

                $osName = $agent['os']['name'] ?? '';

                DB::table('asset')->updateOrInsert(
                    // Vamos reaproveitar a coluna 'id_acronis' para guardar o ID do Wazuh para não teres de mexer na BD agora
                    ['id_acronis' => $agent['id']],
                    [
                        'source'         => 'wazuh',
                        'display_name'   => $agent['name'] ?? null,
                        'hostname'       => $agent['name'] ?? null,
                        'ip'             => $agent['ip'] ?? $agent['registerIP'] ?? null,
                        'type'           => str_contains(strtolower($osName), 'windows') ? 'Endpoint' : 'Servidor',
                        'os_name'        => $osName,
                        'os_version'     => $agent['os']['version'] ?? null,
                        'os_arch'        => $agent['os']['arch'] ?? null,
                        'agent_status'   => $agent['status'] ?? null,
                        'agent_version'  => $agent['version'] ?? null,
                        'updatedat'      => now(),
                    ]
                );
            }

            return response()->json(['message' => 'Sync completed', 'synced_count' => count($agents)]);

        } catch (\Exception $e) {
            $payload = [
                'message'   => 'Erro interno ao sincronizar Wazuh',
                'error'     => $e->getMessage(),
                'exception' => get_class($e),
            ];
            \Log::error('Sync Wazuh falhou', $payload);
            return response()->json($payload, 500);
        }
    }

// =========================================================================
    // POST /api/assets
    // Cria um ativo manual. Aceita criticality e cria/associa tags dinâmicas.
    // =========================================================================
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name'        => 'required|string|max:255',
                'type'        => 'required|string|max:100',
                'criticality' => ['nullable', Rule::in(self::CRITICALITIES)],
                'criticity'   => 'nullable|string|max:50',
                'owner'       => 'nullable|string|max:255',
                'ip'          => 'nullable|string|max:45',
                'notes'       => 'nullable|string',
                'tag_names'   => 'nullable|array',         
                'tag_names.*' => 'string|max:100',
            ]);

            $criticality = $data['criticality'] ?? $this->mapLegacyCriticity($data['criticity'] ?? null);

            $id = DB::table('asset')->insertGetId([
                'source'       => 'manual',
                'display_name' => $data['name'],
                'hostname'     => $data['name'],
                'type'         => $data['type'],
                'ip'           => $data['ip'] ?? null,
                'criticality'  => $criticality,
                'status'       => $data['criticity'] ?? $criticality,
                'description'  => $data['notes'] ?? null,
                'created_at'   => now(),
                'updatedat'    => now(),
            ], 'id_asset');

            // ── LÓGICA DAS TAGS: Criar se não existir e Associar ──
            if (!empty($data['tag_names'])) {
                $tagIdsToLink = [];
                foreach ($data['tag_names'] as $name) {
                    $cleanName = strtolower(trim($name));
                    
                    // Verifica se a tag já existe
                    $existingTag = DB::table('asset_tag')->where('name', $cleanName)->first();
                    
                    if (!$existingTag) {
                        // Se não existe, cria!
                        $tagIdsToLink[] = DB::table('asset_tag')->insertGetId(['name' => $cleanName], 'id_tag');
                    } else {
                        // Se existe, usa o ID
                        $tagIdsToLink[] = $existingTag->id_tag;
                    }
                }

                // Associa ao ativo (evitando duplicados no array)
                foreach (array_unique($tagIdsToLink) as $tagId) {
                    DB::table('asset_tag_map')->insert(['id_asset' => $id, 'id_tag' => $tagId]);
                }
            }

            return response()->json(['message' => 'Ativo criado com sucesso', 'id_asset' => $id], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Dados inválidos', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar ativo manual', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro interno ao criar ativo', 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // Helper — mapear criticity legacy (PT) para o novo enum EN
    // =========================================================================
    private function mapLegacyCriticity(?string $value): string
    {
        return match (strtolower(trim($value ?? ''))) {
            'crítico', 'critico', 'critical' => 'critical',
            'alto', 'high'                   => 'high',
            'baixo', 'low'                   => 'low',
            default                          => 'medium',
        };
    }


    // =========================================================================
    // GET /api/assets/{id}/analyses - Obter o histórico de análises
    // =========================================================================
    public function getAnalyses($id): JsonResponse
    {
        $analyses = DB::table('asset_ai_analysis')
            ->where('id_asset', $id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($analyses);
    }

    // =========================================================================
    // POST /api/assets/{id}/analyze - Gerar nova análise com IA
    // =========================================================================
// =========================================================================
    // POST /api/assets/{id}/analyze - Gerar nova análise com IA
    // =========================================================================
    public function analyze($id, GeminiClient $gemini): JsonResponse 
    {
        try {
            // 1. Recolher Contexto do Ativo
            $asset = DB::table('asset')->where('id_asset', $id)->first();
            if (!$asset) return response()->json(['message' => 'Ativo não encontrado.'], 404);

            // 2. Recolher Riscos Associados
            $risks = DB::table('risk')->where('id_asset', $id)->whereNotIn('status', ['Fechado', 'Aceite'])->get();
            $riskContext = $risks->isEmpty() ? "Nenhum risco aberto." : $risks->map(fn($r) => "- Risco {$r->id_risk}: {$r->title} (Severidade: " . ($r->score ?? 'N/A') . ")")->join("\n");

            // NOVO: 2.5 Recolher as Tags do Ativo
            $tags = DB::table('asset_tag_map as m')
                ->join('asset_tag as t', 't.id_tag', '=', 'm.id_tag')
                ->where('m.id_asset', $id)
                ->pluck('t.name')
                ->toArray();
            $tagsList = empty($tags) ? "Nenhuma tag atribuída" : implode(', ', $tags);

            // 3. Montar o Prompt para o CISO Virtual (Agora com as Tags!)
            $prompt = "Atuando como um CISO experiente, faz uma análise de postura de segurança concisa e direta ao ponto para o seguinte ativo:\n\n"
                    . "NOME: {$asset->display_name}\n"
                    . "TIPO: {$asset->type}\n"
                    . "CRITICIDADE: {$asset->criticality}\n"
                    . "TAGS DE CONTEXTO: {$tagsList}\n"
                    . "SISTEMA OPERATIVO: {$asset->os_name} {$asset->os_version}\n"
                    . "IP: {$asset->ip}\n"
                    . "ESTADO DO AGENTE WAZUH: {$asset->agent_status}\n\n"
                    . "RISCOS ABERTOS:\n{$riskContext}\n\n"
                    . "Fornece um resumo executivo da postura (1 parágrafo), destaca as principais vulnerabilidades/preocupações baseadas nos dados, e sugere 2 ações imediatas. Usa formatação HTML simples (<b>, <br>, <ul>, <li>) para eu injetar diretamente na interface. Não uses Markdown com asteriscos.";

            // 4. Chamar a API do Gemini 
            $aiText = $gemini->generate($prompt);

            if (empty($aiText)) throw new \Exception("A IA devolveu uma resposta vazia.");
            $aiText = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $aiText);
            // 5. Guardar na Base de Dados
            $analysisId = DB::table('asset_ai_analysis')->insertGetId([
                'id_asset' => $id,
                'analysis_text' => $aiText,
                'created_by' => session('tb_user.id'), // Mock auth session
                'created_at' => now()
            ], 'id_analysis');

            return response()->json(['success' => true, 'id' => $analysisId, 'text' => $aiText, 'date' => now()->format('d/m/Y H:i')]);

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar análise IA do ativo: ' . $e->getMessage());
            return response()->json(['message' => 'Falha ao gerar análise IA: ' . $e->getMessage()], 500);
        }
    }
}