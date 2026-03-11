@extends('layouts.app')
@section('title', 'RBAC • Techbase GRC')

@push('styles')
    @vite(['resources/css/pages/rbac.css'])
@endpush

@section('content')
<div class="card">

    {{-- ── Cabeçalho da página ── --}}
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
        <div>
            <h2 style="margin:0 0 4px;">Controlo de Acessos <span style="color:var(--muted); font-weight:400; font-size:14px;">(RBAC)</span></h2>
            <p class="hint" style="margin:0;">Gere utilizadores, papéis e permissões. Alterações refletem no mock instantaneamente.</p>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <span class="chip">Role ativo: <b id="kpiRoleName">—</b></span>
            <span class="chip warn">Permissões: <b id="kpiPermCount">0</b></span>
        </div>
    </div>

    {{-- ── Tabs de navegação ── --}}
    <div class="rbac-tabs" role="tablist">
        <button class="rbac-tab active" type="button" data-tab="users" role="tab" aria-selected="true">
            <i data-lucide="users"></i>
            Utilizadores
            <span class="rbac-tab-count">0</span>
        </button>
        <button class="rbac-tab" type="button" data-tab="roles" role="tab" aria-selected="false">
            <i data-lucide="shield"></i>
            Papéis
            <span class="rbac-tab-count">0</span>
        </button>
        <button class="rbac-tab" type="button" data-tab="matrix" role="tab" aria-selected="false">
            <i data-lucide="grid-3x3"></i>
            Matriz de Permissões
            <span class="rbac-tab-count">0 perms</span>
        </button>
    </div>

    {{-- ════════════════════════════════════════════════
         TAB 1 — Utilizadores
         ════════════════════════════════════════════════ --}}
    <div id="panel-users" class="rbac-panel active" role="tabpanel">
        <div class="section-head">
            <div class="section-head-left">
                <h2>Utilizadores</h2>
                <p>Associa cada utilizador a um papel. O papel define o que pode fazer.</p>
            </div>
            <button id="btnAddUser" class="btn primary" type="button">
                <i data-lucide="user-plus" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i>
                Novo utilizador
            </button>
        </div>

        <div class="filter-row">
            <div class="field">
                <label>Pesquisar</label>
                <input id="userSearch" placeholder="Nome, email…" />
            </div>
            <div class="field" style="max-width:180px;">
                <label>Estado</label>
                <select id="userStatusFilter">
                    <option value="all">Todos</option>
                    <option value="active">Ativo</option>
                    <option value="disabled">Desativado</option>
                </select>
            </div>
        </div>

        <div class="users-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Utilizador</th>
                        <th>Papel</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="usersTbody"></tbody>
            </table>
        </div>

        <p class="hint" style="margin-top:10px;">
            <i data-lucide="info" style="width:13px;height:13px;vertical-align:middle;margin-right:3px;"></i>
            No backend real, a gestão de acessos usa middleware/policies Laravel (Gate/Policy) com RBAC.
        </p>
    </div>

    {{-- ════════════════════════════════════════════════
         TAB 2 — Papéis
         ════════════════════════════════════════════════ --}}
    <div id="panel-roles" class="rbac-panel" role="tabpanel">
        <div class="section-head">
            <div class="section-head-left">
                <h2>Papéis (Roles)</h2>
                <p>Define o que cada papel pode fazer. Clica em "Ver matriz" para editar permissões.</p>
            </div>
            <button id="btnCreateRole" class="btn primary" type="button">
                <i data-lucide="plus" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i>
                Criar papel
            </button>
        </div>

        <div class="filter-row">
            <div class="field">
                <label>Pesquisar papel</label>
                <input id="roleSearch" placeholder="Admin, Auditor…" />
            </div>
            <div class="field" style="max-width:180px;">
                <label>Mostrar</label>
                <select id="roleActiveFilter">
                    <option value="all">Todos</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Desativados</option>
                </select>
            </div>
        </div>

        <div id="rolesGrid" class="roles-grid"></div>

        <p class="hint" style="margin-top:10px;">
            <i data-lucide="info" style="width:13px;height:13px;vertical-align:middle;margin-right:3px;"></i>
            "Clonar" é útil para criar variações de um papel existente (ex.: Auditor → Auditor Externo).
        </p>
    </div>

    {{-- ════════════════════════════════════════════════
         TAB 3 — Matriz de Permissões
         ════════════════════════════════════════════════ --}}
    <div id="panel-matrix" class="rbac-panel" role="tabpanel">
        <div class="section-head">
            <div class="section-head-left">
                <h2>Matriz de Permissões</h2>
                <p>Seleciona um papel e ativa/desativa permissões individuais ou por grupo.</p>
            </div>
        </div>

        {{-- Seletor de papel --}}
        <div class="matrix-role-bar">
            <label for="matrixRolePicker">
                <i data-lucide="shield" style="width:13px;height:13px;vertical-align:middle;margin-right:4px;"></i>
                Papel:
            </label>
            <div class="field">
                <select id="matrixRolePicker"></select>
            </div>
            <div class="field">
                <input id="matrixPermSearch" placeholder="Filtrar permissões…" />
            </div>
        </div>

        <div class="matrix-layout">

            {{-- Esquerda: grupos de permissões --}}
            <div>
                <div id="permGroups"></div>
            </div>

            {{-- Direita: resumo + atalhos + auditoria --}}
            <div class="matrix-sidebar">

                {{-- Resumo do papel --}}
                <div class="sidebar-card">
                    <div class="sidebar-card-title">Resumo do papel</div>
                    <div style="font-size:16px; font-weight:800; margin-bottom:6px;" id="matrixRoleName">—</div>
                    <div id="matrixRoleStatus" style="margin-bottom:10px;"></div>
                    <div class="role-stat-row">
                        <span>Permissões ativas</span>
                        <span class="role-stat-val" id="matrixPermCount">0</span>
                    </div>
                    <div class="role-stat-row">
                        <span>Total disponível</span>
                        <span class="role-stat-val" id="matrixPermTotal">0</span>
                    </div>
                </div>

                {{-- Atalhos --}}
                <div class="sidebar-card">
                    <div class="sidebar-card-title">Atalhos</div>
                    <div class="shortcut-btns">
                        <button id="btnSelectAll" class="btn ok" type="button">
                            <i data-lucide="check-square"></i> Selecionar tudo
                        </button>
                        <button id="btnClearAll" class="btn" type="button">
                            <i data-lucide="square"></i> Limpar tudo
                        </button>
                        <button id="btnResetPerms" class="btn" type="button">
                            <i data-lucide="rotate-ccw"></i> Repor preset
                        </button>
                        <button id="btnDisableRole" class="btn warn" type="button">
                            <i data-lucide="ban"></i> Desativar papel
                        </button>
                    </div>
                </div>

                {{-- Log de auditoria --}}
                <div class="sidebar-card">
                    <div class="sidebar-card-title">
                        <i data-lucide="scroll-text" style="width:12px;height:12px;vertical-align:middle;margin-right:4px;"></i>
                        Auditoria (RNF5)
                    </div>
                    <div id="auditLog" class="audit-log"></div>
                </div>

            </div>
        </div>

        <p class="hint" style="margin-top:14px;">
            <i data-lucide="info" style="width:13px;height:13px;vertical-align:middle;margin-right:3px;"></i>
            Em produção, alterações aqui exigem a permissão <b>rbac.manage</b> e geram trilha de auditoria persistente.
        </p>
    </div>

</div>{{-- /card --}}


{{-- ════════════════════════════════════════════════
     MODAL: Criar / Editar papel
     ════════════════════════════════════════════════ --}}
<div id="roleModal" class="modal-overlay is-hidden" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="roleModalTitle"
         style="width:min(780px,96vw);">

        <div class="modal-header">
            <div>
                <div class="muted" style="font-size:12px; margin-bottom:3px;">RBAC · Papéis</div>
                <div id="roleModalTitle" style="font-size:18px; font-weight:800;">—</div>
            </div>
            <button id="roleModalClose" class="btn" type="button">
                <i data-lucide="x" style="width:14px;height:14px;vertical-align:middle;"></i> Fechar
            </button>
        </div>

        <div style="height:16px;"></div>

        <div class="two">

            {{-- Dados do papel --}}
            <div class="panel">
                <div class="modal-section-title">Dados do papel</div>
                <div class="field">
                    <label>Nome <span style="color:var(--bad);">*</span></label>
                    <input id="rmName" placeholder="Ex.: Auditor Externo" />
                </div>
                <div class="field" style="margin-top:10px;">
                    <label>Descrição</label>
                    <textarea id="rmDesc" placeholder="O que este papel pode fazer…" style="min-height:80px;"></textarea>
                </div>
                <div class="field" style="margin-top:10px;">
                    <label>Estado</label>
                    <select id="rmActive">
                        <option value="true">Ativo</option>
                        <option value="false">Desativado</option>
                    </select>
                </div>
                <div style="display:flex; gap:8px; margin-top:14px;">
                    <button id="rmSave" class="btn primary" type="button">
                        <i data-lucide="save" style="width:13px;height:13px;vertical-align:middle;margin-right:4px;"></i>
                        Guardar
                    </button>
                    <button id="rmCancel" class="btn" type="button">Cancelar</button>
                </div>
            </div>

            {{-- Presets de permissões --}}
            <div class="panel">
                <div class="modal-section-title">Presets de permissões</div>
                <p class="muted" style="margin:0 0 12px; font-size:12px;">
                    Aplica um conjunto pré-definido como ponto de partida — podes ajustar na matriz depois.
                </p>
                <div class="preset-grid">
                    <button class="preset-btn" type="button" data-preset="viewer">
                        <span class="preset-btn-name">Viewer</span>
                        <span class="preset-btn-count">—</span>
                    </button>
                    <button class="preset-btn" type="button" data-preset="auditor">
                        <span class="preset-btn-name">Auditor</span>
                        <span class="preset-btn-count">—</span>
                    </button>
                    <button class="preset-btn" type="button" data-preset="grc">
                        <span class="preset-btn-name">GRC Manager</span>
                        <span class="preset-btn-count">—</span>
                    </button>
                    <button class="preset-btn" type="button" data-preset="admin">
                        <span class="preset-btn-name">Admin</span>
                        <span class="preset-btn-count">—</span>
                    </button>
                </div>
                <div style="margin-top:14px;">
                    <div class="modal-section-title">Permissões selecionadas</div>
                    <div id="rmPermPreview" class="perm-preview">Nenhuma permissão selecionada.</div>
                </div>
            </div>

        </div>
    </div>
</div>


{{-- ════════════════════════════════════════════════
     MODAL: Editar utilizador
     ════════════════════════════════════════════════ --}}
<div id="userModal" class="modal-overlay is-hidden" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="userModalTitle"
         style="width:min(680px,96vw);">

        <div class="modal-header">
            <div>
                <div class="muted" style="font-size:12px; margin-bottom:3px;">RBAC · Utilizador</div>
                <div id="userModalTitle" style="font-size:18px; font-weight:800;">—</div>
            </div>
            <button id="userModalClose" class="btn" type="button">
                <i data-lucide="x" style="width:14px;height:14px;vertical-align:middle;"></i> Fechar
            </button>
        </div>

        <div style="height:16px;"></div>

        <div class="two">

            {{-- Identidade e papel --}}
            <div class="panel">
                <div class="modal-section-title">Identidade</div>
                <div style="margin-bottom:8px;">
                    <div class="muted" style="font-size:11px; margin-bottom:2px;">Email</div>
                    <div id="umEmail" style="font-size:13px;">—</div>
                </div>
                <div style="margin-bottom:14px;">
                    <div class="muted" style="font-size:11px; margin-bottom:4px;">Estado</div>
                    <div id="umStatusBadge"></div>
                </div>
                <div class="field">
                    <label>Papel atribuído</label>
                    <select id="umRole"></select>
                </div>
                <div style="display:flex; gap:8px; margin-top:14px;">
                    <button id="umSave" class="btn primary" type="button">
                        <i data-lucide="save" style="width:13px;height:13px;vertical-align:middle;margin-right:4px;"></i>
                        Guardar
                    </button>
                    <button id="umCancel" class="btn" type="button">Cancelar</button>
                </div>
            </div>

            {{-- Permissões efetivas --}}
            <div class="panel">
                <div class="modal-section-title">Permissões efetivas</div>
                <p class="muted" style="margin:0 0 10px; font-size:12px;">
                    Permissões herdadas do papel selecionado.
                </p>
                <div id="umPermsWrap" class="perms-summary-list"></div>
            </div>

        </div>
    </div>
</div>


{{-- ════════════════════════════════════════════════
     MODAL: Criar utilizador
     ════════════════════════════════════════════════ --}}
<div id="addUserModal" class="modal-overlay is-hidden" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="addUserModalTitle"
         style="width:min(520px,96vw);">

        <div class="modal-header">
            <div>
                <div class="muted" style="font-size:12px; margin-bottom:3px;">RBAC · Novo utilizador</div>
                <div id="addUserModalTitle" style="font-size:18px; font-weight:800;">Criar utilizador</div>
            </div>
            <button id="addUserModalClose" class="btn" type="button">
                <i data-lucide="x" style="width:14px;height:14px;vertical-align:middle;"></i> Fechar
            </button>
        </div>

        <div style="height:16px;"></div>

        <div class="panel">
            <div class="field">
                <label>Nome <span style="color:var(--bad);">*</span></label>
                <input id="auName" placeholder="Ex.: Maria Santos" />
            </div>
            <div class="field" style="margin-top:10px;">
                <label>Email <span style="color:var(--bad);">*</span></label>
                <input id="auEmail" type="email" placeholder="maria@empresa.pt" />
            </div>
            <div class="field" style="margin-top:10px;">
                <label>Papel</label>
                <select id="auRole"></select>
            </div>
            <div class="field" style="margin-top:10px;">
                <label>Estado</label>
                <select id="auStatus">
                    <option value="active">Ativo</option>
                    <option value="disabled">Desativado</option>
                </select>
            </div>
            <div style="display:flex; gap:8px; margin-top:16px;">
                <button id="auCreate" class="btn primary" type="button">
                    <i data-lucide="user-plus" style="width:13px;height:13px;vertical-align:middle;margin-right:4px;"></i>
                    Criar
                </button>
                <button id="auCancel" class="btn" type="button">Cancelar</button>
            </div>
            <p class="hint" style="margin-top:10px;">
                No backend real: verificar email único, enviar convite e definir password/reset.
            </p>
        </div>

    </div>
</div>

@endsection

@push('scripts')
    @vite(['resources/js/pages/rbac.js'])
@endpush
