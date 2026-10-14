<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#0b1324">
<meta name="mobile-web-app-capable" content="yes">
<title>Attendance · {{ $kiosk->name }}</title>
<link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
<style>
:root { --bg: #0b1324; --panel: rgba(255,255,255,.06); --line: rgba(255,255,255,.1); --muted: #94a3b8; --brand: #3b82f6; }
* { box-sizing: border-box; }
html, body { margin: 0; height: 100%; background: radial-gradient(1200px 700px at 80% -10%, #1e3a8a 0%, transparent 60%), var(--bg); color: #f8fafc; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; overflow: hidden; -webkit-user-select: none; user-select: none; }
.k-wrap { display: grid; grid-template-columns: minmax(300px, 38%) 1fr; height: 100vh; gap: 22px; padding: 22px; }
.k-side { display: flex; flex-direction: column; gap: 16px; min-height: 0; }
.k-brand { display: flex; align-items: center; gap: 12px; }
.k-brand img { height: 46px; background: #fff; border-radius: 10px; padding: 4px; }
.k-brand .n { font-weight: 800; font-size: 1.05rem; line-height: 1.2; }
.k-brand .l { color: var(--muted); font-size: .82rem; }
.k-dot { width: 9px; height: 9px; border-radius: 50%; background: #22c55e; display: inline-block; margin-right: 5px; box-shadow: 0 0 0 4px rgba(34,197,94,.2); }
.k-dot.off { background: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,.2); }
.k-clock { font-size: clamp(3.2rem, 8vw, 6.2rem); font-weight: 800; letter-spacing: -.03em; line-height: 1; font-variant-numeric: tabular-nums; }
.k-clock small { font-size: .38em; color: var(--muted); font-weight: 600; margin-left: .15em; }
.k-date { color: #cbd5e1; font-size: 1.15rem; margin-top: 6px; }
.k-counts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.k-count { background: var(--panel); border: 1px solid var(--line); border-radius: 14px; padding: 10px 12px; }
.k-count .v { font-size: 1.7rem; font-weight: 800; font-variant-numeric: tabular-nums; }
.k-count .t { font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; font-weight: 700; }
.k-recent { background: var(--panel); border: 1px solid var(--line); border-radius: 16px; padding: 12px 14px; flex: 1; min-height: 0; overflow: hidden; }
.k-recent h6 { margin: 0 0 8px; font-size: .74rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; }
.k-row { display: flex; align-items: center; gap: 10px; padding: 7px 0; border-top: 1px solid var(--line); font-size: .95rem; animation: fadeIn .4s; }
.k-row:first-of-type { border-top: 0; }
.k-row .tag { font-size: .68rem; font-weight: 800; padding: 2px 8px; border-radius: 999px; }
.tag.in { background: rgba(34,197,94,.18); color: #4ade80; } .tag.out { background: rgba(59,130,246,.2); color: #93c5fd; } .tag.late { background: rgba(245,158,11,.2); color: #fbbf24; }
.k-row .tm { margin-left: auto; color: var(--muted); font-variant-numeric: tabular-nums; }
.k-help { color: var(--muted); font-size: .8rem; }
.k-main { position: relative; display: flex; flex-direction: column; min-height: 0; }
.k-cam { position: relative; flex: 1; border-radius: 26px; overflow: hidden; background: #020617; border: 1px solid var(--line); min-height: 0; }
#reader { width: 100%; height: 100%; }
#reader video { width: 100% !important; height: 100% !important; object-fit: cover; transform: scaleX(-1); }
#reader.rear video { transform: none; }
#reader img[alt="Info icon"], #reader__dashboard, #qr-shaded-region > div { display: none !important; }
#qr-shaded-region { border-color: rgba(2,6,23,.35) !important; }
.k-frame { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; }
.k-frame .box { width: min(58%, 58vh); aspect-ratio: 1; position: relative; }
.k-frame .box i { position: absolute; width: 22%; height: 22%; border: 6px solid #fff; border-radius: 6px; opacity: .9; }
.k-frame .box i:nth-child(1) { top: 0; left: 0; border-right: 0; border-bottom: 0; border-top-left-radius: 22px; }
.k-frame .box i:nth-child(2) { top: 0; right: 0; border-left: 0; border-bottom: 0; border-top-right-radius: 22px; }
.k-frame .box i:nth-child(3) { bottom: 0; left: 0; border-right: 0; border-top: 0; border-bottom-left-radius: 22px; }
.k-frame .box i:nth-child(4) { bottom: 0; right: 0; border-left: 0; border-top: 0; border-bottom-right-radius: 22px; }
.k-frame .laser { position: absolute; left: 6%; right: 6%; height: 3px; background: linear-gradient(90deg, transparent, #60a5fa, transparent); box-shadow: 0 0 18px #60a5fa; animation: laser 2.2s ease-in-out infinite; }
.k-hint { position: absolute; left: 0; right: 0; bottom: 0; padding: 18px 20px 20px; text-align: center; background: linear-gradient(transparent, rgba(2,6,23,.85)); font-size: clamp(1.1rem, 2.4vw, 1.6rem); font-weight: 700; }
.k-hint small { display: block; font-size: .62em; color: var(--muted); font-weight: 500; margin-top: 2px; }
.k-tools { position: absolute; top: 14px; right: 14px; display: flex; gap: 8px; z-index: 5; }
.k-btn { background: rgba(15,23,42,.7); color: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 9px 12px; font-size: 1rem; cursor: pointer; backdrop-filter: blur(6px); }
.k-nocam { position: absolute; inset: 0; display: none; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 30px; gap: 10px; }
.k-nocam i.big { font-size: 4rem; color: #60a5fa; }
.k-nocam .warn { max-width: 560px; background: rgba(245,158,11,.12); border: 1px solid rgba(245,158,11,.35); color: #fde68a; border-radius: 14px; padding: 12px 16px; font-size: .9rem; text-align: left; }
.k-start { position: fixed; left: 50%; top: 16px; transform: translateX(-50%); background: rgba(59,130,246,.95); color: #fff; padding: 10px 18px; border-radius: 999px; font-weight: 700; font-size: .9rem; z-index: 20; box-shadow: 0 10px 30px rgba(0,0,0,.4); cursor: pointer; }
/* result overlay */
.k-result { position: fixed; inset: 0; z-index: 30; display: none; align-items: center; justify-content: center; background: rgba(2,6,23,.6); backdrop-filter: blur(8px); }
.k-result.show { display: flex; animation: fadeIn .15s; }
.k-card { width: min(640px, 92vw); border-radius: 32px; padding: 34px 30px 30px; text-align: center; background: #0f172a; border: 2px solid var(--c, #22c55e); box-shadow: 0 30px 80px rgba(0,0,0,.5), 0 0 0 8px color-mix(in srgb, var(--c, #22c55e) 18%, transparent); animation: pop .25s cubic-bezier(.2,1.4,.4,1); }
.k-card .av { width: 128px; height: 128px; margin: -90px auto 14px; border-radius: 50%; border: 6px solid var(--c); background: #1e293b center/cover; display: flex; align-items: center; justify-content: center; font-size: 2.6rem; font-weight: 800; }
.k-card .ic { font-size: 2.8rem; color: var(--c); line-height: 1; }
.k-card h2 { margin: 6px 0 6px; font-size: clamp(1.6rem, 4vw, 2.4rem); }
.k-card p { margin: 0; color: #cbd5e1; font-size: clamp(1rem, 2.4vw, 1.3rem); }
.k-card .big { font-size: clamp(2.6rem, 8vw, 4.4rem); font-weight: 800; color: var(--c); font-variant-numeric: tabular-nums; margin-top: 8px; }
.k-card .bar { height: 5px; background: var(--c); border-radius: 3px; margin-top: 18px; animation: shrink 3.6s linear forwards; }
.k-disabled { position: fixed; inset: 0; z-index: 40; display: none; align-items: center; justify-content: center; background: var(--bg); text-align: center; padding: 30px; }
@keyframes laser { 0%, 100% { top: 8%; } 50% { top: 90%; } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes pop { from { transform: scale(.85); opacity: 0; } to { transform: scale(1); opacity: 1; } }
@keyframes shrink { from { width: 100%; } to { width: 0; } }
@media (orientation: portrait), (max-width: 820px) {
  .k-wrap { display: flex; flex-direction: column; gap: 14px; padding: 16px; }
  .k-side { display: contents; }
  .k-top { order: 1; display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
  .k-top > div:last-child { margin-top: 0 !important; text-align: right; }
  .k-main { order: 2; flex: 1; min-height: 0; }
  .k-recent { order: 3; flex: 0 0 auto; max-height: 21vh; }
  .k-foot { order: 4; }
  .k-counts { display: none; }
  .k-clock { font-size: 3.6rem; }
  .k-date { font-size: 1rem; }
}
</style>
</head>
<body>

<div class="k-wrap">
    <div class="k-side">
        <div class="k-top">
            <div class="k-brand">
                @if(config('company.logo_url'))<img src="{{ config('company.logo_url') }}" alt="" onerror="this.remove()">@endif
                <div>
                    <div class="n">{{ config('company.name') }}</div>
                    <div class="l"><span class="k-dot" id="netDot"></span><span id="netTxt">{{ $kiosk->name }}{{ $kiosk->location ? ' · ' . $kiosk->location : '' }}</span></div>
                </div>
            </div>
            <div style="margin-top:22px">
                <div class="k-clock" id="clock">{{ $now->format('H:i') }}<small>{{ $now->format(':s') }}</small></div>
                <div class="k-date" id="date">{{ $now->format('l, d F Y') }}</div>
            </div>
        </div>

        <div class="k-counts">
            <div class="k-count"><div class="t">Clocked in</div><div class="v" id="cIn">–</div></div>
            <div class="k-count"><div class="t">Late</div><div class="v" id="cLate" style="color:#fbbf24">–</div></div>
            <div class="k-count"><div class="t">Gone home</div><div class="v" id="cOut" style="color:#93c5fd">–</div></div>
        </div>

        <div class="k-recent">
            <h6><i class="bi bi-clock-history"></i> Latest at this entrance</h6>
            <div id="recent"><div class="k-help">No scans yet today.</div></div>
        </div>

        <div class="k-help k-foot"><i class="bi bi-info-circle"></i> Your QR code is on your staff badge and in the Employee Portal under <strong>My Attendance</strong>. Scan once when you arrive and again when you leave. {{ \App\Models\AttendanceShift::where('is_active', true)->orderBy('sort')->get()->map(fn ($sh) => $sh->name . ' ' . $sh->weekday_start . '–' . $sh->weekday_end)->implode(' · ') }}.</div>
    </div>

    <div class="k-main">
        <div class="k-cam">
            <div id="reader"></div>
            <div class="k-frame" id="frame"><div class="box"><i></i><i></i><i></i><i></i><div class="laser"></div></div></div>
            <div class="k-tools">
                <button class="k-btn" id="btnSwitch" title="Switch camera" style="display:none"><i class="bi bi-arrow-repeat"></i></button>
                <button class="k-btn" id="btnFull" title="Full screen"><i class="bi bi-arrows-fullscreen"></i></button>
            </div>
            <div class="k-nocam" id="noCam">
                <i class="bi bi-upc-scan big"></i>
                <div style="font-size:1.5rem;font-weight:800">Ready for the hand-held scanner</div>
                <div class="k-help" style="font-size:1rem">Point the scanner at your QR code.</div>
                <div class="warn" id="camWarn"></div>
            </div>
            <div class="k-hint" id="hint">Show your QR code to the camera<small>Hold it steady inside the frame, about 20 cm away</small></div>
        </div>
    </div>
</div>

<div class="k-start" id="startHint"><i class="bi bi-hand-index"></i> Tap anywhere to turn on sound &amp; full screen</div>

<div class="k-result" id="result">
    <div class="k-card" id="card">
        <div class="av" id="rAv"></div>
        <div class="ic" id="rIc"></div>
        <h2 id="rTitle"></h2>
        <p id="rMsg"></p>
        <div class="big" id="rTime"></div>
        <div class="bar" id="rBar"></div>
    </div>
</div>

<div class="k-disabled" id="disabled">
    <div><i class="bi bi-slash-circle" style="font-size:4rem;color:#ef4444"></i><h2>This kiosk has been switched off</h2><p class="k-help" style="font-size:1rem">HR disabled this link. Ask HR for a new kiosk link.</p></div>
</div>

<script src="{{ asset('vendor/attendance/html5-qrcode.min.js') }}"></script>
<script>
(function () {
    const CFG = {
        scan: @json(route('kiosk.scan', $token)),
        ping: @json(route('kiosk.ping', $token)),
        photo: @json((bool) $kiosk->capture_photo),
        now: @json($now->format('Y-m-d\TH:i:s')),
    };
    const $ = id => document.getElementById(id);
    const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    // ---------- clock (server's local time, whatever the tablet's own clock says) ----------
    let base = { server: Date.parse(CFG.now + 'Z'), perf: performance.now() };
    const pad = n => String(n).padStart(2, '0');
    function tick() {
        const d = new Date(base.server + (performance.now() - base.perf));
        $('clock').innerHTML = pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()) + '<small>:' + pad(d.getUTCSeconds()) + '</small>';
        $('date').textContent = DAYS[d.getUTCDay()] + ', ' + pad(d.getUTCDate()) + ' ' + MONTHS[d.getUTCMonth()] + ' ' + d.getUTCFullYear();
    }
    setInterval(tick, 1000); tick();

    // ---------- sound ----------
    let audio = null;
    function beep(kind) {
        if (!audio) return;
        const tones = { success: [880, 1320], warning: [880, 660], out: [1320, 880], info: [660], error: [220, 180] }[kind] || [660];
        tones.forEach((f, i) => {
            const o = audio.createOscillator(), g = audio.createGain();
            o.frequency.value = f; o.type = kind === 'error' ? 'square' : 'sine';
            g.gain.setValueAtTime(.0001, audio.currentTime + i * .14);
            g.gain.exponentialRampToValueAtTime(.3, audio.currentTime + i * .14 + .02);
            g.gain.exponentialRampToValueAtTime(.0001, audio.currentTime + i * .14 + .13);
            o.connect(g).connect(audio.destination); o.start(audio.currentTime + i * .14); o.stop(audio.currentTime + i * .14 + .14);
        });
    }

    let wake = null;
    async function keepAwake() { try { if ('wakeLock' in navigator) wake = await navigator.wakeLock.request('screen'); } catch (e) {} }
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') keepAwake(); });

    function goFull() { const el = document.documentElement; (el.requestFullscreen || el.webkitRequestFullscreen || (() => {})).call(el); }
    document.addEventListener('click', function first() {
        try { audio = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
        goFull(); keepAwake(); $('startHint').remove();
        document.removeEventListener('click', first);
    });
    $('btnFull').addEventListener('click', e => { e.stopPropagation(); goFull(); });

    // ---------- result card ----------
    const COLORS = { success: '#22c55e', warning: '#f59e0b', out: '#3b82f6', info: '#94a3b8', error: '#ef4444' };
    const ICONS = { success: 'bi-box-arrow-in-right', warning: 'bi-alarm', out: 'bi-box-arrow-right', info: 'bi-check2-all', error: 'bi-x-octagon' };
    let hideT = null;
    function show(r) {
        const kind = r.kind || 'error';
        $('card').style.setProperty('--c', COLORS[kind]);
        const av = $('rAv');
        if (r.name) {
            av.style.display = 'flex';
            av.style.backgroundImage = r.photo ? 'url("' + r.photo + '")' : '';
            av.textContent = r.photo ? '' : r.name.split(' ').map(p => p[0]).slice(0, 2).join('');
            $('rIc').innerHTML = '';
        } else {
            av.style.display = 'none';
            $('rIc').innerHTML = '<i class="bi ' + ICONS[kind] + '"></i>';
        }
        $('rTitle').textContent = r.title || '';
        $('rMsg').textContent = r.message || '';
        $('rTime').textContent = r.action === 'in' ? 'IN ' + r.time : (r.action === 'out' ? 'OUT ' + r.time : '');
        const bar = $('rBar'); bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = '';
        $('result').classList.add('show');
        beep(kind);
        clearTimeout(hideT);
        hideT = setTimeout(() => { $('result').classList.remove('show'); busy = false; }, kind === 'error' ? 4200 : 3600);
    }
    $('result').addEventListener('click', () => { $('result').classList.remove('show'); busy = false; });

    function renderRecent(list) {
        if (!list || !list.length) { $('recent').innerHTML = '<div class="k-help">No scans yet today.</div>'; return; }
        $('recent').innerHTML = list.map(s => '<div class="k-row"><span class="tag ' + (s.action === 'in' ? (s.late ? 'late' : 'in') : 'out') + '">' + (s.action === 'in' ? (s.late ? 'LATE' : 'IN') : 'OUT') + '</span><span>' + s.name.replace(/[<>&]/g, '') + '</span><span class="tm">' + s.time + '</span></div>').join('');
    }

    // ---------- scanning ----------
    let busy = false, lastCode = '', lastAt = 0, video = null;

    function snapshot() {
        if (!CFG.photo) return null;
        video = video || document.querySelector('#reader video');
        if (!video || !video.videoWidth) return null;
        const w = 320, h = Math.round(video.videoHeight * w / video.videoWidth);
        const c = document.createElement('canvas'); c.width = w; c.height = h;
        c.getContext('2d').drawImage(video, 0, 0, w, h);
        try { return c.toDataURL('image/jpeg', .6); } catch (e) { return null; }
    }

    async function submit(code) {
        code = (code || '').trim();
        const t = Date.now();
        if (!code || busy) return;
        if (code === lastCode && t - lastAt < 6000) return;
        busy = true; lastCode = code; lastAt = t;
        const photo = snapshot();
        try {
            const res = await fetch(CFG.scan, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ code, photo }) });
            const r = await res.json().catch(() => ({}));
            if (res.status === 403) { $('disabled').style.display = 'flex'; return; }
            if (!res.ok && !r.title) { r.kind = 'error'; r.title = 'Something went wrong'; r.message = 'Please scan again. If it keeps failing, tell HR.'; }
            show(r);
            if (r.recent) renderRecent(r.recent);
            ping();
        } catch (e) {
            show({ kind: 'error', title: 'No connection', message: 'The tablet cannot reach the HR system. Check the Wi-Fi, then scan again.' });
            netState(false);
        }
    }

    // USB / Bluetooth scanners type the code and press Enter.
    let buf = '', bufT = 0;
    document.addEventListener('keydown', e => {
        const t = Date.now();
        if (t - bufT > 80) buf = '';
        bufT = t;
        if (e.key === 'Enter') { if (buf.length > 3) submit(buf); buf = ''; return; }
        if (e.key.length === 1) buf += e.key;
    });

    function noCamera(msg) {
        $('noCam').style.display = 'flex'; $('frame').style.display = 'none'; $('hint').style.display = 'none';
        $('camWarn').innerHTML = msg;
    }

    let cams = [], camIdx = 0, qr = null;
    async function startCamera() {
        if (!window.isSecureContext) {
            noCamera('<strong><i class="bi bi-shield-exclamation"></i> Camera blocked by the browser.</strong> Tablets only allow the camera on <strong>https://</strong> addresses (or http://localhost). Open this kiosk link over HTTPS, or in Chrome on the tablet go to <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code>, add <code>' + location.origin + '</code> and restart Chrome. A USB/Bluetooth QR scanner works right now without this.');
            return;
        }
        if (typeof Html5Qrcode === 'undefined') { noCamera('The scanner library did not load. Reload the page.'); return; }
        qr = qr || new Html5Qrcode('reader', { formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE], verbose: false, experimentalFeatures: { useBarCodeDetectorIfSupported: true } });
        try {
            if (!cams.length) {
                cams = await Html5Qrcode.getCameras();
                const front = cams.findIndex(c => /front|user|face/i.test(c.label));
                camIdx = front >= 0 ? front : 0;
                if (cams.length > 1) $('btnSwitch').style.display = '';
            }
            const cam = cams.length ? { deviceId: { exact: cams[camIdx].id } } : { facingMode: 'user' };
            $('reader').classList.toggle('rear', cams.length > 0 && /back|rear|environment/i.test(cams[camIdx].label));
            await qr.start(cam, { fps: 12, qrbox: (w, h) => { const s = Math.floor(Math.min(w, h) * .62); return { width: s, height: s }; }, disableFlip: false },
                text => submit(text), () => {});
            video = null;
        } catch (e) {
            noCamera('<strong>Could not open the camera.</strong> ' + (String(e).includes('Permission') || String(e).includes('NotAllowed') ? 'Allow camera access for this site in the browser settings, then reload.' : 'No camera found or it is used by another app.') + ' A USB/Bluetooth QR scanner still works.');
        }
    }
    $('btnSwitch').addEventListener('click', async e => {
        e.stopPropagation();
        if (!qr || cams.length < 2) return;
        try { await qr.stop(); } catch (x) {}
        camIdx = (camIdx + 1) % cams.length; startCamera();
    });
    startCamera();

    // ---------- heartbeat ----------
    function netState(ok) {
        $('netDot').classList.toggle('off', !ok);
    }
    async function ping() {
        try {
            const res = await fetch(CFG.ping, { headers: { 'Accept': 'application/json' } });
            if (res.status === 403) { $('disabled').style.display = 'flex'; return; }
            const r = await res.json();
            base = { server: Date.parse(r.now + 'Z'), perf: performance.now() };
            $('cIn').textContent = r.counts.in; $('cLate').textContent = r.counts.late; $('cOut').textContent = r.counts.out;
            CFG.photo = !!r.photo;
            renderRecent(r.recent);
            netState(true);
        } catch (e) { netState(false); }
    }
    ping(); setInterval(ping, 60000);

    // Reload once a night so the page never runs stale for weeks.
    setTimeout(() => location.reload(), 1000 * 60 * 60 * 20);
})();
</script>
</body>
</html>
