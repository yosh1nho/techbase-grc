<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RbacController extends Controller
{
    // =========================================================================
    // GET /api/rbac/roles
    // Lista todos os papéis com o número de permissões e utilizadores
    // =========================================================================
    public function roles(): JsonResponse
    {
        $roles = DB::table('role as r')
            ->select('r.id_role', 'r.name', 'r.description', 'r.is_active', 'r.createdat')
            ->orderBy('r.name')
            ->get()
            ->map(function ($r) {
                $perms = DB::table('role_permission as rp')
                    ->join('permission as p', 'p.id_permission', '=', 'rp.id_permission')
                    ->where('rp.id_role', $r->id_role)
                    ->pluck('p.key')
                    ->toArray();

                $userCount = DB::table('userrole')
                    ->where('id_role', $r->id_role)
                    ->where('status', 'active')
                    ->count();

                return [
                    'id'          => $r->id_role,
                    'name'        => $r->name,
                    'description' => $r->description,
                    'is_active'   => (bool) $r->is_active,
                    'created_at'  => $r->createdat,
                    'permissions' => $perms,
                    'user_count'  => $userCount,
                ];
            });

        return response()->json($roles);
    }

    // =========================================================================
    // POST /api/rbac/roles
    // Criar novo papel
    // Body: { name, description, permissions[] }
    // =========================================================================
    public function createRole(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255', 'unique:role,name'],
            'description'   => ['nullable', 'string', 'max:500'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permission,key'],
        ]);

        DB::beginTransaction();
        try {
            $roleId = DB::table('role')->insertGetId([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active'   => 1,
                'createdat'   => now(),
                'updatedat'   => now(),
            ], 'id_role');

            if (!empty($data['permissions'])) {
                $this->syncPermissions($roleId, $data['permissions']);
            }

            DB::commit();

            $this->auditLog('role.create', 'role', $roleId, null, [
                'name' => $data['name'],
                'permissions_count' => count($data['permissions'] ?? []),
            ]);

            return response()->json(['success' => true, 'id' => $roleId], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PUT /api/rbac/roles/{id}
    // Editar papel existente (nome, descrição, permissões)
    // =========================================================================
    public function updateRole(Request $request, int $id): JsonResponse
    {
        $role = DB::table('role')->where('id_role', $id)->first();
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Papel não encontrado.'], 404);
        }

        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:500'],
            'is_active'     => ['sometimes', 'boolean'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permission,key'],
        ]);

        DB::beginTransaction();
        try {
            $update = ['updatedat' => now()];
            if (isset($data['name']))        $update['name']        = $data['name'];
            if (isset($data['description'])) $update['description'] = $data['description'];
            if (isset($data['is_active']))   $update['is_active']   = $data['is_active'] ? 1 : 0;

            DB::table('role')->where('id_role', $id)->update($update);

            if (array_key_exists('permissions', $data)) {
                $this->syncPermissions($id, $data['permissions'] ?? []);
            }

            DB::commit();

            $this->auditLog('role.update', 'role', $id, null, $update);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // DELETE /api/rbac/roles/{id}
    // Apagar papel (só se não tiver utilizadores activos)
    // =========================================================================
    public function deleteRole(int $id): JsonResponse
    {
        $role = DB::table('role')->where('id_role', $id)->first();
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Papel não encontrado.'], 404);
        }

        $activeUsers = DB::table('userrole')
            ->where('id_role', $id)
            ->where('status', 'active')
            ->count();

        if ($activeUsers > 0) {
            return response()->json([
                'success' => false,
                'message' => "Não é possível apagar: {$activeUsers} utilizador(es) activo(s) com este papel.",
            ], 409);
        }

        DB::beginTransaction();
        try {
            DB::table('role_permission')->where('id_role', $id)->delete();
            DB::table('userrole')->where('id_role', $id)->delete();
            DB::table('role')->where('id_role', $id)->delete();
            DB::commit();

            $this->auditLog('role.delete', 'role', $id, ['name' => $role->name], null);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PATCH /api/rbac/roles/{id}/toggle
    // Activar / desactivar papel
    // =========================================================================
    public function toggleRole(int $id): JsonResponse
    {
        $role = DB::table('role')->where('id_role', $id)->first();
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Papel não encontrado.'], 404);
        }

        $newState = $role->is_active ? 0 : 1;
        DB::table('role')->where('id_role', $id)->update([
            'is_active' => $newState,
            'updatedat' => now(),
        ]);

        $this->auditLog('role.toggle', 'role', $id, null, ['is_active' => $newState]);

        return response()->json(['success' => true, 'is_active' => (bool) $newState]);
    }

    // =========================================================================
    // GET /api/rbac/permissions
    // Lista todas as permissões da BD
    // =========================================================================
    public function permissions(): JsonResponse
    {
        $perms = DB::table('permission')
            ->select('id_permission', 'key', 'group', 'description')
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id_permission,
                'key'         => $p->key,
                'group'       => $p->group,
                'description' => $p->description,
            ]);

        return response()->json($perms);
    }

    // =========================================================================
    // POST /api/rbac/users
    // Criar um novo utilizador com Password Encriptada (Bcrypt)
    // =========================================================================
    public function createUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:User,email'], // Garante que o email não repete
            'password' => ['required', 'string', 'min:6'],
            'role_id'  => ['required', 'integer', 'exists:role,id_role'],
            'status'   => ['required', 'in:active,disabled']
        ]);

        DB::beginTransaction();
        try {
            // 1. Criar o Utilizador e usar o Hash para a password
            $userId = DB::table('User')->insertGetId([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']), // A magia do Bcrypt
                'createdat' => now()
            ], 'id_user');

            // 2. Associar a Role escolhida
            DB::table('userrole')->insert([
                'id_user'   => $userId,
                'id_role'   => $data['role_id'],
                'status'    => $data['status'],
                'createdat' => now()
            ]);

            DB::commit();

            // Guardar no Log de Auditoria usando o teu helper interno
            $this->auditLog('user.create', 'user', $userId, null, [
                'email'   => $data['email'],
                'role_id' => $data['role_id']
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    // =========================================================================
    // GET /api/rbac/users
    // Lista utilizadores com o seu papel activo
    // =========================================================================
    public function users(): JsonResponse
    {
        $users = DB::table('User as u')
            ->leftJoin('userrole as ur', function ($join) {
                $join->on('ur.id_user', '=', 'u.id_user')
                     ->where('ur.status', '=', 'active');
            })
            ->leftJoin('role as r', 'r.id_role', '=', 'ur.id_role')
            ->select([
                'u.id_user',
                'u.email',
                'u.name',
                'ur.id_role',
                'r.name as role_name',
                'ur.status as user_role_status',
                'ur.createdat as assigned_at',
            ])
            ->orderBy('u.name')
            ->get()
            ->map(fn($u) => [
                'id'         => $u->id_user,
                'name'       => $u->name ?? explode('@', $u->email)[0],
                'email'      => $u->email,
                'role_id'    => $u->id_role,
                'role_name'  => $u->role_name,
                'status'     => $u->user_role_status ?? 'active',
                'assigned_at'=> $u->assigned_at,
            ]);

        return response()->json($users);
    }

    // =========================================================================
    // PUT /api/rbac/users/{id}/role
    // Atribuir (ou alterar) papel a um utilizador
    // Body: { role_id }
    // =========================================================================
    public function assignRole(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:role,id_role'],
        ]);

        $user = DB::table('User')->where('id_user', $userId)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilizador não encontrado.'], 404);
        }

        DB::beginTransaction();
        try {
            // Desactivar todas as atribuições actuais
            DB::table('userrole')
                ->where('id_user', $userId)
                ->update(['status' => 'inactive']);

            // Criar (ou reactivar) a nova atribuição
            $existing = DB::table('userrole')
                ->where('id_user', $userId)
                ->where('id_role', $data['role_id'])
                ->first();

            if ($existing) {
                DB::table('userrole')
                    ->where('id_user', $userId)
                    ->where('id_role', $data['role_id'])
                    ->update(['status' => 'active']);
            } else {
                DB::table('userrole')->insert([
                    'id_user'   => $userId,
                    'id_role'   => $data['role_id'],
                    'status'    => 'active',
                    'createdat' => now(),
                ]);
            }

            DB::commit();

            $roleName = DB::table('role')->where('id_role', $data['role_id'])->value('name');
            $this->auditLog('user.role_assign', 'user', $userId, null, [
                'role_id'   => $data['role_id'],
                'role_name' => $roleName,
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PATCH /api/rbac/users/{id}/toggle
    // Activar / desactivar todas as atribuições de um utilizador
    // =========================================================================
    public function toggleUser(int $userId): JsonResponse
    {
        $user = DB::table('User')->where('id_user', $userId)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilizador não encontrado.'], 404);
        }

        // Verificar estado actual
        $currentStatus = DB::table('userrole')
            ->where('id_user', $userId)
            ->value('status');

        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

        DB::table('userrole')
            ->where('id_user', $userId)
            ->update(['status' => $newStatus]);

        $this->auditLog('user.toggle', 'user', $userId, null, ['status' => $newStatus]);

        return response()->json(['success' => true, 'status' => $newStatus]);
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    /**
     * Sincroniza as permissões de um papel:
     * remove as que já não estão na lista e insere as novas.
     */
    private function syncPermissions(int $roleId, array $permKeys): void
    {
        // Buscar ids das permissões pelo key
        $permIds = DB::table('permission')
            ->whereIn('key', $permKeys)
            ->pluck('id_permission')
            ->toArray();

        // Remover tudo e reinserir (mais simples que diff)
        DB::table('role_permission')->where('id_role', $roleId)->delete();

        if (!empty($permIds)) {
            $rows = array_map(fn($pid) => [
                'id_role'       => $roleId,
                'id_permission' => $pid,
                'createdat'     => now(),
            ], $permIds);

            DB::table('role_permission')->insert($rows);
        }
    }

    /**
     * Regista uma acção no auditLog.
     */
    private function auditLog(
        string  $action,
        string  $entityType,
        int     $entityId,
        ?array  $oldValues,
        ?array  $newValues
    ): void {
        try {
            $userId = session('tb_user.id') ?? null;
            DB::table('auditLog')->insert([
                'actor_user_id' => $userId,
                'action'        => $action,
                'entity_type'   => $entityType,
                'entity_id'     => $entityId,
                'old_values'    => $oldValues ? json_encode($oldValues) : null,
                'new_values'    => $newValues ? json_encode($newValues) : null,
                'ip_address'    => request()->ip(),
                'createdat'     => now(),
            ]);
        } catch (\Exception) {
            // Nunca deixar falha de auditoria impedir a operação principal
        }
    }
}