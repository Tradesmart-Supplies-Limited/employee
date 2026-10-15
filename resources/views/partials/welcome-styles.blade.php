{{-- Styles for the welcome page and first-time setup (stand-alone pages, no admin chrome). --}}
<style>
:root {
    --paper: #F3F6FA;
    --panel: #FFFFFF;
    --ink: #12304F;
    --ink-soft: #4A6280;
    --line: #D7E0EB;
    --blue: #185FA5;
    --copper: #E07A10;
    --green: #1F8A3B;
    --red: #B42318;
    --shadow: 0 1px 0 rgba(18, 48, 79, .06), 0 14px 30px -18px rgba(18, 48, 79, .35);
    --display: "Bricolage Grotesque", "Segoe UI Variable Display", "Segoe UI", system-ui, sans-serif;
    --body: "Segoe UI", system-ui, -apple-system, Roboto, "Helvetica Neue", Arial, sans-serif;
    color-scheme: light;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --paper: #0D1826; --panel: #142338; --ink: #E6EEF7; --ink-soft: #9DB0C7; --line: #24384F;
        --blue: #5BA3EC; --copper: #F2973A; --green: #4CC475; --red: #F2867C;
        --shadow: 0 1px 0 rgba(0, 0, 0, .3), 0 18px 34px -20px rgba(0, 0, 0, .8);
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --paper: #0D1826; --panel: #142338; --ink: #E6EEF7; --ink-soft: #9DB0C7; --line: #24384F;
    --blue: #5BA3EC; --copper: #F2973A; --green: #4CC475; --red: #F2867C;
    --shadow: 0 1px 0 rgba(0, 0, 0, .3), 0 18px 34px -20px rgba(0, 0, 0, .8);
    color-scheme: dark;
}

* { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body.wl { margin: 0; background: var(--paper); color: var(--ink); font: 400 1rem/1.55 var(--body); }
.wl a { color: var(--blue); }
.wl :focus-visible { outline: 3px solid var(--copper); outline-offset: 3px; border-radius: 6px; }
.wl main { max-width: 1120px; margin: 0 auto; padding: 0 16px 56px; }

/* ---------- top bar ---------- */
.wl-top { max-width: 1120px; margin: 0 auto; padding: 18px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.wl-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--ink) !important; font-family: var(--display); font-size: 1.05rem; }
.wl-brand img { border-radius: 8px; background: #fff; padding: 2px; }
.wl-brand b { color: var(--blue); }
.wl-top-actions { display: flex; align-items: center; gap: 8px; }
.wl-icon { width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--line); background: var(--panel); color: var(--ink); cursor: pointer; font-size: 1rem; }
.wl-signin { white-space: nowrap; font-weight: 600; text-decoration: none; padding: .55rem 1.05rem; border-radius: 999px; background: var(--ink); color: var(--paper) !important; }

/* ---------- setup banner ---------- */
.wl-setup-banner { display: flex; align-items: center; gap: 12px; padding: 12px 16px; margin: 4px 0 8px; border-radius: 14px; text-decoration: none; color: var(--ink) !important;
    background: color-mix(in srgb, var(--copper) 14%, var(--panel)); border: 1px solid color-mix(in srgb, var(--copper) 40%, transparent); }
.wl-setup-banner i { color: var(--copper); font-size: 1.3rem; }
.wl-setup-banner .go { margin-left: auto; font-weight: 700; color: var(--copper); white-space: nowrap; }

/* ---------- hero: the greeting is the headline ---------- */
.wl-hero { padding: 40px 0 8px; max-width: 760px; }
.wl-greet { font-family: var(--display); font-weight: 800; font-size: clamp(3rem, 11vw, 6.6rem); line-height: .95; letter-spacing: -.035em; margin: 0; font-variation-settings: "opsz" 96; }
.wl-greet #greet { display: inline-block; transition: opacity .26s ease, transform .26s ease; }
.wl-greet #greet.out { opacity: 0; transform: translateY(-.18em); }
.wl-greet-mark { color: var(--copper); }
.wl-greet-lang { margin: 10px 0 26px; color: var(--ink-soft); font-size: .95rem; min-height: 1.5em; }
.wl-hero h1 { font-family: var(--display); font-weight: 650; font-size: clamp(1.35rem, 3vw, 1.75rem); line-height: 1.25; margin: 0 0 10px; letter-spacing: -.01em; }
.wl-lead { margin: 0; color: var(--ink-soft); font-size: 1.07rem; max-width: 62ch; }
.wl-h2 { font-family: var(--display); font-weight: 700; font-size: 1.35rem; margin: 0 0 6px; letter-spacing: -.01em; }

/* ---------- badge rail ---------- */
.wl-rail-wrap { margin-top: 40px; }
.wl-rail { position: relative; display: flex; gap: 18px; padding: 34px 6px 22px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: thin; }
.wl-rail::before { /* the rail the lanyards hang from */
    content: ""; position: absolute; left: 0; right: 0; top: 10px; height: 6px; border-radius: 3px;
    background: linear-gradient(var(--line), color-mix(in srgb, var(--line) 60%, var(--ink-soft))); min-width: 100%; }
.wl-badge { position: relative; flex: 1 0 176px; max-width: 230px; scroll-snap-align: center; border: 0; background: none; padding: 0; cursor: pointer; color: inherit; text-align: left; font: inherit;
    transform-origin: 50% -24px; transition: transform .25s ease, opacity .25s ease, filter .25s ease; }
.wl-strap { position: absolute; left: 50%; top: -24px; width: 14px; height: 40px; margin-left: -7px; border-radius: 3px;
    background: repeating-linear-gradient(180deg, var(--c), var(--c) 6px, color-mix(in srgb, var(--c) 70%, #fff) 6px, color-mix(in srgb, var(--c) 70%, #fff) 9px); }
.wl-clip { position: absolute; left: 50%; top: 12px; width: 30px; height: 14px; margin-left: -15px; border-radius: 4px 4px 7px 7px; background: var(--copper); box-shadow: inset 0 -3px 0 rgba(0,0,0,.18); z-index: 2; }
.wl-card { position: relative; display: flex; flex-direction: column; margin-top: 20px; min-height: 262px; background: var(--panel); border-radius: 14px; overflow: hidden;
    border: 1px solid var(--line); box-shadow: var(--shadow); }
.wl-card::before { /* the punched slot */ content: ""; position: absolute; top: 9px; left: 50%; width: 36px; height: 7px; margin-left: -18px; border-radius: 4px; background: var(--paper); z-index: 1; }
.wl-card-band { display: flex; justify-content: flex-end; gap: 8px; align-items: flex-end; padding: 26px 12px 9px; background: var(--c); color: #fff; font-weight: 800; font-size: .74rem; letter-spacing: .08em; }
.wl-card-band i { font-size: 1.25rem; }
.wl-card-photo { width: 58px; height: 64px; margin: -22px 0 0 12px; border-radius: 8px; background: color-mix(in srgb, var(--c) 18%, var(--panel)); border: 3px solid var(--panel);
    display: flex; align-items: flex-end; justify-content: center; overflow: hidden; color: color-mix(in srgb, var(--c) 65%, var(--panel)); font-size: 2.8rem; line-height: 1; position: relative; }
.wl-card-role { font-family: var(--display); font-weight: 800; font-size: 1.32rem; padding: 6px 12px 0; letter-spacing: -.01em; }
.wl-card-who { padding: 2px 12px 0; font-size: .82rem; color: var(--ink-soft); line-height: 1.35; flex: 1; }
.wl-card-qr { display: grid; grid-template-columns: repeat(7, 5px); gap: 1px; padding: 10px 12px 12px; align-self: flex-end; }
.wl-card-qr i { width: 5px; height: 5px; }
.wl-card-qr i.on { background: var(--ink); }
.wl-badge:hover { transform: translateY(4px); }
.wl-rail.has-pick .wl-badge:not(.is-picked) { opacity: .62; filter: saturate(.6); }
.wl-rail.has-pick .wl-badge:not(.is-picked):hover { opacity: .9; filter: none; }
.wl-badge.is-picked { transform: translateY(10px); }
.wl-badge.is-picked .wl-card { border-color: var(--c); box-shadow: 0 0 0 2px var(--c), var(--shadow); }
.wl-badge.swing { animation: wl-swing .9s cubic-bezier(.3, 1.6, .5, 1); }
@keyframes wl-swing { 0% { transform: translateY(10px) rotate(0); } 25% { transform: translateY(10px) rotate(6deg); } 55% { transform: translateY(10px) rotate(-3.5deg); } 80% { transform: translateY(10px) rotate(1.4deg); } 100% { transform: translateY(10px) rotate(0); } }
/* the one page-load moment: badges drop onto the rail */
.js .wl-badge { animation: wl-drop .7s cubic-bezier(.25, 1.5, .5, 1) backwards; animation-delay: calc(var(--i) * 90ms + 150ms); }
.js .wl-badge.swing { animation: wl-swing .9s cubic-bezier(.3, 1.6, .5, 1); }
@keyframes wl-drop { from { transform: translateY(-46px); opacity: 0; } to { transform: none; opacity: 1; } }
.wl-rail-hint { margin: 0; color: var(--ink-soft); font-size: .9rem; }

/* ---------- selected badge details ---------- */
.wl-detail { margin-top: 18px; }
.js .wl-panel { display: none; }
.js .wl-panel.is-open { display: block; animation: wl-open .35s ease both; }
@keyframes wl-open { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
.wl-panel { background: var(--panel); border: 1px solid var(--line); border-left: 6px solid var(--c); border-radius: 16px; padding: 22px; box-shadow: var(--shadow); margin-bottom: 14px; }
.wl-panel-head { display: flex; gap: 14px; align-items: center; }
.wl-panel-head h3 { font-family: var(--display); font-size: 1.45rem; margin: 0; letter-spacing: -.01em; }
.wl-panel-head p { margin: 0; color: var(--ink-soft); }
.wl-panel-icon { width: 48px; height: 48px; flex-shrink: 0; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff; background: var(--c); }
.wl-panel-body { display: grid; grid-template-columns: 1.3fr 1fr; gap: 28px; margin-top: 18px; }
.wl-panel h4 { font-size: .95rem; margin: 0 0 8px; color: var(--ink-soft); font-weight: 600; }
.wl-panel ul { list-style: none; margin: 0; padding: 0; }
.wl-panel li { display: flex; gap: 10px; padding: 7px 0; border-bottom: 1px dashed var(--line); }
.wl-panel li:last-child { border-bottom: 0; }
.wl-panel li i { color: var(--c); font-size: 1.1rem; line-height: 1.4; }
.wl-land { font-weight: 700; margin: 0 0 12px; display: flex; gap: 8px; }
.wl-tip { margin: 0 0 18px; font-size: .92rem; color: var(--ink-soft); display: flex; gap: 8px; }
.wl-land i, .wl-tip i { color: var(--copper); }
.wl-cta { display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 700; padding: .8rem 1.3rem; border-radius: 12px; background: var(--c); color: #fff !important; width: 100%; }
.wl-cta:hover { filter: brightness(1.08); }

/* ---------- "not sure?" ---------- */
.wl-quiz { margin-top: 40px; display: grid; grid-template-columns: 1fr 1.6fr; gap: 24px; align-items: start; }
.wl-quiz-intro p { margin: 0; color: var(--ink-soft); }
.wl-quiz-box { background: var(--panel); border: 1px solid var(--line); border-radius: 16px; padding: 20px 22px; box-shadow: var(--shadow); min-height: 156px; }
.wl-quiz-dots { display: flex; gap: 6px; margin-bottom: 12px; }
.wl-quiz-dots i { width: 28px; height: 5px; border-radius: 3px; background: var(--line); transition: background .25s; }
.wl-quiz-dots i.now { background: var(--copper); }
.wl-quiz-dots i.done { background: var(--blue); }
.wl-quiz-q { font-family: var(--display); font-size: 1.22rem; font-weight: 600; margin: 0 0 16px; line-height: 1.3; }
.wl-quiz-q.in { animation: wl-open .3s ease both; }
.wl-quiz-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.wl-btn { font: inherit; font-weight: 600; padding: .62rem 1.2rem; min-width: 84px; border-radius: 10px; border: 1.5px solid var(--line); background: var(--paper); color: var(--ink); cursor: pointer; }
.wl-btn:hover { border-color: var(--blue); }
.wl-btn-solid { background: var(--blue); border-color: var(--blue); color: #fff; }
.wl-quiz-result p { font-family: var(--display); font-size: 1.3rem; margin: 0 0 14px; }
.wl-quiz-result b { color: var(--blue); }

/* ---------- help ---------- */
.wl-help { margin-top: 44px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); border-top: 1px solid var(--line); }
.wl-help-item { padding: 20px 20px 4px 0; }
.wl-help-item h3 { font-size: 1rem; margin: 0 0 6px; display: flex; gap: 8px; align-items: center; }
.wl-help-item h3 i { color: var(--blue); }
.wl-help-item p { margin: 0 0 8px; color: var(--ink-soft); font-size: .93rem; }
.wl-help-setup h3 i { color: var(--copper); }
.wl-link { font-weight: 700; text-decoration: none; }
.wl-link:hover { text-decoration: underline; }
.wl-foot { max-width: 1120px; margin: 0 auto; padding: 18px 16px 28px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; color: var(--ink-soft); font-size: .85rem; border-top: 1px solid var(--line); }

@media (max-width: 760px) {
    .wl-hide-sm { display: none; }
    .wl-brand { white-space: nowrap; }
    .wl-hero { padding-top: 22px; }
    .wl-panel-body, .wl-quiz { grid-template-columns: 1fr; gap: 16px; }
    .wl-badge { flex: 0 0 158px; }
    .wl-quiz { margin-top: 30px; }
}
@media (prefers-reduced-motion: reduce) {
    .wl *, .wl *::before, .wl *::after { animation: none !important; transition: none !important; }
}
</style>
