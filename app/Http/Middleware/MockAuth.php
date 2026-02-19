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
    }
}
