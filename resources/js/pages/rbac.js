// ═══════════════════════════════════════════════════════════════
// Techbase GRC — RBAC (RF19)
// Ligado à BD via API — sem mock em memória.
// ═══════════════════════════════════════════════════════════════

(() => {

    const $ = (s, ctx = document) => ctx.querySelector(s);
    const $$ = (s, ctx = document) => [...ctx.querySelectorAll(s)];
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";
    const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    // ── API helpers ──────────────────────────────────────────────────
    async function apiGet(url) {
        const r = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
    }
    async function apiPost(url, body = {}) {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            body: JSON.stringify(body),
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(d.message || `HTTP ${r.status}`);
        return d;
    }
    async function apiPut(url, body = {}) {
        const r = await fetch(url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            body: JSON.stringify(body),
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(d.message || `HTTP ${r.status}`);
        return d;
    }
    async function apiPatch(url, body = {}) {
        const r = await fetch(url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            body: JSON.stringify(body),
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(d.message || `HTTP ${r.status}`);
        return d;
    }
    async function apiDelete(url) {
        const r = await fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(d.message || `HTTP ${r.status}`);
        return d;
    }

    // ── Estado ───────────────────────────────────────────────────────
    let ROLES = [];   // [{id, name, description, is_active, permissions[], user_count}]
    let USERS = [];   // [{id, name, email, role_id, role_name, status}]
    let PERMISSIONS = [];   // [{id, key, group, description}] — da BD

    let activeTab = 'users';
    let selectedRoleId = null;
    let matrixSearchQ = '';
    let roleModalMode = 'create';
    let roleModalId = null;
    let roleModalPerms = new Set();
    let userModalId = null;

    // Presets locais (para preencher rapidamente o modal de role)
    const PRESETS = {
        viewer: ['assets.view', 'docs.view', 'frameworks.view', 'assessments.view', 'risk.view'],
        auditor: ['assets.view', 'docs.view', 'docs.approve_links', 'frameworks.view', 'assessments.view', 'risk.view', 'audit.view', 'chat.view_logs'],
        grc: ['assets.view', 'assets.create', 'assets.edit', 'docs.view', 'docs.upload', 'docs.version', 'docs.link_controls', 'docs.approve_links', 'docs.change_status', 'frameworks.view', 'frameworks.import', 'assessments.view', 'assessments.run', 'assessments.edit', 'risk.view', 'risk.plan.manage', 'risk.accept', 'chat.use', 'audit.view'],
        admin: null, // null = todas
    };

    // ── Toast ────────────────────────────────────────────────────────
    function toast(msg, type = 'ok') {
        let t = document.getElementById('rbacToast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'rbacToast';
            t.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:99999;display:none;min-width:240px';
            document.body.appendChild(t);
        }
        const color = type === 'err' ? '#ef4444' : type === 'warn' ? '#f59e0b' : '#22c55e';
        t.innerHTML = `<div class="panel" style="border-left:3px solid ${color};font-size:13px">${esc(msg)}</div>`;
        t.style.display = '';
        clearTimeout(toast._t);
        toast._t = setTimeout(() => (t.style.display = 'none'), 3000);
    }

    function tagHtml(active) {
        return active
            ? `<span class="tag ok"><span class="s"></span> Ativo</span>`
            : `<span class="tag bad"><span class="s"></span> Desativado</span>`;
    }

    function initials(name) {
        return (name || '?').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
    }

    // ── Carregar dados ───────────────────────────────────────────────
    async function loadAll() {
        try {
            [ROLES, USERS, PERMISSIONS] = await Promise.all([
                apiGet('/api/rbac/roles'),
                apiGet('/api/rbac/users'),
                apiGet('/api/rbac/permissions'),
            ]);

            // Seleccionar o primeiro papel activo por defeito
            if (!selectedRoleId && ROLES.length) {
                selectedRoleId = ROLES.find(r => r.is_active)?.id ?? ROLES[0].id;
            }

            renderUsers();
            renderRoles();
            renderMatrix();
            updateTabCounts();
        } catch (e) {
            toast('Erro ao carregar dados RBAC: ' + e.message, 'err');
            console.error(e);
        }
    }

    // ── Tab navigation ───────────────────────────────────────────────
    function switchTab(id) {
        activeTab = id;
        $$('.rbac-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === id));
        $$('.rbac-panel').forEach(p => p.classList.toggle('active', p.id === `panel-${id}`));
        updateTabCounts();
    }

    function updateTabCounts() {
        const selRole = ROLES.find(r => r.id === selectedRoleId);
        const permCount = selRole ? selRole.permissions.length : 0;

        const ut = $('[data-tab="users"] .rbac-tab-count');
        const rt = $('[data-tab="roles"] .rbac-tab-count');
        const mt = $('[data-tab="matrix"] .rbac-tab-count');
        if (ut) ut.textContent = USERS.length;
        if (rt) rt.textContent = ROLES.length;
        if (mt) mt.textContent = `${permCount} perms`;
    }

    // ── Users tab ────────────────────────────────────────────────────
    function renderUsers() {
        const q = ($('#userSearch')?.value || '').toLowerCase().trim();
        const st = $('#userStatusFilter')?.value || 'all';
        const tbody = $('#usersTbody');
        if (!tbody) return;

        const filtered = USERS.filter(u => {
            const matchQ = !q || (u.name || '').toLowerCase().includes(q) || (u.email || '').toLowerCase().includes(q);
            const matchSt = st === 'all' || u.status === st;
            return matchQ && matchSt;
        });

        if (!filtered.length) {
            tbody.innerHTML = `<tr><td colspan="4"><div class="empty-state"><p>Nenhum utilizador encontrado.</p></div></td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        filtered.forEach(u => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>
                <div class="user-cell">
                    <div class="user-avatar">${esc(initials(u.name))}</div>
                    <div>
                        <div class="user-cell-name">${esc(u.name)}</div>
                        <div class="user-cell-email">${esc(u.email)}</div>
                    </div>
                </div>
            </td>
            <td><span class="chip">${esc(u.role_name || '—')}</span></td>
            <td>${tagHtml(u.status === 'active')}</td>
            <td>
                <div style="display:flex;gap:6px">
                    <button class="btn btn-sm" data-open-user="${u.id}">Editar papel</button>
                    <button class="btn btn-sm ${u.status === 'active' ? 'warn' : 'ok'}" data-toggle-user="${u.id}">
                        ${u.status === 'active' ? 'Desativar' : 'Ativar'}
                    </button>
                </div>
            </td>`;
            tbody.appendChild(tr);
        });

        $$('[data-open-user]', tbody).forEach(b => b.addEventListener('click', () => openUserModal(Number(b.dataset.openUser))));
        $$('[data-toggle-user]', tbody).forEach(b => b.addEventListener('click', () => toggleUser(Number(b.dataset.toggleUser))));
    }

    async function toggleUser(userId) {
        const u = USERS.find(x => x.id === userId);
        if (!u) return;
        try {
            const res = await apiPatch(`/api/rbac/users/${userId}/toggle`);
            u.status = res.status;
            renderUsers();
            toast(`Utilizador ${u.email}: ${res.status === 'active' ? 'activado' : 'desactivado'}.`);
        } catch (e) {
            toast('Erro: ' + e.message, 'err');
        }
    }

    // ── Roles tab ────────────────────────────────────────────────────
    function renderRoles() {
        const q = ($('#roleSearch')?.value || '').toLowerCase().trim();
        const flt = $('#roleActiveFilter')?.value || 'all';
        const wrap = $('#rolesGrid');
        if (!wrap) return;

        const filtered = ROLES.filter(r => {
            const matchQ = !q || r.name.toLowerCase().includes(q) || (r.description || '').toLowerCase().includes(q);
            const matchSt = flt === 'all' || (flt === 'active' ? r.is_active : !r.is_active);
            return matchQ && matchSt;
        });

        if (!filtered.length) {
            wrap.innerHTML = `<div class="empty-state"><p>Nenhum papel encontrado.</p></div>`;
            return;
        }

        wrap.innerHTML = '';
        filtered.forEach(r => {
            const isSelected = r.id === selectedRoleId;
            const card = document.createElement('div');
            card.className = `role-card ${isSelected ? 'selected' : ''} ${!r.is_active ? 'inactive' : ''}`;
            card.innerHTML = `
            <div class="role-card-head">
                <div>
                    <div class="role-card-name">${esc(r.name)}</div>
                    <div class="role-card-desc">${esc(r.description || '—')}</div>
                </div>
                ${tagHtml(r.is_active)}
            </div>
            <div class="role-card-meta">
                <span class="chip"><b>${r.permissions.length}</b> permissões</span>
                <span class="chip">${r.user_count} utilizador${r.user_count !== 1 ? 'es' : ''}</span>
            </div>
            <div class="role-card-actions">
                <button class="btn ${isSelected ? 'primary' : ''}" data-select-role="${r.id}">
                    ${isSelected ? '✓ Na matriz' : 'Ver matriz'}
                </button>
                <button class="btn" data-edit-role="${r.id}">Editar</button>
                <button class="btn" data-clone-role="${r.id}">Clonar</button>
                <button class="btn warn" data-toggle-role="${r.id}">
                    ${r.is_active ? 'Desativar' : 'Ativar'}
                </button>
            </div>`;
            wrap.appendChild(card);
        });

        $$('[data-select-role]', wrap).forEach(b => b.addEventListener('click', () => {
            selectedRoleId = Number(b.dataset.selectRole);
            renderRoles(); renderMatrix(); updateTabCounts(); switchTab('matrix');
        }));
        $$('[data-edit-role]', wrap).forEach(b => b.addEventListener('click', () => openRoleModal('edit', Number(b.dataset.editRole))));
        $$('[data-clone-role]', wrap).forEach(b => b.addEventListener('click', () => cloneRole(Number(b.dataset.cloneRole))));
        $$('[data-toggle-role]', wrap).forEach(b => b.addEventListener('click', () => toggleRole(Number(b.dataset.toggleRole))));
    }

    async function toggleRole(roleId) {
        const r = ROLES.find(x => x.id === roleId);
        if (!r) return;
        try {
            const res = await apiPatch(`/api/rbac/roles/${roleId}/toggle`);
            r.is_active = res.is_active;
            renderRoles(); renderMatrix(); updateTabCounts();
            toast(`Papel "${r.name}": ${res.is_active ? 'activado' : 'desactivado'}.`);
        } catch (e) {
            toast('Erro: ' + e.message, 'err');
        }
    }

    async function cloneRole(roleId) {
        const src = ROLES.find(x => x.id === roleId);
        if (!src) return;
        try {
            const res = await apiPost('/api/rbac/roles', {
                name: `${src.name} (Clone)`,
                description: src.description,
                permissions: src.permissions,
            });
            toast(`Papel "${src.name}" clonado.`);
            await loadAll();
        } catch (e) {
            toast('Erro ao clonar: ' + e.message, 'err');
        }
    }

    // ── Matrix tab ───────────────────────────────────────────────────
    function syncRolePicker() {
        const sel = $('#matrixRolePicker');
        if (!sel) return;
        sel.innerHTML = ROLES.map(r =>
            `<option value="${r.id}" ${r.id === selectedRoleId ? 'selected' : ''}>${esc(r.name)}${r.is_active ? '' : ' (desativado)'}</option>`
        ).join('');
    }

    function renderMatrix() {
        const role = ROLES.find(r => r.id === selectedRoleId);
        if (!role) return;

        syncRolePicker();

        // Sidebar stats
        const name = $('#matrixRoleName');
        const status = $('#matrixRoleStatus');
        const count = $('#matrixPermCount');
        const total = $('#matrixPermTotal');
        if (name) name.textContent = role.name;
        if (status) status.innerHTML = tagHtml(role.is_active);
        if (count) count.textContent = role.permissions.length;
        if (total) total.textContent = PERMISSIONS.length;

        const kpiName = $('#kpiRoleName');
        const kpiCount = $('#kpiPermCount');
        if (kpiName) kpiName.textContent = role.name;
        if (kpiCount) kpiCount.textContent = role.permissions.length;

        const q = matrixSearchQ.toLowerCase().trim();

        // Agrupar permissões por group
        const groups = new Map();
        PERMISSIONS.forEach(p => {
            const g = p.group || 'Geral';
            if (!groups.has(g)) groups.set(g, []);
            groups.get(g).push(p);
        });

        const wrap = $('#permGroups');
        if (!wrap) return;
        wrap.innerHTML = '';

        const currentPerms = new Set(role.permissions);

        groups.forEach((items, groupName) => {
            const filtered = items.filter(p =>
                !q || p.key.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q) || groupName.toLowerCase().includes(q)
            );
            if (!filtered.length) return;

            const onCount = filtered.filter(p => currentPerms.has(p.key)).length;

            const g = document.createElement('div');
            g.className = 'perm-group';
            g.innerHTML = `
            <div class="perm-group-head">
                <div>
                    <span class="perm-group-title">${esc(groupName)}</span>
                    <span class="perm-group-count" style="margin-left:8px">${onCount}/${filtered.length} ativas</span>
                </div>
                <div class="perm-group-btns">
                    <button class="btn btn-sm" data-group-all="${esc(groupName)}">Tudo</button>
                    <button class="btn btn-sm" data-group-none="${esc(groupName)}">Nada</button>
                </div>
            </div>
            <div class="perm-items">
                ${filtered.map(p => {
                const on = currentPerms.has(p.key);
                const dis = !role.is_active;
                return `
                    <div class="perm-item ${on ? 'is-on' : ''}">
                        <div class="perm-item-left">
                            <div class="perm-item-key">${esc(p.key)}</div>
                            <div class="perm-item-desc">${esc(p.description || '')}</div>
                        </div>
                        <label class="toggle" title="${on ? 'Desativar' : 'Ativar'} permissão">
                            <input type="checkbox" data-perm="${esc(p.key)}" ${on ? 'checked' : ''} ${dis ? 'disabled' : ''}>
                            <span class="toggle-track"></span>
                            <span class="toggle-thumb"></span>
                        </label>
                    </div>`;
            }).join('')}
            </div>`;
            wrap.appendChild(g);
        });

        // Toggle individual — guarda imediatamente na BD
        $$('[data-perm]', wrap).forEach(cb => {
            cb.addEventListener('change', () => saveSinglePerm(role, cb));
        });

        $$('[data-group-all]', wrap).forEach(b => b.addEventListener('click', () => saveGroup(role, b.dataset.groupAll, true)));
        $$('[data-group-none]', wrap).forEach(b => b.addEventListener('click', () => saveGroup(role, b.dataset.groupNone, false)));
    }

    async function saveSinglePerm(role, cb) {
        const key = cb.dataset.perm;
        const enabled = cb.checked;

        // Optimistic UI
        if (enabled) role.permissions.push(key);
        else role.permissions = role.permissions.filter(k => k !== key);

        const row = cb.closest('.perm-item');
        if (row) row.classList.toggle('is-on', enabled);
        updateTabCounts();
        renderRoles();

        try {
            await apiPut(`/api/rbac/roles/${role.id}`, { permissions: role.permissions });
            toast(`${role.name}: ${key} → ${enabled ? 'ON' : 'OFF'}`);
        } catch (e) {
            // Reverter em caso de erro
            if (enabled) role.permissions = role.permissions.filter(k => k !== key);
            else role.permissions.push(key);
            cb.checked = !enabled;
            if (row) row.classList.toggle('is-on', !enabled);
            toast('Erro ao guardar: ' + e.message, 'err');
        }
    }

    async function saveGroup(role, groupName, enable) {
        const keys = PERMISSIONS.filter(p => (p.group || 'Geral') === groupName).map(p => p.key);
        if (enable) {
            keys.forEach(k => { if (!role.permissions.includes(k)) role.permissions.push(k); });
        } else {
            role.permissions = role.permissions.filter(k => !keys.includes(k));
        }

        try {
            await apiPut(`/api/rbac/roles/${role.id}`, { permissions: role.permissions });
            toast(`"${groupName}": ${enable ? 'tudo ON' : 'tudo OFF'}`);
            renderMatrix(); renderRoles(); updateTabCounts();
        } catch (e) {
            toast('Erro ao guardar: ' + e.message, 'err');
            await loadAll(); // Reverter ao estado da BD
        }
    }

    // ── Role modal ───────────────────────────────────────────────────
    function openRoleModal(mode, roleId = null) {
        roleModalMode = mode;
        roleModalId = roleId;

        const title = $('#roleModalTitle');

        if (mode === 'create') {
            if (title) title.textContent = 'Criar papel';
            $('#rmName').value = '';
            $('#rmDesc').value = '';
            $('#rmActive').value = 'true';
            roleModalPerms = new Set();
        } else {
            const r = ROLES.find(x => x.id === roleId);
            if (!r) return;
            if (title) title.textContent = `Editar "${r.name}"`;
            $('#rmName').value = r.name;
            $('#rmDesc').value = r.description || '';
            $('#rmActive').value = r.is_active ? 'true' : 'false';
            roleModalPerms = new Set(r.permissions);
        }

        updateRoleModalPreview();
        openModalEl('roleModal');
    }

    function updateRoleModalPreview() {
        const preview = $('#rmPermPreview');
        if (!preview) return;
        const arr = [...roleModalPerms].sort();
        preview.innerHTML = arr.length
            ? arr.map(k => `<span class="perm-preview-tag">${esc(k)}</span>`).join('')
            : '<span style="color:var(--muted)">Nenhuma permissão selecionada.</span>';
    }

    function applyPreset(presetKey) {
        let keys = PRESETS[presetKey];
        if (!keys) keys = PERMISSIONS.map(p => p.key); // admin = todas
        roleModalPerms = new Set(keys);
        updateRoleModalPreview();
        // Highlight botão activo
        $$('[data-preset]').forEach(b => b.classList.toggle('active', b.dataset.preset === presetKey));
    }

    async function saveRoleModal() {
        const name = $('#rmName').value.trim();
        if (!name) { toast('Nome do papel é obrigatório.', 'err'); return; }
        const desc = $('#rmDesc').value.trim();
        const isActive = $('#rmActive').value === 'true';
        const btn = $('#rmSave');

        btn.disabled = true;
        btn.textContent = 'A guardar...';

        try {
            if (roleModalMode === 'create') {
                await apiPost('/api/rbac/roles', {
                    name,
                    description: desc,
                    permissions: [...roleModalPerms],
                });
                toast(`Papel "${name}" criado.`);
            } else {
                await apiPut(`/api/rbac/roles/${roleModalId}`, {
                    name,
                    description: desc,
                    is_active: isActive,
                    permissions: [...roleModalPerms],
                });
                toast(`Papel "${name}" atualizado.`);
            }
            await loadAll();
            closeModalEl('roleModal');
        } catch (e) {
            toast('Erro: ' + e.message, 'err');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Guardar';
        }
    }

    async function deleteRole(roleId) {
        const r = ROLES.find(x => x.id === roleId);
        if (!r) return;
        if (!confirm(`Apagar papel "${r.name}"? Esta acção não pode ser desfeita.`)) return;
        try {
            await apiDelete(`/api/rbac/roles/${roleId}`);
            toast(`Papel "${r.name}" apagado.`);
            await loadAll();
        } catch (e) {
            toast('Erro: ' + e.message, 'err');
        }
    }

    // ── User modal ───────────────────────────────────────────────────
    function openUserModal(userId) {
        const u = USERS.find(x => x.id === userId);
        if (!u) return;
        userModalId = userId;

        $('#userModalTitle').textContent = u.name;
        $('#umEmail').textContent = u.email;
        $('#umStatusBadge').innerHTML = tagHtml(u.status === 'active');

        const sel = $('#umRole');
        sel.innerHTML = ROLES.map(r =>
            `<option value="${r.id}" ${r.id === u.role_id ? 'selected' : ''}>${esc(r.name)}${r.is_active ? '' : ' (desativado)'}</option>`
        ).join('');

        sel.onchange = () => refreshUserPerms(Number(sel.value));
        refreshUserPerms(u.role_id);
        openModalEl('userModal');
    }

    function refreshUserPerms(roleId) {
        const r = ROLES.find(x => x.id === roleId);
        const wrap = $('#umPermsWrap');
        if (!wrap) return;
        const arr = r ? [...r.permissions].sort() : [];
        wrap.innerHTML = arr.length
            ? arr.map(k => `<span class="perm-preview-tag">${esc(k)}</span>`).join('')
            : '<span style="color:var(--muted);font-size:12px">Sem permissões.</span>';
    }

    async function saveUserModal() {
        const u = USERS.find(x => x.id === userModalId);
        if (!u) return;
        const newRoleId = Number($('#umRole').value);
        const btn = $('#umSave');

        btn.disabled = true;
        btn.textContent = 'A guardar...';

        try {
            await apiPut(`/api/rbac/users/${userModalId}/role`, { role_id: newRoleId });
            const roleName = ROLES.find(x => x.id === newRoleId)?.name || '—';
            toast(`Papel de ${u.name} → "${roleName}".`);
            await loadAll();
            closeModalEl('userModal');
        } catch (e) {
            toast('Erro: ' + e.message, 'err');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Guardar';
        }
    }

    async function createUser() {
        const name = $('#auName').value.trim();
        const email = $('#auEmail').value.trim().toLowerCase();

        // Vamos buscar a password (que vamos adicionar no rbac.blade.php)
        const pwdEl = $('#auPassword');
        const password = pwdEl ? pwdEl.value : 'admin123'; // Fallback temporário se o campo não existir

        const roleId = Number($('#auRole').value);
        const status = $('#auStatus').value;

        if (!name) { toast('Nome é obrigatório.', 'err'); return; }
        if (!email || !email.includes('@')) { toast('Email inválido.', 'err'); return; }
        if (!password || password.length < 6) { toast('A password deve ter pelo menos 6 caracteres.', 'err'); return; }

        const btn = $('#auCreate');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'A criar...';
        }

        try {
            // Envia para o nosso novo endpoint no RbacController
            await apiPost('/api/rbac/users', {
                name: name,
                email: email,
                password: password,
                role_id: roleId,
                status: status
            });

            toast(`Utilizador ${name} criado com sucesso!`);

            // Limpa o formulário
            $('#auName').value = '';
            $('#auEmail').value = '';
            if (pwdEl) pwdEl.value = '';

            await loadAll(); // Recarrega a tabela diretamente da BD
            closeModalEl('addUserModal');
        } catch (e) {
            toast('Erro ao criar utilizador: ' + e.message, 'err');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Criar';
            }
        }
    }

    // ── Modal helpers ────────────────────────────────────────────────
    function openModalEl(id) {
        const m = document.getElementById(id);
        if (!m) return;
        m.classList.remove('is-hidden');
        m.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeModalEl(id) {
        const m = document.getElementById(id);
        if (!m) return;

        // 1. Remove o foco ativo de qualquer botão/input dentro do modal
        if (document.activeElement && m.contains(document.activeElement)) {
            document.activeElement.blur();
        }

        m.classList.add('is-hidden');
        m.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // ── Init ─────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        // Tabs
        $$('.rbac-tab').forEach(b => b.addEventListener('click', () => switchTab(b.dataset.tab)));

        // Users
        $('#userSearch')?.addEventListener('input', renderUsers);
        $('#userStatusFilter')?.addEventListener('change', renderUsers);
        $('#btnAddUser')?.addEventListener('click', () => {
            // Redirigir para criar utilizador (gerir na página de admin)
            // ou usar modal se existir no blade
            openModalEl('addUserModal');
            // Preencher select de roles
            const sel = $('#auRole');
            if (sel) {
                sel.innerHTML = ROLES.map(r =>
                    `<option value="${r.id}">${esc(r.name)}</option>`
                ).join('');
            }
        });

        // Roles
        $('#roleSearch')?.addEventListener('input', renderRoles);
        $('#roleActiveFilter')?.addEventListener('change', renderRoles);
        $('#btnCreateRole')?.addEventListener('click', () => openRoleModal('create'));

        // Matrix
        $('#matrixRolePicker')?.addEventListener('change', e => {
            selectedRoleId = Number(e.target.value);
            renderMatrix(); renderRoles(); updateTabCounts();
        });
        $('#matrixPermSearch')?.addEventListener('input', e => {
            matrixSearchQ = e.target.value; renderMatrix();
        });
        $('#btnSelectAll')?.addEventListener('click', async () => {
            const r = ROLES.find(x => x.id === selectedRoleId);
            if (!r) return;
            r.permissions = PERMISSIONS.map(p => p.key);
            try {
                await apiPut(`/api/rbac/roles/${r.id}`, { permissions: r.permissions });
                toast('Todas as permissões activadas.');
            } catch (e) { toast('Erro: ' + e.message, 'err'); }
            renderMatrix(); renderRoles(); updateTabCounts();
        });
        $('#btnClearAll')?.addEventListener('click', async () => {
            const r = ROLES.find(x => x.id === selectedRoleId);
            if (!r) return;
            r.permissions = [];
            try {
                await apiPut(`/api/rbac/roles/${r.id}`, { permissions: [] });
                toast('Todas as permissões removidas.');
            } catch (e) { toast('Erro: ' + e.message, 'err'); }
            renderMatrix(); renderRoles(); updateTabCounts();
        });
        $('#btnDisableRole')?.addEventListener('click', () => toggleRole(selectedRoleId));
        $('#btnDeleteRole')?.addEventListener('click', () => deleteRole(selectedRoleId));

        // Role modal
        $('#roleModalClose')?.addEventListener('click', () => closeModalEl('roleModal'));
        $('#rmCancel')?.addEventListener('click', () => closeModalEl('roleModal'));
        $('#rmSave')?.addEventListener('click', saveRoleModal);
        $$('[data-preset]').forEach(b => b.addEventListener('click', () => applyPreset(b.dataset.preset)));
        document.getElementById('roleModal')?.addEventListener('click', e => {
            if (e.target.id === 'roleModal') closeModalEl('roleModal');
        });

        // User modal
        $('#userModalClose')?.addEventListener('click', () => closeModalEl('userModal'));
        $('#umCancel')?.addEventListener('click', () => closeModalEl('userModal'));
        $('#umSave')?.addEventListener('click', saveUserModal);
        document.getElementById('userModal')?.addEventListener('click', e => {
            if (e.target.id === 'userModal') closeModalEl('userModal');
        });

        // Add user modal (se existir)
        $('#addUserModalClose')?.addEventListener('click', () => closeModalEl('addUserModal'));
        $('#auCancel')?.addEventListener('click', () => closeModalEl('addUserModal'));
        document.getElementById('addUserModal')?.addEventListener('click', e => {
            if (e.target.id === 'addUserModal') closeModalEl('addUserModal');
        });

        // Botão Criar Utilizador
        $('#auCreate')?.addEventListener('click', createUser);

        // ESC
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            ['roleModal', 'userModal', 'addUserModal'].forEach(id => {
                if (!document.getElementById(id)?.classList.contains('is-hidden')) closeModalEl(id);
            });
        });

        // Carregar dados reais
        loadAll();
        switchTab('users');
    });

})();