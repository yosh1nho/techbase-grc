// ═══════════════════════════════════════════════════════════════
// Techbase GRC — RBAC (RF19)
// resources/js/pages/rbac.js
// ═══════════════════════════════════════════════════════════════

const $  = (s) => document.querySelector(s);
const $$ = (s) => [...document.querySelectorAll(s)];

// ─── DATA ────────────────────────────────────────────────────────

const PERMISSIONS = [
    { key: 'assets.view',           group: 'Ativos',        desc: 'Ver ativos e detalhes.' },
    { key: 'assets.create',         group: 'Ativos',        desc: 'Registar novos ativos.' },
    { key: 'assets.edit',           group: 'Ativos',        desc: 'Editar ativos existentes.' },
    { key: 'assets.delete',         group: 'Ativos',        desc: 'Eliminar ativos (restrito).' },
    { key: 'docs.view',             group: 'Documentos',    desc: 'Ver documentos e evidências.' },
    { key: 'docs.upload',           group: 'Documentos',    desc: 'Carregar documentos.' },
    { key: 'docs.version',          group: 'Documentos',    desc: 'Criar nova versão de documento.' },
    { key: 'docs.link_controls',    group: 'Documentos',    desc: 'Associar evidências a controlos.' },
    { key: 'docs.approve_links',    group: 'Documentos',    desc: 'Aprovar/rejeitar associações (RF16).' },
    { key: 'docs.change_status',    group: 'Documentos',    desc: 'Alterar status do documento.' },
    { key: 'frameworks.view',       group: 'Frameworks',    desc: 'Ver frameworks e controlos.' },
    { key: 'frameworks.import',     group: 'Frameworks',    desc: 'Importar/atualizar versões.' },
    { key: 'frameworks.edit',       group: 'Frameworks',    desc: 'Editar conteúdos normativos (restrito).' },
    { key: 'assessments.view',      group: 'Avaliações',    desc: 'Ver avaliações e histórico.' },
    { key: 'assessments.run',       group: 'Avaliações',    desc: 'Executar avaliação.' },
    { key: 'assessments.edit',      group: 'Avaliações',    desc: 'Editar resultados e evidências.' },
    { key: 'risk.view',             group: 'Risco',         desc: 'Ver riscos e matriz.' },
    { key: 'risk.plan.manage',      group: 'Risco',         desc: 'Gerir planos de tratamento (RF10-11).' },
    { key: 'risk.accept',           group: 'Risco',         desc: 'Aceitação formal de risco (RF12).' },
    { key: 'chat.use',              group: 'Chat / RAG',    desc: 'Usar chat de governação (RF15).' },
    { key: 'chat.view_logs',        group: 'Chat / RAG',    desc: 'Ver logs do chat e auditoria.' },
    { key: 'audit.view',            group: 'Auditoria',     desc: 'Consultar logs de auditoria (RNF5).' },
    { key: 'rbac.manage',           group: 'Admin / RBAC',  desc: 'Gerir papéis e permissões (RF19).' },
];

const ROLE_PRESETS = {
    viewer:  { label: 'Viewer',      keys: new Set(['assets.view','docs.view','frameworks.view','assessments.view','risk.view']) },
    auditor: { label: 'Auditor',     keys: new Set(['assets.view','docs.view','docs.approve_links','frameworks.view','assessments.view','risk.view','audit.view','chat.view_logs']) },
    grc:     { label: 'GRC Manager', keys: new Set(['assets.view','assets.create','assets.edit','docs.view','docs.upload','docs.version','docs.link_controls','docs.approve_links','docs.change_status','frameworks.view','frameworks.import','assessments.view','assessments.run','assessments.edit','risk.view','risk.plan.manage','risk.accept','chat.use','audit.view']) },
    admin:   { label: 'Admin',       keys: new Set(PERMISSIONS.map(p => p.key)) },
};

let roles = [
    { id: 'R1', name: 'Admin',       desc: 'Acesso total. Pode gerir papéis e permissões.', active: true, perms: new Set(ROLE_PRESETS.admin.keys) },
    { id: 'R2', name: 'GRC Manager', desc: 'Gestão de conformidade, risco e evidências.', active: true, perms: new Set(ROLE_PRESETS.grc.keys) },
    { id: 'R3', name: 'Auditor',     desc: 'Consulta e valida evidências, acessa auditoria.', active: true, perms: new Set(ROLE_PRESETS.auditor.keys) },
    { id: 'R4', name: 'Viewer',      desc: 'Somente leitura para dashboards e dados.', active: true, perms: new Set(ROLE_PRESETS.viewer.keys) },
];

let users = [
    { id: 'U1', name: 'Ana Silva',   email: 'ana@empresa.pt',   roleId: 'R2', status: 'active' },
    { id: 'U2', name: 'João Costa',  email: 'joao@empresa.pt',  roleId: 'R4', status: 'active' },
    { id: 'U3', name: 'Pedro Nunes', email: 'pedro@empresa.pt', roleId: 'R3', status: 'active' },
    { id: 'U4', name: 'Rita Alves',  email: 'rita@empresa.pt',  roleId: 'R1', status: 'active' },
];

// ─── STATE ───────────────────────────────────────────────────────

let activeTab        = 'users';
let selectedRoleId   = 'R2';
let matrixSearchQ    = '';
let userModalId      = null;
let roleModalMode    = 'create';
let roleModalId      = null;
let roleModalPerms   = new Set();
let roleModalPreset  = null;

// ─── HELPERS ─────────────────────────────────────────────────────

const roleById = (id) => roles.find(r => r.id === id);
const userById = (id) => users.find(u => u.id === id);
const normalize = (s) => (s || '').toLowerCase().trim();
const esc = (s) => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

function initials(name) {
    return (name || '?').split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
}

function tagHtml(active) {
    return active
        ? `<span class="tag ok"><span class="s"></span> Ativo</span>`
        : `<span class="tag bad"><span class="s"></span> Desativado</span>`;
}

function groupedPerms() {
    const map = new Map();
    PERMISSIONS.forEach(p => {
        if (!map.has(p.group)) map.set(p.group, []);
        map.get(p.group).push(p);
    });
    return map;
}

function logAudit(msg) {
    const box = $('#auditLog');
    if (!box) return;
    const ts  = new Date().toTimeString().slice(0,8);
    const row = document.createElement('div');
    row.className = 'audit-log-entry';
    row.innerHTML = `<span class="audit-log-ts">${ts}</span><span class="audit-log-msg">${esc(msg)}</span>`;
    box.prepend(row);
    // keep last 50 entries
    [...box.children].slice(50).forEach(el => el.remove());
}

function openModal(id)  {
    const m = document.getElementById(id);
    m?.classList.remove('is-hidden');
    m?.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    const m = document.getElementById(id);
    m?.classList.add('is-hidden');
    m?.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
}

function toast(msg, type = 'ok') {
    let t = document.getElementById('rbacToast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'rbacToast';
        t.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:99999;display:none;';
        document.body.appendChild(t);
    }
    const color = type === 'err' ? '#ef4444' : type === 'warn' ? '#f59e0b' : '#22c55e';
    t.innerHTML = `<div class="panel" style="border-left:3px solid ${color};min-width:240px;font-size:13px;">${esc(msg)}</div>`;
    t.style.display = '';
    clearTimeout(toast._t);
    toast._t = setTimeout(() => (t.style.display = 'none'), 2800);
}

// ─── TAB NAVIGATION ──────────────────────────────────────────────

function switchTab(id) {
    activeTab = id;
    $$('.rbac-tab').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === id));
    $$('.rbac-panel').forEach(p => p.classList.toggle('active', p.id === `panel-${id}`));
    updateTabCounts();
}

function updateTabCounts() {
    const userCount = users.length;
    const roleCount = roles.length;
    const selRole   = roleById(selectedRoleId);
    const permCount = selRole ? selRole.perms.size : 0;

    const ut = document.querySelector('[data-tab="users"] .rbac-tab-count');
    const rt = document.querySelector('[data-tab="roles"] .rbac-tab-count');
    const mt = document.querySelector('[data-tab="matrix"] .rbac-tab-count');
    if (ut) ut.textContent = userCount;
    if (rt) rt.textContent = roleCount;
    if (mt) mt.textContent = `${permCount} perms`;
}

// ─── USERS TAB ───────────────────────────────────────────────────

function renderUsers() {
    const q  = normalize($('#userSearch')?.value);
    const st = $('#userStatusFilter')?.value || 'all';
    const tbody = $('#usersTbody');
    if (!tbody) return;

    const filtered = users.filter(u => {
        const matchQ  = !q || normalize(u.name).includes(q) || normalize(u.email).includes(q);
        const matchSt = st === 'all' || u.status === st;
        return matchQ && matchSt;
    });

    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="4"><div class="empty-state"><p>Nenhum utilizador encontrado.</p></div></td></tr>`;
        return;
    }

    tbody.innerHTML = '';
    filtered.forEach(u => {
        const role = roleById(u.roleId);
        const tr   = document.createElement('tr');
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
            <td>
                <span class="chip">${role ? esc(role.name) : '—'}</span>
            </td>
            <td>${tagHtml(u.status === 'active')}</td>
            <td>
                <div style="display:flex;gap:6px;">
                    <button class="btn btn-sm" type="button" data-open-user="${u.id}">
                        <i data-lucide="pencil" style="width:13px;height:13px;"></i> Editar
                    </button>
                    <button class="btn btn-sm ${u.status === 'active' ? 'warn' : 'ok'}" type="button" data-toggle-user="${u.id}">
                        ${u.status === 'active' ? 'Desativar' : 'Ativar'}
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    $$('[data-open-user]').forEach(b => b.addEventListener('click', () => openUserModal(b.dataset.openUser)));
    $$('[data-toggle-user]').forEach(b => b.addEventListener('click', () => toggleUserStatus(b.dataset.toggleUser)));

    if (window.lucide) window.lucide.createIcons();
}

function toggleUserStatus(userId) {
    const u = userById(userId);
    if (!u) return;
    u.status = u.status === 'active' ? 'disabled' : 'active';
    logAudit(`Utilizador ${u.email} → ${u.status === 'active' ? 'ATIVO' : 'DESATIVADO'}`);
    renderUsers();
    updateTabCounts();
}

// ─── ROLES TAB ───────────────────────────────────────────────────

function renderRoles() {
    const q   = normalize($('#roleSearch')?.value);
    const flt = $('#roleActiveFilter')?.value || 'all';
    const wrap = $('#rolesGrid');
    if (!wrap) return;

    const filtered = roles.filter(r => {
        const matchQ  = !q || normalize(r.name).includes(q) || normalize(r.desc).includes(q);
        const matchSt = flt === 'all' || (flt === 'active' ? r.active : !r.active);
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
        card.className = `role-card ${isSelected ? 'selected' : ''} ${!r.active ? 'inactive' : ''}`;
        card.innerHTML = `
            <div class="role-card-head">
                <div>
                    <div class="role-card-name">${esc(r.name)}</div>
                    <div class="role-card-desc">${esc(r.desc || '—')}</div>
                </div>
                ${tagHtml(r.active)}
            </div>
            <div class="role-card-meta">
                <span class="chip"><b>${r.perms.size}</b> permissões</span>
                <span class="chip" style="font-family:var(--font-mono);font-size:10px;">${esc(r.id)}</span>
            </div>
            <div class="role-card-actions">
                <button class="btn ${isSelected ? 'primary' : ''}" type="button" data-select-role="${r.id}">
                    <i data-lucide="${isSelected ? 'check' : 'crosshair'}" style="width:12px;height:12px;"></i>
                    ${isSelected ? 'Na matriz' : 'Ver matriz'}
                </button>
                <button class="btn" type="button" data-edit-role="${r.id}">
                    <i data-lucide="pencil" style="width:12px;height:12px;"></i> Editar
                </button>
                <button class="btn" type="button" data-clone-role="${r.id}">
                    <i data-lucide="copy" style="width:12px;height:12px;"></i> Clonar
                </button>
                <button class="btn warn" type="button" data-toggle-role="${r.id}">
                    ${r.active ? 'Desativar' : 'Ativar'}
                </button>
            </div>
        `;
        wrap.appendChild(card);
    });

    $$('[data-select-role]').forEach(b => b.addEventListener('click', () => {
        setSelectedRole(b.dataset.selectRole);
        switchTab('matrix');
    }));
    $$('[data-edit-role]').forEach(b  => b.addEventListener('click', () => openRoleModal('edit', b.dataset.editRole)));
    $$('[data-clone-role]').forEach(b => b.addEventListener('click', () => cloneRole(b.dataset.cloneRole)));
    $$('[data-toggle-role]').forEach(b => b.addEventListener('click', () => toggleRoleActive(b.dataset.toggleRole)));

    if (window.lucide) window.lucide.createIcons();
}

function setSelectedRole(roleId) {
    selectedRoleId = roleId;
    logAudit(`Selecionou papel: ${roleById(roleId)?.name}`);
    renderRoles();
    renderMatrix();
    updateTabCounts();
    syncRolePicker();
}

function toggleRoleActive(roleId) {
    const r = roleById(roleId);
    if (!r) return;
    r.active = !r.active;
    logAudit(`Papel "${r.name}" → ${r.active ? 'ATIVO' : 'DESATIVADO'}`);
    renderRoles();
    renderMatrix();
    updateTabCounts();
}

function cloneRole(roleId) {
    const src = roleById(roleId);
    if (!src) return;
    const newId = 'R' + (Math.floor(Math.random() * 9000) + 1000);
    roles.unshift({ id: newId, name: `${src.name} (Clone)`, desc: src.desc, active: true, perms: new Set(src.perms) });
    logAudit(`Clonou papel "${src.name}"`);
    renderRoles();
    syncRolePicker();
    updateTabCounts();
    toast(`Papel "${src.name}" clonado com sucesso`);
}

// ─── MATRIX TAB ──────────────────────────────────────────────────

function syncRolePicker() {
    const sel = $('#matrixRolePicker');
    if (!sel) return;
    const prev = sel.value;
    sel.innerHTML = '';
    roles.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.textContent = r.name + (r.active ? '' : ' (desativado)');
        sel.appendChild(opt);
    });
    sel.value = selectedRoleId || prev;
}

function renderMatrix() {
    const role = roleById(selectedRoleId);
    if (!role) return;

    syncRolePicker();

    // sidebar stats
    const statName   = $('#matrixRoleName');
    const statStatus = $('#matrixRoleStatus');
    const statCount  = $('#matrixPermCount');
    const statTotal  = $('#matrixPermTotal');
    if (statName)   statName.textContent   = role.name;
    if (statStatus) statStatus.innerHTML   = tagHtml(role.active);
    if (statCount)  statCount.textContent  = role.perms.size;
    if (statTotal)  statTotal.textContent  = PERMISSIONS.length;

    // KPI chips
    const kpiName  = $('#kpiRoleName');
    const kpiCount = $('#kpiPermCount');
    if (kpiName)  kpiName.textContent  = role.name;
    if (kpiCount) kpiCount.textContent = role.perms.size;

    const q = normalize(matrixSearchQ);
    const groups = groupedPerms();
    const wrap = $('#permGroups');
    if (!wrap) return;
    wrap.innerHTML = '';

    groups.forEach((items, groupName) => {
        const filtered = items.filter(p =>
            !q || normalize(p.key).includes(q) || normalize(p.desc).includes(q) || normalize(p.group).includes(q)
        );
        if (!filtered.length) return;

        const onCount = filtered.filter(p => role.perms.has(p.key)).length;

        const g = document.createElement('div');
        g.className = 'perm-group';
        g.innerHTML = `
            <div class="perm-group-head">
                <div>
                    <span class="perm-group-title">${esc(groupName)}</span>
                    <span class="perm-group-count" style="margin-left:8px;">${onCount}/${filtered.length} ativas</span>
                </div>
                <div class="perm-group-btns">
                    <button class="btn btn-sm" type="button" data-group-all="${esc(groupName)}">Tudo</button>
                    <button class="btn btn-sm" type="button" data-group-none="${esc(groupName)}">Nada</button>
                </div>
            </div>
            <div class="perm-items">
                ${filtered.map(p => {
                    const on = role.perms.has(p.key);
                    const dis = !role.active;
                    return `
                    <div class="perm-item ${on ? 'is-on' : ''}">
                        <div class="perm-item-left">
                            <div class="perm-item-key">${esc(p.key)}</div>
                            <div class="perm-item-desc">${esc(p.desc)}</div>
                        </div>
                        <label class="toggle" title="${on ? 'Desativar' : 'Ativar'} permissão">
                            <input type="checkbox" data-perm="${esc(p.key)}" ${on ? 'checked' : ''} ${dis ? 'disabled' : ''}>
                            <span class="toggle-track"></span>
                            <span class="toggle-thumb"></span>
                        </label>
                    </div>`;
                }).join('')}
            </div>
        `;
        wrap.appendChild(g);
    });

    $$('[data-perm]').forEach(cb => {
        cb.addEventListener('change', () => {
            const key = cb.dataset.perm;
            if (cb.checked) role.perms.add(key); else role.perms.delete(key);
            const row = cb.closest('.perm-item');
            if (row) row.classList.toggle('is-on', cb.checked);
            logAudit(`${role.name}: ${key} → ${cb.checked ? 'ON' : 'OFF'}`);
            updateTabCounts();
            renderMatrix(); // refresh counts
            renderRoles();
        });
    });

    $$('[data-group-all]').forEach(b => b.addEventListener('click', () => setGroup(b.dataset.groupAll, true)));
    $$('[data-group-none]').forEach(b => b.addEventListener('click', () => setGroup(b.dataset.groupNone, false)));
}

function setGroup(groupName, enable) {
    const role = roleById(selectedRoleId);
    if (!role) return;
    PERMISSIONS.filter(p => p.group === groupName).forEach(p => enable ? role.perms.add(p.key) : role.perms.delete(p.key));
    logAudit(`${role.name}: grupo "${groupName}" → ${enable ? 'TUDO ON' : 'TUDO OFF'}`);
    renderMatrix();
    renderRoles();
    updateTabCounts();
}

// ─── ROLE MODAL ──────────────────────────────────────────────────

function openRoleModal(mode, roleId = null) {
    roleModalMode = mode;
    roleModalId   = roleId;
    roleModalPreset = null;

    const title = $('#roleModalTitle');
    if (mode === 'create') {
        if (title) title.textContent = 'Criar papel';
        $('#rmName').value  = '';
        $('#rmDesc').value  = '';
        $('#rmActive').value = 'true';
        roleModalPerms = new Set();
    } else {
        const r = roleById(roleId);
        if (!r) return;
        if (title) title.textContent = `Editar "${r.name}"`;
        $('#rmName').value   = r.name;
        $('#rmDesc').value   = r.desc || '';
        $('#rmActive').value = r.active ? 'true' : 'false';
        roleModalPerms = new Set(r.perms);
    }
    updateRoleModalPreview();
    updatePresetBtns();
    openModal('roleModal');
}

function closeRoleModal() { closeModal('roleModal'); }

function updateRoleModalPreview() {
    const preview = $('#rmPermPreview');
    if (!preview) return;
    const arr = [...roleModalPerms].sort();
    if (!arr.length) { preview.innerHTML = '<span style="color:var(--muted);">Nenhuma permissão selecionada.</span>'; return; }
    preview.innerHTML = arr.map(k => `<span class="perm-preview-tag">${esc(k)}</span>`).join('');
}

function updatePresetBtns() {
    $$('[data-preset]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.preset === roleModalPreset);
        const p = ROLE_PRESETS[btn.dataset.preset];
        btn.querySelector('.preset-btn-count').textContent = `${p.keys.size} permissões`;
    });
}

function applyPreset(presetKey) {
    const p = ROLE_PRESETS[presetKey];
    if (!p) return;
    roleModalPerms  = new Set(p.keys);
    roleModalPreset = presetKey;
    updateRoleModalPreview();
    updatePresetBtns();
}

function saveRoleModal() {
    const name = $('#rmName').value.trim();
    if (!name) { toast('Nome do papel é obrigatório.', 'err'); return; }
    const desc   = $('#rmDesc').value.trim();
    const active = $('#rmActive').value === 'true';

    if (roleModalMode === 'create') {
        const newId = 'R' + (Math.floor(Math.random() * 9000) + 1000);
        roles.unshift({ id: newId, name, desc, active, perms: new Set(roleModalPerms) });
        selectedRoleId = newId;
        logAudit(`Criou papel "${name}" (${roleModalPerms.size} permissões)`);
        toast(`Papel "${name}" criado`);
    } else {
        const r = roleById(roleModalId);
        if (!r) return;
        r.name   = name;
        r.desc   = desc;
        r.active = active;
        r.perms  = new Set(roleModalPerms);
        logAudit(`Editou papel "${name}" (${r.perms.size} permissões)`);
        toast(`Papel "${name}" atualizado`);
    }

    renderRoles();
    renderMatrix();
    syncRolePicker();
    updateTabCounts();
    closeRoleModal();
}

// ─── USER MODAL ──────────────────────────────────────────────────

function openUserModal(userId) {
    const u = userById(userId);
    if (!u) return;
    userModalId = userId;

    $('#userModalTitle').textContent = u.name;
    $('#umEmail').textContent        = u.email;
    $('#umStatusBadge').innerHTML    = tagHtml(u.status === 'active');

    const sel = $('#umRole');
    sel.innerHTML = '';
    roles.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.textContent = r.name + (r.active ? '' : ' (desativado)');
        sel.appendChild(opt);
    });
    sel.value = u.roleId;
    sel.addEventListener('change', () => refreshUserPerms(sel.value));

    refreshUserPerms(u.roleId);
    openModal('userModal');
}

function refreshUserPerms(roleId) {
    const r = roleById(roleId);
    const wrap = $('#umPermsWrap');
    if (!wrap) return;
    const arr = r ? [...r.perms].sort() : [];
    if (!arr.length) { wrap.innerHTML = '<span style="color:var(--muted);font-size:12px;">Sem permissões.</span>'; return; }
    wrap.innerHTML = arr.map(k => `<span class="perm-preview-tag">${esc(k)}</span>`).join('');
}

function saveUserModal() {
    const u = userById(userModalId);
    if (!u) return;
    const newRoleId = $('#umRole').value;
    const oldRole   = roleById(u.roleId)?.name;
    u.roleId = newRoleId;
    logAudit(`${u.email}: papel alterado de "${oldRole}" para "${roleById(newRoleId)?.name}"`);
    toast(`Papel de ${u.name} atualizado`);
    renderUsers();
    closeModal('userModal');
}

// ─── ADD USER MODAL ──────────────────────────────────────────────

function openAddUserModal() {
    const sel = $('#auRole');
    sel.innerHTML = '';
    roles.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.textContent = r.name + (r.active ? '' : ' (desativado)');
        sel.appendChild(opt);
    });
    sel.value = selectedRoleId;
    $('#auName').value  = '';
    $('#auEmail').value = '';
    $('#auStatus').value = 'active';
    openModal('addUserModal');
}

function createUser() {
    const name   = $('#auName').value.trim();
    const email  = $('#auEmail').value.trim().toLowerCase();
    const roleId = $('#auRole').value;
    const status = $('#auStatus').value;

    if (!name)  { toast('Nome é obrigatório.', 'err'); return; }
    if (!email || !email.includes('@')) { toast('Email inválido.', 'err'); return; }
    if (users.some(u => u.email === email)) { toast('Email já existe.', 'warn'); return; }

    const id = 'U' + (Math.floor(Math.random() * 9000) + 1000);
    users.unshift({ id, name, email, roleId, status });
    logAudit(`Criou utilizador ${email} com papel "${roleById(roleId)?.name}"`);
    toast(`Utilizador ${name} criado`);
    renderUsers();
    updateTabCounts();
    closeModal('addUserModal');
}

// ─── INIT ────────────────────────────────────────────────────────

function init() {
    // tabs
    $$('.rbac-tab').forEach(btn => btn.addEventListener('click', () => switchTab(btn.dataset.tab)));

    // users tab
    $('#userSearch')?.addEventListener('input', renderUsers);
    $('#userStatusFilter')?.addEventListener('change', renderUsers);
    $('#btnAddUser')?.addEventListener('click', openAddUserModal);

    // roles tab
    $('#roleSearch')?.addEventListener('input', renderRoles);
    $('#roleActiveFilter')?.addEventListener('change', renderRoles);
    $('#btnCreateRole')?.addEventListener('click', () => openRoleModal('create'));

    // matrix tab
    $('#matrixRolePicker')?.addEventListener('change', e => setSelectedRole(e.target.value));
    $('#matrixPermSearch')?.addEventListener('input', e => { matrixSearchQ = e.target.value; renderMatrix(); });
    $('#btnSelectAll')?.addEventListener('click', () => {
        const r = roleById(selectedRoleId); if (!r) return;
        PERMISSIONS.forEach(p => r.perms.add(p.key));
        logAudit(`${r.name}: todas as permissões ativadas`);
        renderMatrix(); renderRoles(); updateTabCounts();
    });
    $('#btnClearAll')?.addEventListener('click', () => {
        const r = roleById(selectedRoleId); if (!r) return;
        r.perms = new Set();
        logAudit(`${r.name}: todas as permissões removidas`);
        renderMatrix(); renderRoles(); updateTabCounts();
    });
    $('#btnDisableRole')?.addEventListener('click', () => toggleRoleActive(selectedRoleId));
    $('#btnResetPerms')?.addEventListener('click', () => {
        const r = roleById(selectedRoleId); if (!r) return;
        const preset = r.name.toLowerCase().includes('admin') ? ROLE_PRESETS.admin.keys : ROLE_PRESETS.viewer.keys;
        r.perms = new Set(preset);
        logAudit(`${r.name}: permissões reposto para preset`);
        renderMatrix(); renderRoles(); updateTabCounts();
    });

    // role modal
    $('#roleModalClose')?.addEventListener('click', closeRoleModal);
    $('#rmCancel')?.addEventListener('click', closeRoleModal);
    $('#rmSave')?.addEventListener('click', saveRoleModal);
    $$('[data-preset]').forEach(btn => btn.addEventListener('click', () => applyPreset(btn.dataset.preset)));
    $('#roleModal')?.addEventListener('click', e => { if (e.target.id === 'roleModal') closeRoleModal(); });

    // user modal
    $('#userModalClose')?.addEventListener('click', () => closeModal('userModal'));
    $('#umCancel')?.addEventListener('click', () => closeModal('userModal'));
    $('#umSave')?.addEventListener('click', saveUserModal);
    $('#userModal')?.addEventListener('click', e => { if (e.target.id === 'userModal') closeModal('userModal'); });

    // add user modal
    $('#addUserModalClose')?.addEventListener('click', () => closeModal('addUserModal'));
    $('#auCancel')?.addEventListener('click', () => closeModal('addUserModal'));
    $('#auCreate')?.addEventListener('click', createUser);
    $('#addUserModal')?.addEventListener('click', e => { if (e.target.id === 'addUserModal') closeModal('addUserModal'); });

    // ESC
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        ['roleModal','userModal','addUserModal'].forEach(id => {
            if (!document.getElementById(id)?.classList.contains('is-hidden')) closeModal(id);
        });
    });

    // initial render
    switchTab('users');
    renderUsers();
    renderRoles();
    renderMatrix();
    updateTabCounts();
    logAudit('RBAC carregado.');

    if (window.lucide) window.lucide.createIcons();
    setTimeout(() => { if (window.lucide) window.lucide.createIcons(); }, 50);
}

document.addEventListener('DOMContentLoaded', init);
