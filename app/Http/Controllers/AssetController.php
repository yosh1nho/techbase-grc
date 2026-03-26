<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
    // POST /api/assets/sync-acronis   (sem alterações — mantido igual)
    // =========================================================================
    public function syncAcronis(): JsonResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer acronis_fake_jwt_token_998877'
            ])->get('http://127.0.0.1:9999/resource_management/v4/resources');

            if (!$response->ok()) {
                return response()->json([
                    'message' => 'Erro ao acessar API Acronis',
                    'status'  => $response->status(),
                ], 500);
            }

            $resources = $response->json()['items'] ?? [];

            foreach ($resources as $r) {
                DB::table('asset')->updateOrInsert(
                    ['id_acronis' => $r['id']],
                    [
                        'source'              => 'acronis',
                        'display_name'        => $r['name'] ?? null,
                        'hostname'            => $r['host']['hostname'] ?? null,
                        'ip'                  => $r['network']['ip'] ?? null,
                        'mac_address'         => $r['network']['mac'] ?? null,
                        'type'                => $r['type'] ?? null,
                        'os_name'             => $r['os']['name'] ?? null,
                        'os_version'          => $r['os']['version'] ?? null,
                        'os_build'            => $r['os']['build'] ?? null,
                        'os_arch'             => $r['os']['arch'] ?? null,
                        'os_patch_level'      => $r['os']['patch_level'] ?? null,
                        'agent_status'        => $r['agent']['status'] ?? null,
                        'agent_version'       => $r['agent']['version'] ?? null,
                        'backup_enabled'      => (int) ($r['protection']['backup_enabled'] ?? 0),
                        'antimalware_enabled' => (int) ($r['protection']['antimalware_enabled'] ?? 0),
                        'patch_mgmt_enabled'  => (int) ($r['protection']['patch_management_enabled'] ?? 0),
                        'acronis_tenant_id'   => $r['tenant_id'] ?? null,
                        'domain'              => $r['host']['domain'] ?? null,
                        'updatedat'           => now(),
                    ]
                );
            }

            return response()->json(['message' => 'Sync completed', 'count' => count($resources)]);

        } catch (\Exception $e) {
            $payload = [
                'message'   => 'Erro interno ao sincronizar Acronis',
                'error'     => $e->getMessage(),
                'exception' => get_class($e),
            ];
            if (config('app.debug')) {
                $payload['file']  = $e->getFile();
                $payload['line']  = $e->getLine();
                $payload['trace'] = collect($e->getTrace())->take(5)->map(fn($t) => [
                    'file' => $t['file'] ?? null, 'line' => $t['line'] ?? null, 'function' => $t['function'] ?? null,
                ])->toArray();
            }
            \Log::error('Sync Acronis falhou', $payload);
            return response()->json($payload, 500);
        }
    }

    // =========================================================================
    // POST /api/assets
    // Cria um ativo manual. Aceita agora criticality (novo) e tags (opcional).
    // =========================================================================
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name'        => 'required|string|max:255',
                'type'        => 'required|string|max:100',
                // Aceita 'criticality' (novo padrão) OU 'criticity' (legacy do frontend)
                'criticality' => ['nullable', Rule::in(self::CRITICALITIES)],
                'criticity'   => 'nullable|string|max:50',
                'owner'       => 'nullable|string|max:255',
                'ip'          => 'nullable|string|max:45',
                'notes'       => 'nullable|string',
                'tag_ids'     => 'nullable|array',
                'tag_ids.*'   => 'integer|exists:asset_tag,id_tag',
            ]);

            // Normalizar criticality — novo campo tem prioridade; fallback mapeia o legacy
            $criticality = $data['criticality'] ?? $this->mapLegacyCriticity($data['criticity'] ?? null);

            $id = DB::table('asset')->insertGetId([
                'source'       => 'manual',
                'display_name' => $data['name'],
                'hostname'     => $data['name'],
                'type'         => $data['type'],
                'ip'           => $data['ip'] ?? null,
                'criticality'  => $criticality,
                // Mantém 'status' para compatibilidade com queries antigas
                'status'       => $data['criticity'] ?? $criticality,
                'description'  => $data['notes'] ?? null,
                'created_at'   => now(),
                'updatedat'    => now(),
            ], 'id_asset');

            // Associar tags se fornecidas
            if (!empty($data['tag_ids'])) {
                foreach (array_unique($data['tag_ids']) as $tagId) {
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
}