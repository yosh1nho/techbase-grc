// store.js — source of truth central do Techbase GRC
// Todas as páginas importam daqui em vez de usar localStorage diretamente.
// Uso: import { store } from './store.js';

const KEYS = {
    risks:      'tb_risks',
    treatments: 'tb_treatments',
    assets:     'tb_assets',
    alerts:     'tb_alerts',
    maturity:   'tb_maturity',
};

function read(key) {
    try { return JSON.parse(localStorage.getItem(key) || 'null'); }
    catch { return null; }
}

function write(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); }
    catch (e) { console.error('[store] write error', key, e); }
}

// Subscribers: { key -> [fn, fn, ...] }
const _subs = {};

function subscribe(key, fn) {
    if (!_subs[key]) _subs[key] = [];
    _subs[key].push(fn);
    // chama imediatamente com valor atual
    fn(read(key));
    return () => { _subs[key] = _subs[key].filter(f => f !== fn); };
}

function notify(key) {
    (_subs[key] || []).forEach(fn => fn(read(key)));
}

export const store = {
    // ── Riscos ──────────────────────────────────────
    getRisks:       ()       => read(KEYS.risks) || [],
    setRisks:       (data)   => { write(KEYS.risks, data); notify(KEYS.risks); },
    onRisks:        (fn)     => subscribe(KEYS.risks, fn),

    // ── Tratamentos ─────────────────────────────────
    getTreatments:  ()       => read(KEYS.treatments) || [],
    setTreatments:  (data)   => { write(KEYS.treatments, data); notify(KEYS.treatments); },
    onTreatments:   (fn)     => subscribe(KEYS.treatments, fn),

    // ── Ativos ──────────────────────────────────────
    getAssets:      ()       => read(KEYS.assets) || [],
    setAssets:      (data)   => { write(KEYS.assets, data); notify(KEYS.assets); },
    onAssets:       (fn)     => subscribe(KEYS.assets, fn),

    // ── Alertas (Acronis/Wazuh) ──────────────────────
    getAlerts:      ()       => read(KEYS.alerts) || [],
    setAlerts:      (data)   => { write(KEYS.alerts, data); notify(KEYS.alerts); },
    onAlerts:       (fn)     => subscribe(KEYS.alerts, fn),

    // ── Maturidade ──────────────────────────────────
    getMaturity:    ()       => read(KEYS.maturity) || [],
    setMaturity:    (data)   => { write(KEYS.maturity, data); notify(KEYS.maturity); },
    onMaturity:     (fn)     => subscribe(KEYS.maturity, fn),
};
