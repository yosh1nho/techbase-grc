<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{

    public function index()
    {
        $assets = DB::table('asset')->get();
        return response()->json($assets);
    }

    public function syncAcronis()
    {
        try {

            $response = Http::withHeaders([
                'Authorization' => 'Bearer acronis_fake_jwt_token_998877'
            ])->get('http://127.0.0.1:9999/resource_management/v4/resources');

            if (!$response->ok()) {
                return response()->json([
                    "message" => "Erro ao acessar API Acronis",
                    "status" => $response->status()
                ], 500);
            }

            $data = $response->json();
            $resources = $data['items'] ?? [];

            foreach ($resources as $r) {

                DB::table('asset')->updateOrInsert(
                    ['id_acronis' => $r['id']],
                    [
                        'source'               => 'acronis',
                        'display_name'         => $r['name'] ?? null,
                        'hostname'             => $r['host']['hostname'] ?? null,
                        'ip'                   => $r['network']['ip'] ?? null,
                        'mac_address'          => $r['network']['mac'] ?? null,
                        'type'                 => $r['type'] ?? null,
                        'os_name'              => $r['os']['name'] ?? null,
                        'os_version'           => $r['os']['version'] ?? null,
                        'os_build'             => $r['os']['build'] ?? null,        // confirma a chave
                        'os_arch'              => $r['os']['arch'] ?? null,
                        'os_patch_level'       => $r['os']['patch_level'] ?? null,  // confirma a chave
                        'agent_status'         => $r['agent']['status'] ?? null,
                        'agent_version'        => $r['agent']['version'] ?? null,
                        'backup_enabled'       => (int) ($r['protection']['backup_enabled'] ?? 0),
                        'antimalware_enabled'  => (int) ($r['protection']['antimalware_enabled'] ?? 0),
                        'patch_mgmt_enabled'   => (int) ($r['protection']['patch_management_enabled'] ?? 0),
                        'acronis_tenant_id'    => $r['tenant_id'] ?? null,
                        'domain' => $r['host']['domain'] ?? null,
                        'updatedat'            => now(),
                    ]
                );
            }

            return response()->json([
                "message" => "Sync completed",
                "count" => count($resources)
            ]);

        } catch (\Exception $e) {

            $payload = [
                "message" => "Erro interno ao sincronizar Acronis",
                "error" => $e->getMessage(),
                "exception" => get_class($e),
            ];

            if (config('app.debug')) {

                $payload["file"] = $e->getFile();
                $payload["line"] = $e->getLine();

                $payload["trace"] = collect($e->getTrace())
                    ->take(5)
                    ->map(fn($t) => [
                        'file' => $t['file'] ?? null,
                        'line' => $t['line'] ?? null,
                        'function' => $t['function'] ?? null,
                    ])
                    ->toArray();
            }

            \Log::error("Sync Acronis falhou", $payload);

            return response()->json($payload, 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name'       => 'required|string|max:255',
                'type'       => 'required|string|max:100',
                'criticity'  => 'required|string|max:50',
                'owner'      => 'nullable|string|max:255',
                'ip'         => 'nullable|string|max:45',
                'prob'       => 'required|integer|min:1|max:5',
                'impact'     => 'required|integer|min:1|max:5',
                'notes'      => 'nullable|string',
            ]);

            $id = DB::table('asset')->insertGetId([
                'source'       => 'manual',
                'display_name' => $data['name'],
                'hostname'     => $data['name'],
                'type'         => $data['type'],
                'ip'           => $data['ip'] ?? null,
                'status'       => $data['criticity'],
                'description'  => $data['notes'] ?? null,
                'created_at'   => now(),
                'updatedat'    => now(),
            ],'id_asset');

            return response()->json([
                'message'  => 'Ativo criado com sucesso',
                'id_asset' => $id,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar ativo manual', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Erro interno ao criar ativo',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}