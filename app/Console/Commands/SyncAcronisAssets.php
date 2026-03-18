<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAcronisAssets extends Command
{

    protected $signature = 'acronis:sync-assets';
    protected $description = 'Sincroniza ativos com Acronis';

    public function handle()
    {

        try {

            $response = Http::withHeaders([
                'Authorization' => 'Bearer acronis_fake_jwt_token_998877'
            ])->get('http://127.0.0.1:9999/resource_management/v4/resources');


            if (!$response->ok()) {

                $this->error("Erro ao acessar API Acronis: ".$response->status());

                Log::error("Acronis API error", [
                    "status" => $response->status(),
                    "body" => $response->body()
                ]);

                return Command::FAILURE;
            }


            $resources = $response->json()['items'] ?? [];

            $count = 0;

            foreach ($resources as $r) {

                DB::table('asset')->updateOrInsert(

                    ['id_acronis' => $r['id']],

                    [

                        'source' => 'acronis',

                        'display_name' => $r['name'] ?? null,

                        'hostname' => $r['host']['hostname'] ?? null,
                        'domain' => $r['host']['domain'] ?? null,

                        'ip' => $r['network']['ip'] ?? null,
                        'mac_address' => $r['network']['mac'] ?? null,

                        'type' => $r['type'] ?? null,

                        'os_name' => $r['os']['name'] ?? null,
                        'os_version' => $r['os']['version'] ?? null,
                        'os_build' => $r['os']['build'] ?? null,
                        'os_arch' => $r['os']['arch'] ?? null,
                        'os_patch_level' => $r['os']['patch_level'] ?? null,

                        'agent_status' => $r['agent']['status'] ?? null,
                        'agent_version' => $r['agent']['version'] ?? null,

                        'backup_enabled' => (int) ($r['protection']['backup_enabled'] ?? 0),
                        'antimalware_enabled' => (int) ($r['protection']['antimalware_enabled'] ?? 0),
                        'patch_mgmt_enabled' => (int) ($r['protection']['patch_management_enabled'] ?? 0),

                        'acronis_tenant_id' => $r['tenant_id'] ?? null,

                        'updatedat' => now(),

                    ]
                );

                $count++;
            }

            $this->info("Acronis sync concluído. Assets sincronizados: ".$count);

            Log::info("Acronis sync executado", [
                "assets_synced" => $count
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {

            $this->error("Erro interno ao sincronizar Acronis");

            Log::error("Sync Acronis falhou", [
                "error" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine()
            ]);

            return Command::FAILURE;
        }

    }
}