<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Regista uma alteração na trilha de auditoria (Audit Trail).
     */
    public static function log(string $action, string $entityType, int $entityId, ?array $oldValues = null, ?array $newValues = null): void
    {
        $deltaOld = [];
        $deltaNew = [];

        // Se for um UPDATE, comparamos os arrays para descobrir o "Delta"
        if ($action === 'UPDATE' && $oldValues && $newValues) {
            foreach ($newValues as $key => $newValue) {
                // Se a chave existir no array antigo e o valor for diferente
                if (array_key_exists($key, $oldValues) && $oldValues[$key] != $newValue) {
                    $deltaOld[$key] = $oldValues[$key];
                    $deltaNew[$key] = $newValue;
                }
            }
            
            // Se foi chamado um UPDATE mas os dados são iguais, ignoramos (não polui o log)
            if (empty($deltaNew)) {
                return;
            }
        } else {
            // Se for CREATE ou DELETE, guardamos tudo o que foi passado
            $deltaOld = $oldValues;
            $deltaNew = $newValues;
        }

        try {
            DB::table('auditlog')->insert([
                'actor_user_id' => session('tb_user.id') ?? 1,
                'action'        => $action,
                'entity_type'   => $entityType,
                'entity_id'     => $entityId,
                'old_values'    => $deltaOld ? json_encode($deltaOld, JSON_UNESCAPED_UNICODE) : null,
                'new_values'    => $deltaNew ? json_encode($deltaNew, JSON_UNESCAPED_UNICODE) : null,
                'ip_address'    => Request::ip(),
                'createdat'     => now()
            ]);
        } catch (\Exception $e) {
            \Log::error("Erro no AuditService: " . $e->getMessage());
        }
    }
}