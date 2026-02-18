@extends('layouts.app')
@section('title', 'Admin • RBAC • Techbase GRC')

@section('content')
    <section id="page-rbac" class="page">
        <div class="card">
            <h3>Admin • RBAC (RF19)</h3>
            <p class="muted" style="margin-top:6px">
                Gestão de papéis e permissões (Role-Based Access Control). Mock interativo: alterações refletem no HTML.
            </p>

            <div class="two" style="margin-top:12px">

                {{-- LEFT: Users --}}
                <div class="panel">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px">
                        <h2>Utilizadores</h2>
                        <button class="btn" type="button" id="btnAddUserMock">+ (mock) Utilizador</button>
                    </div>

                    <div class="row" style="margin-top:10px">
                        <div class="field">
                            <label>Pesquisar</label>
                            <input id="userSearch" placeholder="nome, email..." />
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <select id="userStatusFilter">
                                <option value="all">Todos</option>
                                <option value="active">Ativo</option>
                                <option value="disabled">Desativado</option>
                            </select>
                        </div>
                    </div>

                    <div style="height:10px"></div>

                    <table>
                        <thead>
                            <tr>
                                <th>Utilizador</th>
                                <th>Papel</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="usersTbody"></tbody>
                    </table>

                    <div class="hint" style="margin-top:10px">
                        Nota: no backend real, isto vira RBAC com middleware/policies (ex.: Gate/Policy no Laravel).
                    </div>
                </div>

                {{-- RIGHT: Roles --}}
                <div class="panel">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px">
                        <h2>Papéis (Roles)</h2>
                        <button class="btn primary" type="button" id="btnCreateRole">+ Criar papel</button>
                    </div>

                    <div class="row" style="margin-top:10px">
                        <div class="field">
                            <label>Pesquisar papel</label>
                            <input id="roleSearch" placeholder="Admin, Auditor..." />
                        </div>
                        <div class="field">
                            <label>Mostrar</label>
                            <select id="roleActiveFilter">
                                <option value="all">Todos</option>
                                <option value="active">Ativos</option>
                                <option value="inactive">Desativados</option>
                            </select>
                        </div>
                    </div>

                    <div style="height:10px"></div>

                    <div id="rolesList" class="roles-list"></div>

                    <div class="hint" style="margin-top:10px">
                        Dica: “Clonar” é útil para criar papéis parecidos (ex.: Auditor vs Viewer).
                    </div>
                </div>
            </div>

            <div style="height:12px"></div>

            {{-- MATRIX --}}
            <div class="panel">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px">
                    <div>
                        <h2>Matriz Role × Permissão</h2>
                        <p class="muted" style="margin-top:6px">
                            Seleciona um papel para editar permissões. As checkboxes atualizam o estado do mock
                            instantaneamente.
                        </p>
                    </div>

                    <div class="kpirow">
                        <span class="chip">Role ativo: <b id="kpiRoleName">—</b></span>
                        <span class="chip warn">Permissões: <b id="kpiPermCount">0</b></span>
                    </div>
                </div>

                <div style="height:10px"></div>

                <div class="row">
                    <div class="field">
                        <label>Papel selecionado</label>
                        <select id="rolePicker"></select>
                    </div>
                    <div class="field">
                        <label>Pesquisar permissão</label>
                        <input id="permSearch" placeholder="assets., docs., rbac..." />
                    </div>
                    <div class="field">
                        <label>&nbsp;</label>
                        <button class="btn" type="button" id="btnResetRolePerms">Reset (mock)</button>
                    </div>
                </div>

                <div style="height:10px"></div>

                <div class="perm-grid">
                    <div class="perm-left">
                        <div id="permGroups"></div>
                    </div>

                    <div class="perm-right">
                        <div class="perm-card">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px">
                                <div>
                                    <div class="muted">Resumo do papel</div>
                                    <div style="font-size:16px; font-weight:900" id="roleSummaryName">—</div>
                                </div>
                                <span class="tag" id="roleSummaryStatus"><span class="s"></span> —</span>
                            </div>

                            <div style="height:10px"></div>

                            <div class="mini-note" id="roleSummaryDesc">—</div>

                            <div style="height:10px"></div>

                            <div class="two">
                                <div class="panel" style="background: rgba(0,0,0,.10)">
                                    <h2 style="font-size:14px">Atalhos</h2>
                                    <div style="display:flex; gap:10px; flex-wrap:wrap">
                                        <button class="btn" type="button" id="btnSelectAllPerms">Selecionar tudo</button>
                                        <button class="btn" type="button" id="btnClearAllPerms">Limpar tudo</button>
                                        <button class="btn warn" type="button" id="btnDisableRole">Desativar papel</button>
                                    </div>
                                </div>

                                <div class="panel" style="background: rgba(0,0,0,.10)">
                                    <h2 style="font-size:14px">Auditoria (RNF5)</h2>
                                    <div class="mini-note">
                                        Este mock não grava BD, mas regista logs visuais aqui:
                                    </div>
                                    <div id="auditBox" class="audit-box"></div>
                                </div>
                            </div>
                        </div>

                        <div class="hint" style="margin-top:10px">
                            Em produção, mudanças aqui devem exigir permissão <b>rbac.manage</b> + trilha de auditoria.
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- MODAL: Create/Edit Role --}}
        <div id="roleModal" class="modal-overlay is-hidden" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="roleModalTitle">
                <div class="modal-header">
                    <div>
                        <div class="muted" style="margin-bottom:4px">RBAC</div>
                        <div id="roleModalTitle" style="font-size:18px;font-weight:900">—</div>
                    </div>
                    <button class="btn" type="button" id="roleModalClose">Fechar</button>
                </div>

                <div style="height:12px"></div>

                <div class="two">
                    <div class="panel">
                        <h2>Dados do papel</h2>
                        <div class="field">
                            <label>Nome</label>
                            <input id="rmName" placeholder="Ex.: Auditor" />
                        </div>
                        <div class="field">
                            <label>Descrição</label>
                            <textarea id="rmDesc" placeholder="O que este papel consegue fazer..."
                                style="min-height:90px"></textarea>
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <select id="rmActive">
                                <option value="true">Ativo</option>
                                <option value="false">Desativado</option>
                            </select>
                        </div>

                        <div style="display:flex; gap:10px; margin-top:10px">
                            <button class="btn primary" type="button" id="rmSave">Guardar</button>
                            <button class="btn" type="button" id="rmCancel">Cancelar</button>
                        </div>

                        <div class="hint" style="margin-top:10px">
                            No sistema real, nome deve ser único e normalizado (ex.: ADMIN, AUDITOR...).
                        </div>
                    </div>

                    <div class="panel">
                        <h2>Permissões rápidas</h2>
                        <p class="muted">Opcional: aplicar um preset (mock) para acelerar.</p>

                        <div class="roles-presets">
                            <button class="btn" type="button" data-preset="viewer">Preset: Viewer</button>
                            <button class="btn" type="button" data-preset="auditor">Preset: Auditor</button>
                            <button class="btn" type="button" data-preset="grc">Preset: GRC Manager</button>
                            <button class="btn" type="button" data-preset="admin">Preset: Admin</button>
                        </div>

                        <div style="height:10px"></div>
                        <div class="mini-note" id="rmPresetNote">
                            Dica: presets apenas ajustam permissões do mock.
                        </div>

                        <div style="height:10px"></div>
                        <div class="chunk-preview" id="rmPermPreview">Sem alterações ainda.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODAL: User Details --}}
        <div id="userModal" class="modal-overlay is-hidden" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
                <div class="modal-header">
                    <div>
                        <div class="muted" style="margin-bottom:4px">Detalhes do utilizador</div>
                        <div id="userModalTitle" style="font-size:18px;font-weight:900">—</div>
                    </div>
                    <button class="btn" type="button" id="userModalClose">Fechar</button>
                </div>

                <div style="height:12px"></div>

                <div class="two">
                    <div class="panel">
                        <h2>Identidade</h2>
                        <div class="muted">Email</div>
                        <div id="umEmail" style="margin-bottom:10px">—</div>

                        <div class="muted">Status</div>
                        <div id="umStatus" style="margin-bottom:10px">—</div>

                        <div class="muted">Papel</div>
                        <select id="umRole"></select>

                        <div style="height:10px"></div>
                        <div style="display:flex; gap:10px">
                            <button class="btn primary" type="button" id="umSave">Guardar</button>
                            <button class="btn" type="button" id="umCancel">Cancelar</button>
                        </div>
                    </div>

                    <div class="panel">
                        <h2>Resumo de acesso</h2>
                        <p class="muted">Permissões efetivas (mock) do papel atual:</p>
                        <div id="umPerms" class="audit-box"></div>
                    </div>
                </div>
            </div>
        </div>
        {{-- MODAL: Criar Utilizador (mock) --}}
        <div id="addUserModal" class="modal-overlay is-hidden" aria-hidden="true">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="addUserModalTitle">
                <div class="modal-header">
                    <div>
                        <div class="muted" style="margin-bottom:4px">Criar utilizador</div>
                        <div id="addUserModalTitle" style="font-size:18px;font-weight:900">Novo utilizador (mock)</div>
                    </div>
                    <button class="btn" type="button" id="addUserModalClose">Fechar</button>
                </div>

                <div style="height:12px"></div>

                <div class="two">
                    <div class="panel">
                        <h2>Dados</h2>

                        <div class="field">
                            <label>Nome</label>
                            <input id="auName" placeholder="Ex.: Maria" />
                        </div>

                        <div class="field">
                            <label>Email</label>
                            <input id="auEmail" placeholder="ex.: maria@clinica.pt" />
                        </div>

                        <div class="field">
                            <label>Papel</label>
                            <select id="auRole"></select>
                        </div>

                        <div class="field">
                            <label>Status</label>
                            <select id="auStatus">
                                <option value="active">Ativo</option>
                                <option value="disabled">Desativado</option>
                            </select>
                        </div>

                        <div style="display:flex; gap:10px; margin-top:10px">
                            <button class="btn primary" type="button" id="auCreate">Criar</button>
                            <button class="btn" type="button" id="auCancel">Cancelar</button>
                        </div>

                        <div class="hint" style="margin-top:10px">
                            Mock: validações simples (nome/email) e cria na lista imediatamente.
                        </div>
                    </div>

                    <div class="panel">
                        <h2>Nota</h2>
                        <p class="muted">
                            No backend real: verificar email único, mandar convite, definir password/reset, etc.
                        </p>
                    </div>
                </div>
            </div>
        </div>


        {{-- CSS local --}}
        <style>
            .roles-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .role-row {
                border: 1px solid rgba(255, 255, 255, .10);
                background: rgba(0, 0, 0, .14);
                border-radius: 14px;
                padding: 12px;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
            }

            .role-row .left {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .role-row .title {
                font-weight: 900;
                font-size: 15px;
            }

            .role-row .desc {
                color: var(--muted);
                font-size: 12px;
                line-height: 1.35;
                max-width: 62ch;
            }

            .role-row .right {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: flex-end;
                align-items: center;
                min-width: 260px;
            }

            .perm-grid {
                display: grid;
                grid-template-columns: 1.4fr .9fr;
                gap: 12px;
                align-items: start;
            }

            .perm-card {
                border: 1px solid rgba(255, 255, 255, .10);
                background: rgba(0, 0, 0, .12);
                border-radius: 14px;
                padding: 12px;
            }

            .perm-group {
                border: 1px solid rgba(255, 255, 255, .10);
                background: rgba(0, 0, 0, .12);
                border-radius: 14px;
                padding: 12px;
                margin-bottom: 10px;
            }

            .perm-group-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
            }

            .perm-group-title {
                font-weight: 900;
                font-size: 14px;
            }

            .perm-items {
                margin-top: 10px;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .perm-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                padding: 10px;
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, .08);
                background: rgba(0, 0, 0, .10);
            }

            .perm-item .k {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 12px;
                font-weight: 800;
            }

            .perm-item .d {
                color: var(--muted);
                font-size: 12px;
                line-height: 1.35;
                margin-top: 2px;
            }

            .perm-item .left {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .perm-item .right {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 6px 10px;
                border-radius: 999px;
                border: 1px solid rgba(255, 255, 255, .10);
                background: rgba(255, 255, 255, .06);
                color: rgba(255, 255, 255, .90);
                font-weight: 800;
                font-size: 12px;
                width: fit-content;
            }

            .audit-box {
                border: 1px solid rgba(255, 255, 255, .10);
                background: rgba(0, 0, 0, .14);
                border-radius: 12px;
                padding: 10px;
                max-height: 160px;
                overflow: auto;
                color: rgba(255, 255, 255, .86);
                font-size: 12px;
                line-height: 1.35;
                white-space: pre-wrap;
            }

            .mini-note {
                color: var(--muted);
                font-size: 12px;
                line-height: 1.35;
            }

            .roles-presets {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            /* modal base */
            .modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .62);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 18px;
                z-index: 99999;
            }

            .modal-overlay.is-hidden {
                display: none;
            }

            .modal-card {
                width: min(1100px, 96vw);
                max-height: 90vh;
                overflow: auto;
                border: 1px solid rgba(255, 255, 255, .10);
                border-radius: 16px;
                background: rgba(18, 26, 43, .96);
                box-shadow: 0 30px 60px rgba(0, 0, 0, .55);
                padding: 14px;
            }

            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding-bottom: 12px;
                border-bottom: 1px solid rgba(255, 255, 255, .06);
            }

            .chunk-preview {
                border: 1px solid rgba(255, 255, 255, .10);
                background: rgba(0, 0, 0, .14);
                border-radius: 12px;
                padding: 10px;
                color: rgba(255, 255, 255, .88);
                font-size: 13px;
                line-height: 1.35;
                white-space: pre-wrap;
            }
        </style>
    </section>

    @push('scripts')
        <script>
            // ========= DATA (mock) =========

            const PERMISSIONS = [
                { key: 'assets.view', group: 'Ativos', desc: 'Ver ativos e detalhes.' },
                { key: 'assets.create', group: 'Ativos', desc: 'Registar ativos.' },
                { key: 'assets.edit', group: 'Ativos', desc: 'Editar ativos.' },
                { key: 'assets.delete', group: 'Ativos', desc: 'Eliminar ativos (restrito).' },

                { key: 'docs.view', group: 'Documentos', desc: 'Ver documentos e evidências.' },
                { key: 'docs.upload', group: 'Documentos', desc: 'Carregar documentos.' },
                { key: 'docs.version', group: 'Documentos', desc: 'Criar nova versão.' },
                { key: 'docs.link_controls', group: 'Documentos', desc: 'Associar evidências a controlos.' },
                { key: 'docs.approve_links', group: 'Documentos', desc: 'Aprovar/rejeitar associações sugeridas (RF16).' },
                { key: 'docs.change_status', group: 'Documentos', desc: 'Alterar status do documento (Ativo/Pendente/etc).' },

                { key: 'frameworks.view', group: 'Frameworks', desc: 'Ver frameworks e controlos.' },
                { key: 'frameworks.import', group: 'Frameworks', desc: 'Importar/atualizar versões.' },
                { key: 'frameworks.edit', group: 'Frameworks', desc: 'Editar conteúdos normativos (restrito).' },

                { key: 'assessments.view', group: 'Avaliações', desc: 'Ver avaliações e histórico.' },
                { key: 'assessments.run', group: 'Avaliações', desc: 'Executar avaliação (org/ativo).' },
                { key: 'assessments.edit', group: 'Avaliações', desc: 'Editar resultados e evidências.' },

                { key: 'risk.view', group: 'Risco', desc: 'Ver riscos e matriz.' },
                { key: 'risk.plan.manage', group: 'Risco', desc: 'Gerir planos de tratamento (RF10-11).' },
                { key: 'risk.accept', group: 'Risco', desc: 'Aceitação formal de risco (RF12).' },

                { key: 'chat.use', group: 'Chat/RAG', desc: 'Usar chat de governação (RF15).' },
                { key: 'chat.view_logs', group: 'Chat/RAG', desc: 'Ver logs do chat e auditoria.' },

                { key: 'audit.view', group: 'Auditoria', desc: 'Consultar logs de auditoria (RNF5).' },

                { key: 'rbac.manage', group: 'Admin/RBAC', desc: 'Gerir papéis e permissões (RF19).' },
            ];

            const ROLE_PRESETS = {
                viewer: new Set(['assets.view', 'docs.view', 'frameworks.view', 'assessments.view', 'risk.view']),
                auditor: new Set(['assets.view', 'docs.view', 'docs.approve_links', 'frameworks.view', 'assessments.view', 'risk.view', 'audit.view', 'chat.view_logs']),
                grc: new Set(['assets.view', 'assets.create', 'assets.edit', 'docs.view', 'docs.upload', 'docs.version', 'docs.link_controls', 'docs.approve_links', 'docs.change_status', 'frameworks.view', 'frameworks.import', 'assessments.view', 'assessments.run', 'assessments.edit', 'risk.view', 'risk.plan.manage', 'risk.accept', 'chat.use', 'audit.view']),
                admin: new Set(PERMISSIONS.map(p => p.key)),
            };

            let roles = [
                { id: 'R1', name: 'Admin', desc: 'Acesso total. Pode gerir RBAC.', active: true, perms: new Set(ROLE_PRESETS.admin) },
                { id: 'R2', name: 'GRC Manager', desc: 'Gestão de conformidade, risco e evidências.', active: true, perms: new Set(ROLE_PRESETS.grc) },
                { id: 'R3', name: 'Auditor', desc: 'Consulta e valida evidências, acessa auditoria.', active: true, perms: new Set(ROLE_PRESETS.auditor) },
                { id: 'R4', name: 'Viewer', desc: 'Somente leitura para dashboards e dados.', active: true, perms: new Set(ROLE_PRESETS.viewer) },
            ];

            let users = [
                { id: 'U1', name: 'Ana', email: 'ana@clinica.pt', roleId: 'R2', status: 'active' },
                { id: 'U2', name: 'João', email: 'joao@clinica.pt', roleId: 'R4', status: 'active' },
                { id: 'U3', name: 'Pedro', email: 'pedro@clinica.pt', roleId: 'R3', status: 'active' },
                { id: 'U4', name: 'Rita', email: 'rita@clinica.pt', roleId: 'R1', status: 'active' },
            ];

            // state
            let selectedRoleId = 'R2';
            let roleModalMode = 'create'; // create|edit
            let roleModalRoleId = null;
            let roleModalWorkingPerms = new Set(); // preview/preset

            // ========= HELPERS =========

            const $ = (sel) => document.querySelector(sel);
            const $$ = (sel) => Array.from(document.querySelectorAll(sel));

            function logAudit(msg) {
                const box = $('#auditBox');
                const ts = new Date().toISOString().slice(0, 19).replace('T', ' ');
                const line = `[${ts}] ${msg}\n`;
                box.textContent = line + box.textContent;
            }

            function roleById(id) { return roles.find(r => r.id === id); }
            function userById(id) { return users.find(u => u.id === id); }

            function roleTag(role) {
                if (!role.active) return `<span class="tag bad"><span class="s"></span> Desativado</span>`;
                return `<span class="tag ok"><span class="s"></span> Ativo</span>`;
            }

            function countPerms(role) { return role.perms.size; }

            function groupedPermissions() {
                const map = new Map();
                PERMISSIONS.forEach(p => {
                    if (!map.has(p.group)) map.set(p.group, []);
                    map.get(p.group).push(p);
                });
                return map;
            }

            function normalize(str) { return (str || '').toLowerCase().trim(); }

            // ========= RENDER =========

            function renderRolePicker() {
                const sel = $('#rolePicker');
                sel.innerHTML = '';
                roles.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name + (r.active ? '' : ' (desativado)');
                    sel.appendChild(opt);
                });
                sel.value = selectedRoleId;
            }

            function renderUsers() {
                const q = normalize($('#userSearch').value);
                const st = $('#userStatusFilter').value;

                const tbody = $('#usersTbody');
                tbody.innerHTML = '';

                users
                    .filter(u => {
                        const matches = !q || normalize(u.name).includes(q) || normalize(u.email).includes(q);
                        const matchesSt = st === 'all' || u.status === st;
                        return matches && matchesSt;
                    })
                    .forEach(u => {
                        const role = roleById(u.roleId);
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                                  <td>
                                    <b>${u.name}</b>
                                    <div class="muted">${u.email}</div>
                                  </td>
                                  <td>
                                    <span class="pill">${role ? role.name : '—'}</span>
                                  </td>
                                  <td>${u.status === 'active'
                                ? `<span class="tag ok"><span class="s"></span> Ativo</span>`
                                : `<span class="tag bad"><span class="s"></span> Desativado</span>`
                            }</td>
                                  <td style="display:flex; gap:8px; align-items:center">
                                    <button class="btn" type="button" data-user-details="${u.id}">Detalhes</button>
                                    <button class="btn warn" type="button" data-toggle-user="${u.id}">
                                      ${u.status === 'active' ? 'Desativar' : 'Ativar'}
                                    </button>
                                  </td>
                                `;
                        tbody.appendChild(tr);
                    });

                $$('[data-user-details]').forEach(b => b.addEventListener('click', () => openUserModal(b.dataset.userDetails)));
                $$('[data-toggle-user]').forEach(b => b.addEventListener('click', () => toggleUserStatus(b.dataset.toggleUser)));
            }

            function renderRolesList() {
                const q = normalize($('#roleSearch').value);
                const flt = $('#roleActiveFilter').value;

                const wrap = $('#rolesList');
                wrap.innerHTML = '';

                roles
                    .filter(r => {
                        const matches = !q || normalize(r.name).includes(q);
                        const matchesSt = flt === 'all' || (flt === 'active' ? r.active : !r.active);
                        return matches && matchesSt;
                    })
                    .forEach(r => {
                        const div = document.createElement('div');
                        div.className = 'role-row';
                        div.innerHTML = `
                                  <div class="left">
                                    <div class="title">${r.name}</div>
                                    <div class="desc">${r.desc || '—'}</div>
                                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
                                      ${roleTag(r)}
                                      <span class="chip">Permissões: <b>${countPerms(r)}</b></span>
                                      <span class="chip">ID: <b>${r.id}</b></span>
                                    </div>
                                  </div>
                                  <div class="right">
                                    <button class="btn ${r.id === selectedRoleId ? 'primary' : ''}" type="button" data-select-role="${r.id}">
                                      ${r.id === selectedRoleId ? 'Selecionado' : 'Selecionar'}
                                    </button>
                                    <button class="btn" type="button" data-edit-role="${r.id}">Editar</button>
                                    <button class="btn" type="button" data-clone-role="${r.id}">Clonar</button>
                                    <button class="btn warn" type="button" data-toggle-role="${r.id}">
                                      ${r.active ? 'Desativar' : 'Ativar'}
                                    </button>
                                  </div>
                                `;
                        wrap.appendChild(div);
                    });

                $$('[data-select-role]').forEach(b => b.addEventListener('click', () => setSelectedRole(b.dataset.selectRole)));
                $$('[data-edit-role]').forEach(b => b.addEventListener('click', () => openRoleModal('edit', b.dataset.editRole)));
                $$('[data-clone-role]').forEach(b => b.addEventListener('click', () => cloneRole(b.dataset.cloneRole)));
                $$('[data-toggle-role]').forEach(b => b.addEventListener('click', () => toggleRoleActive(b.dataset.toggleRole)));
            }

            function renderPermGroups() {
                const role = roleById(selectedRoleId);
                if (!role) return;

                // KPI
                $('#kpiRoleName').textContent = role.name;
                $('#kpiPermCount').textContent = role.perms.size;

                // Summary
                $('#roleSummaryName').textContent = role.name;
                $('#roleSummaryDesc').textContent = role.desc || '—';
                $('#roleSummaryStatus').className = 'tag ' + (role.active ? 'ok' : 'bad');
                $('#roleSummaryStatus').innerHTML = `<span class="s"></span> ${role.active ? 'Ativo' : 'Desativado'}`;

                const search = normalize($('#permSearch').value);
                const groups = groupedPermissions();

                const wrap = $('#permGroups');
                wrap.innerHTML = '';

                groups.forEach((items, groupName) => {
                    // filter items by search
                    const filtered = items.filter(p => {
                        if (!search) return true;
                        return normalize(p.key).includes(search) || normalize(p.desc).includes(search) || normalize(p.group).includes(search);
                    });
                    if (filtered.length === 0) return;

                    const onCount = filtered.filter(p => role.perms.has(p.key)).length;

                    const g = document.createElement('div');
                    g.className = 'perm-group';
                    g.innerHTML = `
                                <div class="perm-group-head">
                                  <div>
                                    <div class="perm-group-title">${groupName}</div>
                                    <div class="mini-note">${onCount}/${filtered.length} selecionadas</div>
                                  </div>
                                  <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end">
                                    <button class="btn" type="button" data-group-all="${groupName}">Tudo</button>
                                    <button class="btn" type="button" data-group-none="${groupName}">Nada</button>
                                  </div>
                                </div>

                                <div class="perm-items">
                                  ${filtered.map(p => `
                                    <div class="perm-item">
                                      <div class="left">
                                        <div class="k">${p.key}</div>
                                        <div class="d">${p.desc}</div>
                                      </div>
                                      <div class="right">
                                        <label class="toggle">
                                          <input type="checkbox" data-perm="${p.key}" ${role.perms.has(p.key) ? 'checked' : ''} ${(!role.active) ? 'disabled' : ''}/>
                                          <span>${role.perms.has(p.key) ? 'On' : 'Off'}</span>
                                        </label>
                                      </div>
                                    </div>
                                  `).join('')}
                                </div>
                              `;
                    wrap.appendChild(g);
                });

                // checkbox handlers
                $$('[data-perm]').forEach(cb => {
                    cb.addEventListener('change', () => {
                        const key = cb.dataset.perm;
                        if (cb.checked) role.perms.add(key);
                        else role.perms.delete(key);

                        $('#kpiPermCount').textContent = role.perms.size;
                        logAudit(`Permissão ${key} => ${cb.checked ? 'ON' : 'OFF'} (role=${role.name})`);
                        renderRolesList(); // update counts
                        renderUserModalPermsIfOpen(); // if user modal open, refresh
                    });
                });

                // group buttons
                $$('[data-group-all]').forEach(b => b.addEventListener('click', () => setGroup(b.dataset.groupAll, true)));
                $$('[data-group-none]').forEach(b => b.addEventListener('click', () => setGroup(b.dataset.groupNone, false)));
            }

            function setGroup(groupName, enable) {
                const role = roleById(selectedRoleId);
                const items = PERMISSIONS.filter(p => p.group === groupName);
                items.forEach(p => enable ? role.perms.add(p.key) : role.perms.delete(p.key));
                logAudit(`${enable ? 'Selecionar' : 'Limpar'} grupo "${groupName}" (role=${role.name})`);
                renderPermGroups();
                renderRolesList();
            }

            function setSelectedRole(roleId) {
                selectedRoleId = roleId;
                renderRolePicker();
                renderRolesList();
                renderPermGroups();
                logAudit(`Selecionou role=${roleById(roleId)?.name || roleId}`);
            }

            // ========= ACTIONS =========

            function toggleRoleActive(roleId) {
                const r = roleById(roleId);
                if (!r) return;
                r.active = !r.active;

                // se desativar role selecionado, disable checkboxes via rerender
                renderRolesList();
                renderPermGroups();

                logAudit(`Role ${r.name} => ${r.active ? 'ATIVO' : 'DESATIVADO'}`);
            }

            function cloneRole(roleId) {
                const src = roleById(roleId);
                if (!src) return;

                const newId = 'R' + (Math.floor(Math.random() * 9000) + 1000);
                const copy = {
                    id: newId,
                    name: src.name + ' (Clone)',
                    desc: src.desc,
                    active: true,
                    perms: new Set(Array.from(src.perms))
                };
                roles.unshift(copy);

                renderRolePicker();
                renderRolesList();

                logAudit(`Clonou role "${src.name}" => "${copy.name}"`);
            }

            function toggleUserStatus(userId) {
                const u = userById(userId);
                if (!u) return;
                u.status = (u.status === 'active') ? 'disabled' : 'active';
                renderUsers();
                logAudit(`Utilizador ${u.email} => ${u.status === 'active' ? 'ATIVO' : 'DESATIVADO'}`);
            }

            // ========= ROLE MODAL =========

            function openRoleModal(mode, roleId = null) {
                roleModalMode = mode;
                roleModalRoleId = roleId;

                const modal = $('#roleModal');
                const title = $('#roleModalTitle');

                if (mode === 'create') {
                    title.textContent = 'Criar papel';
                    $('#rmName').value = '';
                    $('#rmDesc').value = '';
                    $('#rmActive').value = 'true';
                    roleModalWorkingPerms = new Set(); // start empty
                    $('#rmPermPreview').textContent = 'Sem alterações ainda.';
                } else {
                    const r = roleById(roleId);
                    title.textContent = 'Editar papel';
                    $('#rmName').value = r.name;
                    $('#rmDesc').value = r.desc || '';
                    $('#rmActive').value = r.active ? 'true' : 'false';
                    roleModalWorkingPerms = new Set(Array.from(r.perms));
                    updateRoleModalPermPreview();
                }

                modal.classList.remove('is-hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeRoleModal() {
                const modal = $('#roleModal');
                modal.classList.add('is-hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            function updateRoleModalPermPreview() {
                const arr = Array.from(roleModalWorkingPerms).sort();
                $('#rmPermPreview').textContent = arr.length
                    ? `Permissões selecionadas (${arr.length}):\n- ` + arr.join('\n- ')
                    : 'Sem permissões selecionadas.';
            }

            function applyPresetToRoleModal(presetKey) {
                const preset = ROLE_PRESETS[presetKey];
                roleModalWorkingPerms = new Set(Array.from(preset));
                $('#rmPresetNote').textContent = `Preset aplicado: ${presetKey}. Podes ajustar depois na matriz.`;
                updateRoleModalPermPreview();
            }

            function saveRoleModal() {
                const name = $('#rmName').value.trim();
                if (!name) {
                    alert('Nome do papel é obrigatório.');
                    return;
                }

                const desc = $('#rmDesc').value.trim();
                const active = $('#rmActive').value === 'true';

                if (roleModalMode === 'create') {
                    const newId = 'R' + (Math.floor(Math.random() * 9000) + 1000);
                    roles.unshift({
                        id: newId,
                        name,
                        desc,
                        active,
                        perms: new Set(Array.from(roleModalWorkingPerms))
                    });

                    logAudit(`Criou role "${name}" (perms=${roleModalWorkingPerms.size})`);
                    // seleciona o novo role
                    selectedRoleId = newId;
                } else {
                    const r = roleById(roleModalRoleId);
                    r.name = name;
                    r.desc = desc;
                    r.active = active;
                    r.perms = new Set(Array.from(roleModalWorkingPerms));
                    logAudit(`Editou role "${name}" (perms=${roleModalWorkingPerms.size})`);

                    // manter seleção se editou o selecionado
                    if (selectedRoleId === r.id) selectedRoleId = r.id;
                }

                renderRolePicker();
                renderRolesList();
                renderPermGroups();
                closeRoleModal();
            }

            // ========= USER MODAL =========

            let userModalUserId = null;

            function openUserModal(userId) {
                const u = userById(userId);
                if (!u) return;
                userModalUserId = userId;

                $('#userModalTitle').textContent = u.name;
                $('#umEmail').textContent = u.email;
                $('#umStatus').innerHTML = (u.status === 'active')
                    ? `<span class="tag ok"><span class="s"></span> Ativo</span>`
                    : `<span class="tag bad"><span class="s"></span> Desativado</span>`;

                const sel = $('#umRole');
                sel.innerHTML = '';
                roles.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name + (r.active ? '' : ' (desativado)');
                    sel.appendChild(opt);
                });
                sel.value = u.roleId;

                renderUserModalPerms();

                $('#userModal').classList.remove('is-hidden');
                $('#userModal').setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function renderUserModalPerms() {
                const u = userById(userModalUserId);
                if (!u) return;
                const r = roleById(u.roleId);
                const arr = r ? Array.from(r.perms).sort() : [];
                $('#umPerms').textContent = arr.length ? ('- ' + arr.join('\n- ')) : 'Sem permissões.';
            }

            function renderUserModalPermsIfOpen() {
                const modalOpen = !$('#userModal').classList.contains('is-hidden');
                if (modalOpen) renderUserModalPerms();
            }

            function closeUserModal() {
                $('#userModal').classList.add('is-hidden');
                $('#userModal').setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                userModalUserId = null;
            }

            function saveUserModal() {
                const u = userById(userModalUserId);
                if (!u) return;
                const newRoleId = $('#umRole').value;
                u.roleId = newRoleId;
                renderUsers();
                renderUserModalPerms();
                logAudit(`Alterou role do utilizador ${u.email} => ${roleById(newRoleId)?.name || newRoleId}`);
                closeUserModal();
            }

            // ---- Add User Modal ----
            function openAddUserModal() {
                // preencher roles no select
                const sel = $('#auRole');
                sel.innerHTML = '';
                roles.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name + (r.active ? '' : ' (desativado)');
                    sel.appendChild(opt);
                });

                // defaults
                $('#auName').value = '';
                $('#auEmail').value = '';
                $('#auRole').value = selectedRoleId;
                $('#auStatus').value = 'active';

                $('#addUserModal').classList.remove('is-hidden');
                $('#addUserModal').setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeAddUserModal() {
                $('#addUserModal').classList.add('is-hidden');
                $('#addUserModal').setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            function createUserFromModal() {
                const name = $('#auName').value.trim();
                const email = $('#auEmail').value.trim().toLowerCase();
                const roleId = $('#auRole').value;
                const status = $('#auStatus').value;

                if (!name) { alert('Nome é obrigatório.'); return; }
                if (!email || !email.includes('@')) { alert('Email inválido.'); return; }

                // mock: impedir emails repetidos
                const exists = users.some(u => u.email.toLowerCase() === email);
                if (exists) { alert('Já existe um utilizador com este email (mock).'); return; }

                const id = 'U' + (Math.floor(Math.random() * 9000) + 1000);
                users.unshift({ id, name, email, roleId, status });

                renderUsers();
                logAudit(`Criou utilizador (mock) ${email} com role=${roleById(roleId)?.name || roleId}`);
                closeAddUserModal();
            }

            // ========= INIT =========

            function init() {
                // default selected role
                renderRolePicker();
                renderUsers();
                renderRolesList();
                renderPermGroups();

                $('#rolePicker').addEventListener('change', (e) => setSelectedRole(e.target.value));
                $('#permSearch').addEventListener('input', renderPermGroups);

                $('#userSearch').addEventListener('input', renderUsers);
                $('#userStatusFilter').addEventListener('change', renderUsers);

                $('#roleSearch').addEventListener('input', renderRolesList);
                $('#roleActiveFilter').addEventListener('change', renderRolesList);

                $('#btnCreateRole').addEventListener('click', () => openRoleModal('create'));
                $('#roleModalClose').addEventListener('click', closeRoleModal);
                $('#rmCancel').addEventListener('click', closeRoleModal);
                $('#rmSave').addEventListener('click', saveRoleModal);

                $$('[data-preset]').forEach(b => b.addEventListener('click', () => applyPresetToRoleModal(b.dataset.preset)));

                $('#userModalClose').addEventListener('click', closeUserModal);
                $('#umCancel').addEventListener('click', closeUserModal);
                $('#umSave').addEventListener('click', saveUserModal);

                // click outside closes
                $('#roleModal').addEventListener('click', (e) => { if (e.target.id === 'roleModal') closeRoleModal(); });
                $('#userModal').addEventListener('click', (e) => { if (e.target.id === 'userModal') closeUserModal(); });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        if (!$('#roleModal').classList.contains('is-hidden')) closeRoleModal();
                        if (!$('#userModal').classList.contains('is-hidden')) closeUserModal();
                    }
                });

                // matrix shortcuts
                $('#btnSelectAllPerms').addEventListener('click', () => {
                    const r = roleById(selectedRoleId);
                    PERMISSIONS.forEach(p => r.perms.add(p.key));
                    logAudit(`Selecionou todas permissões (role=${r.name})`);
                    renderPermGroups(); renderRolesList();
                });

                $('#btnClearAllPerms').addEventListener('click', () => {
                    const r = roleById(selectedRoleId);
                    r.perms = new Set();
                    logAudit(`Limpou todas permissões (role=${r.name})`);
                    renderPermGroups(); renderRolesList();
                });

                $('#btnDisableRole').addEventListener('click', () => {
                    toggleRoleActive(selectedRoleId);
                });

                $('#btnResetRolePerms').addEventListener('click', () => {
                    const r = roleById(selectedRoleId);
                    // reset heurístico: se for Admin, volta admin; senão volta viewer
                    const preset = (r.name.toLowerCase().includes('admin')) ? ROLE_PRESETS.admin : ROLE_PRESETS.viewer;
                    r.perms = new Set(Array.from(preset));
                    logAudit(`Reset permissões (mock) (role=${r.name})`);
                    renderPermGroups(); renderRolesList();
                });

                // mock add user
                // abre modal de criar user
                $('#btnAddUserMock').addEventListener('click', openAddUserModal);

                // modal criar user
                $('#addUserModalClose').addEventListener('click', closeAddUserModal);
                $('#auCancel').addEventListener('click', closeAddUserModal);
                $('#auCreate').addEventListener('click', createUserFromModal);

                $('#addUserModal').addEventListener('click', (e) => {
                    if (e.target.id === 'addUserModal') closeAddUserModal();
                });

                logAudit('RBAC mock carregado.');
            }

            document.addEventListener('DOMContentLoaded', init);
        </script>
    @endpush

@endsection