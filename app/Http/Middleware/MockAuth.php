<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MockAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('tb_user')) {
            return redirect()->route('login');
        }
        return $next($request);

        $user = DB::table('User')->where('id_user', 1)->first(); // Utilizador de teste

        // 1. Ir buscar o papel e as permissões associadas na BD
        $role = DB::table('userrole as ur')
            ->join('role as r', 'ur.id_role', '=', 'r.id_role')
            ->where('ur.id_user', $user->id_user)
            ->select('r.name', 'r.id_role')
            ->first();

        $permissions = DB::table('rolepermission')
            ->where('id_role', $role->id_role)
            ->pluck('permission_key') // Ex: ['assets.view', 'risks.edit']
            ->toArray();

        // 2. Guardar tudo na sessão para consulta rápida
        session(['tb_user' => [
            'id'          => $user->id_user,
            'name'        => $user->name,
            'role'        => $role->name,
            'permissions' => $permissions,
        ]]);
    }
}
