<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// ========= Auth mock (sessão) =========
Route::get('/', function () {
    if (session()->has('tb_user')) {
        return redirect()->route('dashboard');
    }
    return view('pages.login');
})->name('login');

Route::get('/debug-session', function () {
    dd(session()->all());
});


// Login (mock)
Route::post('/login', function (Request $request) {
    $data = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    // ✅ mock simples (depois liga no DB + RBAC)
    $users = [
        'admin@techbase.local' => ['name' => 'Admin', 'role' => 'Admin', 'password' => 'admin123'],
        'grc@techbase.local'   => ['name' => 'GRC Manager', 'role' => 'GRC Manager', 'password' => 'grc123'],
        'viewer@techbase.local'=> ['name' => 'Viewer', 'role' => 'Viewer', 'password' => 'viewer123'],
    ];

    $u = $users[$data['email']] ?? null;
    if (!$u || $u['password'] !== $data['password']) {
        return back()
            ->withErrors(['email' => 'Credenciais inválidas.'])
            ->withInput();
    }

    session(['tb_user' => [
        'email' => $data['email'],
        'name'  => $u['name'],
        'role'  => $u['role'],
    ]]);

    return redirect()->route('dashboard');
})->name('login.post');

// Logout
Route::post('/logout', function () {
    session()->forget('tb_user');
    return redirect()->route('login');
})->name('logout');


// ========= Área protegida =========
Route::middleware('mock.auth')->group(function () {

    Route::get('/dashboard', fn () => view('pages.dashboard'))->name('dashboard');
    Route::get('/ativos', fn () => view('pages.assets'))->name('assets');
    Route::get('/documentos', fn () => view('pages.docs'))->name('docs');
    Route::get('/avaliacoes', fn () => view('pages.assessments'))->name('assessments');
    Route::get('/riscos', fn () => view('pages.risks'))->name('risks');
    Route::get('/tratamento', fn () => view('pages.treatment'))->name('treatment');
    Route::get('/questionario', fn () => view('pages.questionnaire'))->name('questionnaire');
    Route::get('/chat', fn () => view('pages.chat'))->name('chat');
    Route::get('/auditoria', fn () => view('pages.audit'))->name('audit');
    Route::get('/admin/rbac', fn () => view('pages.rbac'))->name('rbac');
    Route::get('/relatorios-cncs', fn () => view('pages.reports-cncs'))->name('relatorios-cncs');

});


