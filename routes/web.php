<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\TreatmentPlanController;
use App\Http\Controllers\TreatmentTaskController;
use App\Http\Controllers\TreatmentCommentController;
use App\Http\Controllers\DocumentController;

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
        'email'    => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $users = [
        'admin@techbase.local'  => ['name' => 'Admin',       'role' => 'Admin',       'password' => 'admin123'],
        'grc@techbase.local'    => ['name' => 'GRC Manager', 'role' => 'GRC Manager', 'password' => 'grc123'],
        'viewer@techbase.local' => ['name' => 'Viewer',      'role' => 'Viewer',      'password' => 'viewer123'],
    ];

    $u = $users[$data['email']] ?? null;
    if (!$u || $u['password'] !== $data['password']) {
        return back()->withErrors(['email' => 'Credenciais inválidas.'])->withInput();
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

    // ── Páginas ───────────────────────────────────────────────────────────────
    Route::get('/dashboard',      fn () => view('pages.dashboard'))->name('dashboard');
    Route::get('/ativos',         fn () => view('pages.assets'))->name('assets');
    Route::get('/documentos',     fn () => view('pages.docs'))->name('docs');
    Route::get('/avaliacoes',     fn () => view('pages.assessments'))->name('assessments');
    Route::get('/riscos',         fn () => view('pages.risks'))->name('risks');
    Route::get('/tratamento',     fn () => view('pages.treatment'))->name('treatment');
    Route::get('/questionario',   fn () => view('pages.questionnaire'))->name('questionnaire');
    Route::get('/chat',           fn () => view('pages.chat'))->name('chat');
    Route::get('/auditoria',      fn () => view('pages.audit'))->name('audit');
    Route::get('/admin/rbac',     fn () => view('pages.rbac'))->name('rbac');
    Route::get('/relatorios-cncs',fn () => view('pages.reports-cncs'))->name('relatorios-cncs');

    // ── Ativos ────────────────────────────────────────────────────────────────
    Route::get('/api/assets',               [AssetController::class, 'index']);
    Route::post('/api/assets/sync-acronis', [AssetController::class, 'syncAcronis']);
    Route::post('/api/assets',              [AssetController::class, 'store']);

    // ── Chat ──────────────────────────────────────────────────────────────────
    Route::post('/chat/ask', [ChatController::class, 'ask'])->middleware('throttle:60,1');

    // ── Riscos ────────────────────────────────────────────────────────────────
    Route::get('/api/risks',              [RiskController::class, 'index']);
    Route::post('/api/risks',             [RiskController::class, 'store']);
    Route::put('/api/risks/{id}',         [RiskController::class, 'update']);
    Route::delete('/api/risks/{id}',      [RiskController::class, 'destroy']);
    Route::post('/api/risks/from-alert',  [RiskController::class, 'createFromAlert']);

    // ── Planos de tratamento ──────────────────────────────────────────────────
    Route::get('/api/treatment-plans',         [TreatmentPlanController::class, 'index']);
    Route::post('/api/treatment-plans',        [TreatmentPlanController::class, 'store']);
    Route::put('/api/treatment-plans/{id}',    [TreatmentPlanController::class, 'update']);
    Route::delete('/api/treatment-plans/{id}', [TreatmentPlanController::class, 'destroy']);

    // ── Tarefas (nested sob o plano) ──────────────────────────────────────────
    // GET    /api/treatment-plans/{planId}/tasks          → index (listar tarefas do plano)
    // POST   /api/treatment-plans/{planId}/tasks          → store (criar tarefa)
    // PUT    /api/treatment-plans/{planId}/tasks/{taskId} → update (editar / mudar status)
    // DELETE /api/treatment-plans/{planId}/tasks/{taskId} → destroy (eliminar)
    Route::get('/api/treatment-plans/{planId}/tasks',
        [TreatmentTaskController::class, 'index']);
    Route::post('/api/treatment-plans/{planId}/tasks',
        [TreatmentTaskController::class, 'store']);
    Route::put('/api/treatment-plans/{planId}/tasks/{taskId}',
        [TreatmentTaskController::class, 'update']);
    Route::delete('/api/treatment-plans/{planId}/tasks/{taskId}',
        [TreatmentTaskController::class, 'destroy']);

    // ── Documentos & Evidências ───────────────────────────────────────────────
    // GET  /api/documents               → listar todos (com ?status=pending|approved)
    // POST /api/documents/upload         → upload directo (framework → aprovado; doc → pending)
    // POST /api/documents/{id}/approve   → aprovar + dispara ingest
    // POST /api/documents/{id}/reject    → rejeitar
    // GET  /api/documents/{id}/download  → download do ficheiro
    Route::get('/api/documents',                      [DocumentController::class, 'index']);
    Route::post('/api/documents/upload',              [DocumentController::class, 'upload']);
    Route::post('/api/documents/{id}/approve',        [DocumentController::class, 'approve']);
    Route::post('/api/documents/{id}/reject',         [DocumentController::class, 'reject']);
    Route::get('/api/documents/{id}/download',        [DocumentController::class, 'download']);

    // ── Comentários (nested sob tarefa) ──────────────────────────────────────
    // GET    /api/tasks/{taskId}/comments          → index
    // POST   /api/tasks/{taskId}/comments          → store (multipart com files[])
    // DELETE /api/tasks/{taskId}/comments/{id}     → destroy
    Route::get('/api/tasks/{taskId}/comments',
        [TreatmentCommentController::class, 'index']);
    Route::post('/api/tasks/{taskId}/comments',
        [TreatmentCommentController::class, 'store']);
    Route::delete('/api/tasks/{taskId}/comments/{commentId}',
        [TreatmentCommentController::class, 'destroy']);

    // ── Anexos ────────────────────────────────────────────────────────────────
    // GET    /api/attachments/{id}/download  → serve o ficheiro
    // POST   /api/attachments/{id}/promote   → promover a documento pendente
    // DELETE /api/attachments/{id}           → soft delete
    Route::get('/api/attachments/{attachmentId}/download',
        [TreatmentCommentController::class, 'download']);
    Route::post('/api/attachments/{attachmentId}/promote',
        [TreatmentCommentController::class, 'promote']);
    Route::delete('/api/attachments/{attachmentId}',
        [TreatmentCommentController::class, 'destroyAttachment']);

    // ── Users (rota provisória) ───────────────────────────────────────────────
    Route::get('/api/users', function () {
        return DB::table('User')->select('id_user', 'name', 'email')->get();
    });

});

// ── Debug ─────────────────────────────────────────────────────────────────────
Route::get('/debug-session', function () { dd(session()->all()); });
Route::get('/_debug/php', function () {
    return response()->json([
        'php_binary'    => PHP_BINARY,
        'php_version'   => PHP_VERSION,
        'loaded_ini'    => php_ini_loaded_file(),
        'curl_cainfo'   => ini_get('curl.cainfo'),
        'openssl_cafile'=> ini_get('openssl.cafile'),
    ]);
});

Route::get('/_debug/ingest-test/{docId}', function ($docId) {
    // 1. Buscar o documento
    $doc = DB::table('document as d')
        ->leftJoin('attachment as a', 'a.id_attachment', '=', 'd.id_attachment')
        ->select(['d.*', 'a.file_path as attach_path', 'a.original_name', 'a.file_name'])
        ->where('d.id_doc', $docId)
        ->first();
 
    if (!$doc) {
        return response()->json(['error' => 'Documento nao encontrado', 'doc_id' => $docId], 404);
    }
 
    $filePath     = $doc->attach_path ?? $doc->file_path;
    $absolutePath = \Illuminate\Support\Facades\Storage::disk('attachments')->path($filePath);
 
    // 2. Verificar se o ficheiro existe no disco
    if (!file_exists($absolutePath)) {
        return response()->json([
            'error'         => 'Ficheiro nao existe no disco',
            'file_path_db'  => $filePath,
            'absolute_path' => $absolutePath,
            'disk_root'     => storage_path('app/private/attachments'),
        ]);
    }
 
    // 3. Verificar se o script Python existe
    $scriptPath = base_path('rag/ingest_pinecone_only.py');
    if (!file_exists($scriptPath)) {
        return response()->json([
            'error'       => 'Script Python nao encontrado',
            'script_path' => $scriptPath,
            'base_path'   => base_path(),
        ]);
    }
 
    // 4. Testar se o Python esta disponivel
    $pythonTest = shell_exec('python --version 2>&1');
    $python3Test = shell_exec('python3 --version 2>&1');
    $pythonEnv  = env('PYTHON_BIN', 'nao definido');
 
    // 5. Tentar executar com proc_open directamente
    $pythonBin = env('PYTHON_BIN', 'python');
    $namespace = (string) $docId;
 
    $args = [
        $pythonBin,
        $scriptPath,
        '--file',     $absolutePath,
        '--tenant',   $namespace,
        '--doc-id',   $namespace,
        '--doc-name', $doc->original_name ?? $doc->file_name ?? 'teste',
        '--profile',  'auto',
    ];
 
    $cmd = implode(' ', array_map('escapeshellarg', $args));
 
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
 
    $envVars = array_merge($_ENV, [
        'PINECONE_API_KEY' => env('PINECONE_API_KEY', ''),
        'PINECONE_INDEX'   => env('PINECONE_INDEX', ''),
    ]);
 
    $process = proc_open($cmd, $descriptors, $pipes, null, $envVars);
 
    if (!is_resource($process)) {
        return response()->json([
            'error'      => 'proc_open falhou — nao conseguiu iniciar o processo',
            'cmd'        => $cmd,
            'python_bin' => $pythonBin,
        ]);
    }
 
fclose($pipes[0]);
    $stdout   = stream_get_contents($pipes[1]);
    $stderr   = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    // Forçar UTF-8 — Windows pode devolver CP1252/Latin-1 no stderr
    $toUtf8 = fn($s) => mb_detect_encoding($s, 'UTF-8', true)
        ? $s
        : mb_convert_encoding($s, 'UTF-8', 'Windows-1252');

    $stdout = $toUtf8($stdout ?: '');
    $stderr = $toUtf8($stderr ?: '');

    $result = null;
    foreach (array_reverse(explode("\n", trim($stdout))) as $line) {
        if (str_starts_with(trim($line), '{')) {
            $result = json_decode(trim($line), true);
            break;
        }
    }

    return response()->json([
        'php_version'        => PHP_VERSION,
        'python_env'         => $pythonEnv,
        'python_test'        => $toUtf8(trim($pythonTest ?? 'sem output')),
        'python3_test'       => $toUtf8(trim($python3Test ?? 'sem output')),
        'PINECONE_KEY_set'   => !empty(env('PINECONE_API_KEY')),
        'PINECONE_INDEX_set' => !empty(env('PINECONE_INDEX')),
        'QUEUE_CONNECTION'   => env('QUEUE_CONNECTION', 'nao definido'),
        'file_path_db'       => $filePath,
        'absolute_path'      => $absolutePath,
        'file_exists'        => file_exists($absolutePath),
        'script_exists'      => file_exists($scriptPath),
        'cmd'                => $cmd,
        'exit_code'          => $exitCode,
        'stdout'             => $stdout,
        'stderr'             => $stderr,
        'parsed_result'      => $result,
    ]);
});
 
