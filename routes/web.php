<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

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
