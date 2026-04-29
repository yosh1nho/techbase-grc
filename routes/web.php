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
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CncsReportController;
use App\Http\Controllers\DocumentGeneratorController;
use App\Http\Controllers\DocumentAnalyserController;
use App\Http\Controllers\RbacController;
use Illuminate\Support\Facades\Hash;
use App\Http\Middleware\CheckPermission;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\IncidentController;
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
// Login (Real usando a Base de Dados)
Route::post('/login', function (Request $request) {
    // 1. Validar as credenciais submetidas no formulário
    $data = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    // 2. Procurar o utilizador na tabela 'User' pelo email
    $user = DB::table('User')->where('email', $data['email'])->first();

    // 3. Verificar se o utilizador existe e a password bate certo
    if ($user && Hash::check($data['password'], $user->password)) {

        // 4. Buscar Role ativa (agora pedimos também o id_role!)
        $role = DB::table('userrole')
            ->join('role', 'userrole.id_role', '=', 'role.id_role')
            ->where('userrole.id_user', $user->id_user)
            ->where('userrole.status', 'active')
            ->select('role.id_role', 'role.name') // 👈 IMPORTANTE: precisamos do id_role
            ->first();

        // 5. Buscar o array de permissões associadas a esta Role
        $permissions = [];
        if ($role) {
            $permissions = DB::table('role_permission')
                ->join('permission', 'role_permission.id_permission', '=', 'permission.id_permission')
                ->where('role_permission.id_role', $role->id_role)
                ->pluck('permission.key')
                ->toArray();
        }

        // 6. Guardar na sessão (agora com as PERMISSIONS!)
        session([
            'tb_user' => [
                'id'          => $user->id_user,
                'email'       => $user->email,
                'name'        => $user->name,
                'role'        => $role ? $role->name : 'Sem Acesso',
                'permissions' => $permissions, // 👈 O SEGREDO ESTAVA AQUI!
            ]
        ]);

        return redirect()->route('dashboard');
    }

    // 7. Erro de credenciais
    return back()->withErrors([
        'email' => 'As credenciais fornecidas não estão corretas.',
    ])->onlyInput('email');
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
    Route::get('/ativos',         fn () => view('pages.assets'))->name('assets')->middleware(CheckPermission::class.':assets.view');
    Route::get('/documentos',     fn () => view('pages.docs'))->name('docs')->middleware(CheckPermission::class.':docs.view');
    Route::get('/avaliacoes',     fn () => view('pages.assessments'))->name('assessments')->middleware(CheckPermission::class.':assessments.view');
    Route::get('/riscos',         fn () => view('pages.risks'))->name('risks')->middleware(CheckPermission::class.':risk.view');
    Route::get('/tratamento',     fn () => view('pages.treatment'))->name('treatment')->middleware(CheckPermission::class.':treatment.view');
    Route::get('/questionario',   fn () => view('pages.questionnaire'))->name('questionnaire')->middleware(CheckPermission::class.':questionnaire.view');
    Route::get('/chat',           fn () => view('pages.chat'))->name('chat')->middleware(CheckPermission::class.':chat.use');
    Route::get('/auditoria',      fn () => view('pages.audit'))->name('audit')->middleware(CheckPermission::class.':audit.view');
    Route::get('/admin/rbac',     fn () => view('pages.rbac'))->name('rbac')->middleware(CheckPermission::class.':rbac.manage');
    Route::get('/relatorios-cncs',fn () => view('pages.reports-cncs'))->name('relatorios-cncs');

    // ── Ativos ────────────────────────────────────────────────────────────────
    Route::get('/api/assets',               [AssetController::class, 'index'])->middleware(CheckPermission::class.':assets.view');
    Route::post('/api/assets/sync-wazuh', [AssetController::class, 'syncWazuh'])->middleware(CheckPermission::class.':assets.view');
    Route::post('/api/assets',              [AssetController::class, 'store'])->middleware(CheckPermission::class.':assets.create');
    Route::get('/api/asset-tags',                          [AssetController::class, 'tags'])->middleware(CheckPermission::class.':assets.view');
    Route::post('/api/assets/{id}/tags',                   [AssetController::class, 'addTags'])->middleware(CheckPermission::class.':assets.edit');
    Route::delete('/api/assets/{id}/tags/{tagId}',         [AssetController::class, 'removeTag'])->middleware(CheckPermission::class.':assets.edit');
    Route::patch('/api/assets/{id}/criticality',           [AssetController::class, 'updateCriticality'])->middleware(CheckPermission::class.':assets.edit');
    Route::get('/api/assets/{id}/analyses', [AssetController::class, 'getAnalyses'])->middleware(CheckPermission::class.':assets.view');
    Route::post('/api/assets/{id}/analyze', [AssetController::class, 'analyze'])->middleware(CheckPermission::class.':assets.edit');
    Route::put('/api/assets/{id}', [AssetController::class, 'update']);
Route::patch('/api/assets/{id}/risk', [AssetController::class, 'patch']);    
    // ── Chat ──────────────────────────────────────────────────────────────────
    Route::post('/chat/ask', [ChatController::class, 'ask'])->middleware('throttle:60,1');

    // ── Riscos ────────────────────────────────────────────────────────────────
    Route::get('/api/risks',              [RiskController::class, 'index'])->middleware(CheckPermission::class.':risk.view');
    Route::post('/api/risks',             [RiskController::class, 'store'])->middleware(CheckPermission::class.':risk.create');
    Route::put('/api/risks/{id}',         [RiskController::class, 'update'])->middleware(CheckPermission::class.':risk.edit');
    Route::delete('/api/risks/{id}',      [RiskController::class, 'destroy'])->middleware(CheckPermission::class.':risk.edit');

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
    Route::get('/api/documents',                      [DocumentController::class, 'index'])->middleware(CheckPermission::class.':docs.view');
    Route::post('/api/documents/upload',              [DocumentController::class, 'upload'])->middleware(CheckPermission::class.':docs.upload');
    Route::post('/api/documents/{id}/approve',        [DocumentController::class, 'approve'])->middleware(CheckPermission::class.':docs.approve_links');
    Route::post('/api/documents/{id}/reject',         [DocumentController::class, 'reject'])->middleware(CheckPermission::class.':docs.approve_links');
    Route::post('/api/documents/{id}/obsolete',       [DocumentController::class, 'obsolete'])->middleware(CheckPermission::class.':frameworks.edit');
    Route::get('/api/documents/{id}/preview', [DocumentController::class, 'preview'])->middleware(CheckPermission::class.':docs.view');
    Route::get('/api/documents/{id}/download',        [DocumentController::class, 'download'])->middleware(CheckPermission::class.':docs.view');
    Route::post('/api/documents/{id}/delete', [DocumentController::class, 'delete'])->middleware(CheckPermission::class.':docs.edit');
    Route::post('/api/documents/{id}/re-upload',           [DocumentController::class, 'reUpload'])->middleware(CheckPermission::class.':docs.upload');
    Route::get('/api/document-generator/templates', [DocumentGeneratorController::class, 'templates'])->middleware(CheckPermission::class.':docs.view');
    Route::post('/api/document-generator/generate',  [DocumentGeneratorController::class, 'generate'])->middleware(CheckPermission::class.':docs.view');
    // Extração de texto para análise Gemini
    Route::get('/api/documents/{id}/extract-text',  [DocumentController::class, 'extractText'])->middleware(CheckPermission::class.':docs.view');

    // Análise Gemini — IMPORTANTE: esta rota tem de vir ANTES de {id}/xxx para não colidir
    Route::post('/api/documents/gemini-analyse',    [DocumentController::class, 'geminiAnalyse'])->middleware(CheckPermission::class.':docs.view');
    
    
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



    //COMPLIANCE
    // ── Nova página ───────────────────────────────────────────────────────────────
    Route::get('/compliance', fn () => view('pages.compliance'))->name('compliance')->middleware(CheckPermission::class.':compliance.view');

    Route::get('/api/compliance', [ComplianceController::class, 'index'])->middleware(CheckPermission::class.':compliance.view');

    // KPIs para o dashboard (leve, sem estrutura de grupos/controlos)
    // GET /api/compliance/summary
    Route::get('/api/compliance/summary', [ComplianceController::class, 'summary'])->middleware(CheckPermission::class.':compliance.view');

    // Avaliar um controlo (criar/actualizar assessment)
    // POST /api/compliance/assess
    // Body: { control_id, status, notes?, evidence_link? }
    Route::post('/api/compliance/assess', [ComplianceController::class, 'assess'])->middleware(CheckPermission::class.':compliance.manage');

    // Histórico de avaliações de um controlo
    // GET /api/compliance/{controlId}/history
    Route::get('/api/compliance/{controlId}/history', [ComplianceController::class, 'history'])->middleware(CheckPermission::class.':compliance.view');

    // Listar documentos de evidência ligados a um controlo
    // GET /api/compliance/{controlId}/evidences
    Route::get('/api/compliance/{controlId}/evidences', [ComplianceController::class, 'evidences'])->middleware(CheckPermission::class.':compliance.view');

    // Ligar um documento a um controlo como evidência
    // POST /api/compliance/{controlId}/link-doc
    // Body: { doc_id }
    Route::post('/api/compliance/{controlId}/link-doc', [ComplianceController::class, 'linkDoc'])->middleware(CheckPermission::class.':compliance.manage');

    // Remover ligação documento ↔ controlo
    // DELETE /api/compliance/{controlId}/link-doc/{docId}
    Route::delete('/api/compliance/{controlId}/link-doc/{docId}', [ComplianceController::class, 'unlinkDoc'])->middleware(CheckPermission::class.':compliance.manage');

    // Lista plana de controlos para os selects (dropdowns)
// Lista ESTRUTURADA de controlos (Framework -> Grupo -> Controlo)
    Route::get('/api/controls-structured', function () {
        return DB::table('framework_control as fc')
            ->join('framework_group as fg', 'fc.id_group', '=', 'fg.id_group')
            ->join('framework as f', 'fg.id_framework', '=', 'f.id_framework')
            ->select(
                'f.name as framework_name',
                'fg.name as group_name',
                'fc.control_code as key', 
                'fc.description as title', 
                'fc.guidance as desc'
            )
            ->orderBy('f.name')
            ->orderBy('fg.name')
            ->orderBy('fc.control_code')
            ->get();
    })->middleware(CheckPermission::class.':compliance.view');

    //Dashboard ───────────────────────────────────────────────
    // KPIs completos (riscos + planos + compliance) — chamado pelo dashboard.js
    Route::get('/api/dashboard',             [DashboardController::class, 'index']);
 
    // Endpoints individuais (para refresh independente no futuro)
    Route::get('/api/dashboard/risks',       [DashboardController::class, 'risks']);
    Route::get('/api/dashboard/treatments',  [DashboardController::class, 'treatments']);
    Route::get('/api/dashboard/compliance',  [DashboardController::class, 'compliance']);
    Route::get('/api/dashboard/wazuh-alerts', [DashboardController::class, 'getWazuhAlerts']);
    Route::post('/api/dashboard/wazuh-alerts/{id}/analyze', [DashboardController::class, 'analyzeWazuhAlert'])->middleware(CheckPermission::class.':assets.edit');
 

    // Relatórios ─────────────────────────────────────────────────────────────
    // Listagem e CRUD de relatórios
    Route::get('/api/cncs-reports',               [CncsReportController::class, 'index']);
    Route::post('/api/cncs-reports',              [CncsReportController::class, 'store']);
    Route::get('/api/cncs-reports/report-data',   [CncsReportController::class, 'reportData']);
    Route::get('/api/cncs-reports/compliance-table', [CncsReportController::class, 'complianceTable']);
    Route::get('/api/cncs-reports/{id}',          [CncsReportController::class, 'show']);
    Route::put('/api/cncs-reports/{id}',          [CncsReportController::class, 'update']);
    Route::post('/api/cncs-reports/{id}/submit',  [CncsReportController::class, 'submit']);
    Route::delete('/api/cncs-reports/{id}',       [CncsReportController::class, 'destroy']);

    // ── Users (rota provisória) ───────────────────────────────────────────────
    Route::get('/api/users', function () {
        return DB::table('User')->select('id_user', 'name', 'email')->get();
    });

    Route::post('/api/documents/{id}/analyse', [DocumentAnalyserController::class, 'analyse']);
    

});

// ── RBAC ─────────────────────────────────────────────────────────
Route::prefix('api/rbac')->group(function () {
    Route::get('/roles', [RbacController::class, 'roles']);
    Route::post('/roles', [RbacController::class, 'createRole']);
    Route::put('/roles/{id}', [RbacController::class, 'updateRole']);
    Route::delete('/roles/{id}', [RbacController::class, 'deleteRole']);
    Route::patch('/roles/{id}/toggle', [RbacController::class, 'toggleRole']);
    
    Route::get('/permissions', [RbacController::class, 'permissions']);
    
    Route::get('/users', [RbacController::class, 'users']);
    Route::post('/users', [RbacController::class, 'createUser']); 
    Route::put('/users/{id}/role', [RbacController::class, 'assignRole']);
    Route::patch('/users/{id}/toggle', [RbacController::class, 'toggleUser']);
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

// ── Avaliações (Assessments) ─────────────────────────────────────────────────
Route::get('/api/assessments', [AssessmentController::class, 'index'])
    ->middleware(CheckPermission::class . ':assessments.view');

Route::post('/api/assessments', [AssessmentController::class, 'run'])
    ->middleware(CheckPermission::class . ':assessments.run');

Route::post('/api/assessments/{id}/evaluate', [AssessmentController::class, 'evaluate'])
    ->middleware(CheckPermission::class . ':assessments.view');

Route::patch('/api/assessments/{id}/close', [AssessmentController::class, 'close'])
    ->middleware(CheckPermission::class . ':assessments.view');

Route::get('/api/assessments/latest/{assetId}', [AssessmentController::class, 'getLatest'])
    ->middleware(CheckPermission::class . ':assessments.view');


Route::get('/api/assessments/kpis', [AssessmentController::class, 'kpis'])
    ->middleware(CheckPermission::class . ':assessments.view');

Route::get('/api/frameworks-list', function() {
    return DB::table('framework')
        ->select('id_framework as id', 'name')
        // ->whereNull('deleted_at') 
        ->get();
})->middleware(CheckPermission::class . ':assessments.view');

Route::get('/api/assessments/{id}', [AssessmentController::class, 'show'])
    ->middleware(CheckPermission::class . ':assessments.view');

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

// ─────────────────────────────────────────────────────────────────────────────
// INCIDENTES - PÁGINA VISUAL
// ─────────────────────────────────────────────────────────────────────────────
Route::get('/incidentes', function () {
    return view('pages.incidents');
})->name('incidents');

// ─────────────────────────────────────────────────────────────────────────────
// INCIDENTES - ROTAS DE API (Backend)
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('api/incidents')->group(function () {
    // Configurações da Empresa (CISO, NIF, etc)
    Route::get ('/company-settings', [\App\Http\Controllers\IncidentController::class, 'companySettings']);
    Route::put ('/company-settings', [\App\Http\Controllers\IncidentController::class, 'updateCompanySettings']);
    
    // Gestão do Incidente em si
    Route::get    ('/',         [\App\Http\Controllers\IncidentController::class, 'index']);
    Route::post   ('/',         [\App\Http\Controllers\IncidentController::class, 'store']);
    Route::get    ('/{id}',     [\App\Http\Controllers\IncidentController::class, 'show']);
    Route::put    ('/{id}',     [\App\Http\Controllers\IncidentController::class, 'update']);
    Route::delete ('/{id}',     [\App\Http\Controllers\IncidentController::class, 'destroy']);
    
    // Ações de Estado
    Route::post   ('/{id}/contain', [\App\Http\Controllers\IncidentController::class, 'contain']);
    Route::post   ('/{id}/resolve', [\App\Http\Controllers\IncidentController::class, 'resolve']);
    Route::post   ('/{id}/close',   [\App\Http\Controllers\IncidentController::class, 'close']);
    Route::post   ('/{id}/reopen',  [\App\Http\Controllers\IncidentController::class, 'reopen']);
    
    // Gerar Notificação 24h
    Route::post   ('/{id}/reports', [\App\Http\Controllers\IncidentController::class, 'createReport']);
});
 
