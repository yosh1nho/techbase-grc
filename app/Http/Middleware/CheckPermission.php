<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
public function handle(Request $request, Closure $next, string $permission): Response
    {
        // 1. Extração robusta do array de permissões (igual ao que fizemos no Blade)
        $tbUser = session('tb_user');
        $userPerms = (is_array($tbUser) && isset($tbUser['permissions'])) ? (array) $tbUser['permissions'] : [];

        // 2. Se a permissão não existir no array do utilizador
        if (!in_array($permission, $userPerms, true)) {
            
            if ($request->expectsJson() || $request->is('api/*')) {
                // Devolve o erro e a lista de permissões atuais para ajudar a debugar!
                return response()->json([
                    'success' => false, 
                    'message' => 'Acesso negado. Falta a permissão: ' . $permission,
                    'tuas_permissoes' => $userPerms
                ], 403);
            }

            abort(403, 'Acesso Negado. Não tens a permissão necessária (' . $permission . ').');
        }

        return $next($request);
    }
}