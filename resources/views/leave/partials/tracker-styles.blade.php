@once
<style>
/* ===== Leave stage tracker (shared: admin + portal) ===== */
.lt {
    --lt-done: #16a34a;
    --lt-current: #f59e0b;
    --lt-declined: #dc2626;
    --lt-muted: #94a3b8;
    --lt-track: var(--admin-border, #e2e8f0);
    --lt-text: var(--admin-text, #0f172a);
    --lt-sub: var(--admin-muted, #64748b);
    --lt-surface: var(--admin-surface, #fff);
    position: relative;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    padding: 6px 0 2px;
}
.lt-step { position: relative; text-align: center; padding: 0 6px; }
.lt-step::before {                       /* connector to previous step */
    content: "";
    position: absolute;
    top: 21px;
    right: 50%;
    width: 100%;
    height: 4px;
    background: var(--lt-track);
    border-radius: 4px;
    z-index: 0;
}
.lt-step:first-child::before { display: none; }
.lt-step.is-done::before,
.lt-step.is-declined::before,
.lt-step.is-current::before,
.lt-step.is-skipped::before { background: linear-gradient(90deg, var(--lt-done), var(--lt-done)); }
.lt-step.is-current::before { background: linear-gradient(90deg, var(--lt-done), var(--lt-current)); }
.lt-step.is-declined::before { background: linear-gradient(90deg, var(--lt-done), var(--lt-declined)); }
.lt-step.is-skipped::before { background: var(--lt-muted); }

.lt-dot {
    position: relative;
    z-index: 1;
    width: 46px; height: 46px;
    margin: 0 auto 10px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem;
    background: var(--lt-surface);
    border: 3px solid var(--lt-track);
    color: var(--lt-muted);
    transition: transform .2s;
}
.lt-step.is-done .lt-dot      { background: var(--lt-done); border-color: var(--lt-done); color: #fff; }
.lt-step.is-declined .lt-dot  { background: var(--lt-declined); border-color: var(--lt-declined); color: #fff; }
.lt-step.is-skipped .lt-dot   { background: var(--lt-track); border-color: var(--lt-muted); color: var(--lt-sub); border-style: dashed; }
.lt-step.is-cancelled .lt-dot { opacity: .55; }
.lt-step.is-current .lt-dot   {
    border-color: var(--lt-current); color: var(--lt-current);
    box-shadow: 0 0 0 0 rgba(245, 158, 11, .45);
    animation: lt-pulse 1.8s infinite;
}
@keyframes lt-pulse {
    0%   { box-shadow: 0 0 0 0 rgba(245, 158, 11, .45); }
    70%  { box-shadow: 0 0 0 12px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}
@media (prefers-reduced-motion: reduce) { .lt-step.is-current .lt-dot { animation: none; } }

.lt-title { font-weight: 700; font-size: .84rem; color: var(--lt-text); }
.lt-note  { font-size: .76rem; font-weight: 600; margin-top: 2px; color: var(--lt-sub); }
.lt-step.is-done .lt-note     { color: var(--lt-done); }
.lt-step.is-current .lt-note  { color: #b45309; }
.lt-step.is-declined .lt-note { color: var(--lt-declined); }
.lt-who  { font-size: .74rem; color: var(--lt-sub); margin-top: 2px; }
.lt-when { font-size: .7rem; color: var(--lt-muted); margin-top: 1px; }

@media (max-width: 640px) {
    .lt { grid-template-columns: 1fr; gap: 4px; }
    .lt-step { display: grid; grid-template-columns: 46px 1fr; column-gap: 12px; text-align: left; padding: 0 0 14px; }
    .lt-step::before { top: auto; bottom: 100%; right: auto; left: 21px; width: 4px; height: 18px; }
    .lt-dot { margin: 0; grid-row: span 4; }
}

/* ===== Compact tracker (tables / lists) ===== */
.lt-mini { display: inline-flex; flex-direction: column; gap: 4px; min-width: 150px; }
.lt-mini-bar { display: flex; gap: 3px; }
.lt-mini-seg { flex: 1; height: 6px; border-radius: 3px; background: var(--admin-border, #e2e8f0); }
.lt-mini-seg.is-done      { background: #16a34a; }
.lt-mini-seg.is-current   { background: #f59e0b; }
.lt-mini-seg.is-declined  { background: #dc2626; }
.lt-mini-seg.is-skipped   { background: #94a3b8; }
.lt-mini-label { font-size: .72rem; font-weight: 600; display: flex; align-items: center; gap: 4px; }
.lt-mini-label.text-warning { color: #b45309 !important; }
.lt-mini-label.text-info { color: #0369a1 !important; }
.lt-pending { color: #b45309; }

/* ===== Balance meter ===== */
.lt-balance-ring {
    --pct: 0;
    width: 92px; height: 92px; border-radius: 50%;
    background: conic-gradient(#16a34a calc(var(--pct) * 1%), var(--admin-border, #e2e8f0) 0);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.lt-balance-ring > div {
    width: 72px; height: 72px; border-radius: 50%;
    background: var(--admin-surface, #fff);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    line-height: 1.05;
}
.lt-balance-ring strong { font-size: 1.35rem; color: var(--admin-text, #0f172a); }
.lt-balance-ring small  { font-size: .66rem; color: var(--admin-muted, #64748b); }
</style>
@endonce
