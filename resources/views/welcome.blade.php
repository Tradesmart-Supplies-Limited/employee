<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tradesmart Supplies Limited HR system: leave, shifts, attendance and payslips.">
    <title>Welcome | Tradesmart Supplies HR</title>
    <script>
        // Apply the saved light/dark choice before paint (same key as the rest of the app).
        try { var t = localStorage.getItem('adminHMD.colorTheme'); if (t) { document.documentElement.setAttribute('data-theme', t); } } catch (e) {}
        document.documentElement.classList.add('js');
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    @include('partials.welcome-styles')
</head>
<body class="wl">

@php
    $order = ['employee', 'manager', 'hr', 'director', 'admin'];
    // A small, stable QR-ish pattern per badge (decoration only).
    $pattern = function (string $seed) {
        $h = md5($seed); $cells = [];
        for ($i = 0; $i < 49; $i++) { $cells[] = hexdec($h[$i % 32]) % 3 !== 0; }
        foreach ([0, 1, 7, 8, 5, 6, 12, 13, 35, 36, 42, 43] as $k) { $cells[$k] = true; }
        return $cells;
    };
    $signInAs = ['employee' => 'an employee', 'manager' => 'a manager', 'hr' => 'HR', 'director' => 'a director', 'admin' => 'an admin'];
@endphp

<header class="wl-top">
    <a class="wl-brand" href="{{ route('home') }}">
        <img src="{{ config('company.logo_url') }}" width="34" height="34" alt="">
        <span>Tradesmart<span class="wl-hide-sm"> Supplies</span> <b>HR</b></span>
    </a>
    <div class="wl-top-actions">
        <button class="wl-icon" type="button" data-theme-toggle aria-label="Switch light or dark mode"><i class="bi bi-moon-stars" data-theme-icon></i></button>
        <a class="wl-signin" href="{{ route('login') }}">Sign in</a>
    </div>
</header>

<main>
    @if($needsSetup)
        <a class="wl-setup-banner" href="{{ route('setup') }}">
            <i class="bi bi-stars"></i>
            <span><b>Nobody has signed in yet.</b> Set up the system and create the first Admin login.</span>
            <span class="go">Set up</span>
        </a>
    @endif

    <section class="wl-hero">
        <p class="wl-greet" aria-live="polite"><span id="greet">Muli shani</span><span class="wl-greet-mark">.</span></p>
        <p class="wl-greet-lang" id="greetLang">That's "how are you?" in Bemba.</p>
        <h1>Welcome to the Tradesmart HR system.</h1>
        <p class="wl-lead">Check your leave, shifts and payslips, look after your team, or run HR for the whole company. Pick the badge that matches you and you'll go to the right sign-in.</p>
    </section>

    <section class="wl-rail-wrap" aria-labelledby="pickTitle">
        <h2 id="pickTitle" class="wl-h2">Which badge is yours?</h2>
        <div class="wl-rail" role="radiogroup" aria-label="Kind of login">
            @foreach($order as $i => $r)
                @php $o = $roles[$r]; @endphp
                <button type="button" class="wl-badge" role="radio" aria-checked="false" data-role="{{ $r }}" style="--c:{{ $o['color'] }}; --i:{{ $i }}">
                    <span class="wl-strap" aria-hidden="true"></span>
                    <span class="wl-clip" aria-hidden="true"></span>
                    <span class="wl-card">
                        <span class="wl-card-band"><i class="bi {{ $o['icon'] }}"></i><span>TSL</span></span>
                        <span class="wl-card-photo" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                        <span class="wl-card-role">{{ $o['label'] }}</span>
                        <span class="wl-card-who">{{ $guide[$r]['who'] }}</span>
                        <span class="wl-card-qr" aria-hidden="true">@foreach($pattern($r) as $on)<i class="{{ $on ? 'on' : '' }}"></i>@endforeach</span>
                    </span>
                </button>
            @endforeach
        </div>
        <p class="wl-rail-hint" id="railHint">Tap a badge to see what it opens.</p>
    </section>

    <section class="wl-detail" id="detail" aria-live="polite">
        @foreach($order as $r)
            @php $o = $roles[$r]; $g = $guide[$r]; @endphp
            <article class="wl-panel" data-panel="{{ $r }}" style="--c:{{ $o['color'] }}">
                <div class="wl-panel-head">
                    <span class="wl-panel-icon"><i class="bi {{ $o['icon'] }}"></i></span>
                    <div>
                        <h3>The {{ $o['label'] }} badge</h3>
                        <p>{{ $g['who'] }}</p>
                    </div>
                </div>
                <div class="wl-panel-body">
                    <div>
                        <h4>What it opens</h4>
                        <ul>@foreach($g['can'] as $c)<li><i class="bi bi-check2"></i>{{ $c }}</li>@endforeach</ul>
                    </div>
                    <div class="wl-panel-side">
                        <h4>Where you'll land</h4>
                        <p class="wl-land"><i class="bi bi-geo-alt"></i>{{ $g['lands'] }}</p>
                        <p class="wl-tip"><i class="bi bi-lightbulb"></i>{{ $g['tip'] }}</p>
                        <a class="wl-cta" href="{{ route('login', ['as' => $r]) }}">Sign in as {{ $signInAs[$r] }}</a>
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="wl-quiz" id="quiz" aria-labelledby="quizTitle">
        <div class="wl-quiz-intro">
            <h2 id="quizTitle" class="wl-h2">Not sure which one?</h2>
            <p>Answer up to four quick questions.</p>
        </div>
        <div class="wl-quiz-box">
            <div class="wl-quiz-dots" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <p class="wl-quiz-q" id="quizQ">Are you setting up the system, or deciding who gets a login?</p>
            <div class="wl-quiz-actions" id="quizActions">
                <button type="button" class="wl-btn" data-answer="yes">Yes</button>
                <button type="button" class="wl-btn" data-answer="no">No</button>
            </div>
            <div class="wl-quiz-result" id="quizResult" hidden>
                <p>Your badge is <b id="quizRole"></b>.</p>
                <div class="wl-quiz-actions">
                    <button type="button" class="wl-btn wl-btn-solid" id="quizShow">Show me that badge</button>
                    <button type="button" class="wl-btn" id="quizAgain">Start again</button>
                </div>
            </div>
        </div>
    </section>

    <section class="wl-help">
        @if($needsSetup)
            <div class="wl-help-item wl-help-setup">
                <h3><i class="bi bi-rocket-takeoff"></i>First time here?</h3>
                <p>Create the first Admin login in four short steps. We'll email you a code to confirm the address.</p>
                <a class="wl-link" href="{{ route('setup') }}">Set up the system</a>
            </div>
        @endif
        <div class="wl-help-item">
            <h3><i class="bi bi-person-plus"></i>No login yet?</h3>
            <p>HR creates logins for staff. @if($hrContact)Email <a href="mailto:{{ $hrContact }}">{{ $hrContact }}</a> and they'll send you yours.@else Ask HR and they'll send you yours.@endif</p>
        </div>
        <div class="wl-help-item">
            <h3><i class="bi bi-key"></i>Forgot your password?</h3>
            <p>Get a reset link or a 6-digit code by email.</p>
            <a class="wl-link" href="{{ route('password.request') }}">Reset my password</a>
        </div>
        <div class="wl-help-item">
            <h3><i class="bi bi-qr-code-scan"></i>Clocking in?</h3>
            <p>Scan your QR badge on the tablet at the entrance. No sign-in needed.</p>
        </div>
    </section>
</main>

<footer class="wl-foot">
    <span>&copy; {{ date('Y') }} Tradesmart Supplies Limited</span>
    <span>Lusaka time <b id="clock">{{ now('Africa/Lusaka')->format('H:i') }}</b></span>
</footer>

<script>
(function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ---- rotating greeting -------------------------------------------------
    var greetings = [
        ['Muli shani', 'That\'s "how are you?" in Bemba.'],
        ['Muli bwanji', 'That\'s "how are you?" in Nyanja.'],
        ['Mwabuka buti', 'That\'s "good morning" in Tonga.'],
        ['Mu zuhile cwañi', 'That\'s "good morning" in Lozi.'],
        ['Hello', 'Plain English works too.']
    ];
    var gi = 0, greet = document.getElementById('greet'), lang = document.getElementById('greetLang');
    if (!reduce) {
        setInterval(function () {
            gi = (gi + 1) % greetings.length;
            greet.classList.add('out');
            setTimeout(function () {
                greet.textContent = greetings[gi][0];
                lang.textContent = greetings[gi][1];
                greet.classList.remove('out');
            }, 260);
        }, 3200);
    }

    // ---- badges ------------------------------------------------------------
    var badges = [].slice.call(document.querySelectorAll('.wl-badge'));
    var panels = [].slice.call(document.querySelectorAll('.wl-panel'));
    var rail = document.querySelector('.wl-rail');
    var hint = document.getElementById('railHint');

    function pick(role, scroll) {
        badges.forEach(function (b) {
            var on = b.dataset.role === role;
            b.setAttribute('aria-checked', on ? 'true' : 'false');
            b.tabIndex = on ? 0 : -1;
            b.classList.toggle('is-picked', on);
            if (on && !reduce) { b.classList.remove('swing'); void b.offsetWidth; b.classList.add('swing'); }
        });
        rail.classList.add('has-pick');
        panels.forEach(function (p) { p.classList.toggle('is-open', p.dataset.panel === role); });
        hint.textContent = 'Tap another badge to compare.';
        try { localStorage.setItem('tsl.welcome.role', role); } catch (e) {}
        if (scroll) {
            var el = document.querySelector('.wl-badge[data-role="' + role + '"]');
            el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center', inline: 'center' });
        }
    }

    badges.forEach(function (b, i) {
        b.tabIndex = i === 0 ? 0 : -1;
        b.addEventListener('click', function () { pick(b.dataset.role); });
        b.addEventListener('keydown', function (e) {
            var k = e.key, n = null;
            if (k === 'ArrowRight' || k === 'ArrowDown') n = badges[(i + 1) % badges.length];
            if (k === 'ArrowLeft' || k === 'ArrowUp') n = badges[(i - 1 + badges.length) % badges.length];
            if (n) { e.preventDefault(); n.focus(); pick(n.dataset.role); }
        });
    });

    try { var saved = localStorage.getItem('tsl.welcome.role'); if (saved && document.querySelector('[data-panel="' + saved + '"]')) pick(saved); } catch (e) {}

    // ---- "not sure?" questions --------------------------------------------
    var qs = [
        ['Are you setting up the system, or deciding who gets a login?', 'admin'],
        ['Are you a director of the company?', 'director'],
        ['Do you work in the HR team?', 'hr'],
        ['Do people report to you, so you approve their leave or plan their shifts?', 'manager']
    ];
    var labels = { admin: 'Admin', director: 'Director', hr: 'HR', manager: 'Manager', employee: 'Employee' };
    var qi = 0, result = null;
    var qEl = document.getElementById('quizQ'), acts = document.getElementById('quizActions'),
        res = document.getElementById('quizResult'), roleEl = document.getElementById('quizRole'),
        dots = [].slice.call(document.querySelectorAll('.wl-quiz-dots i'));

    function showQ() {
        qEl.textContent = qs[qi][0];
        dots.forEach(function (d, j) { d.className = j < qi ? 'done' : (j === qi ? 'now' : ''); });
        qEl.classList.remove('in'); void qEl.offsetWidth; qEl.classList.add('in');
    }
    function finish(role) {
        result = role;
        dots.forEach(function (d) { d.className = 'done'; });
        qEl.textContent = '';
        acts.hidden = true;
        res.hidden = false;
        roleEl.textContent = labels[role];
        document.getElementById('quizShow').focus();
    }
    acts.addEventListener('click', function (e) {
        var b = e.target.closest('[data-answer]'); if (!b) return;
        if (b.dataset.answer === 'yes') return finish(qs[qi][1]);
        qi++;
        if (qi >= qs.length) return finish('employee');
        showQ();
    });
    document.getElementById('quizShow').addEventListener('click', function () { pick(result, true); });
    document.getElementById('quizAgain').addEventListener('click', function () {
        qi = 0; result = null; acts.hidden = false; res.hidden = true; showQ(); acts.querySelector('button').focus();
    });

    // ---- Lusaka clock ------------------------------------------------------
    var clock = document.getElementById('clock');
    function tick() {
        try { clock.textContent = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', timeZone: 'Africa/Lusaka' }); } catch (e) {}
    }
    setInterval(tick, 15000);

    // ---- light / dark ------------------------------------------------------
    var key = 'adminHMD.colorTheme', icon = document.querySelector('[data-theme-icon]');
    function current() {
        var t = document.documentElement.getAttribute('data-theme');
        if (t) return t;
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    function setIcon() { icon.className = current() === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars'; }
    document.querySelector('[data-theme-toggle]').addEventListener('click', function () {
        var next = current() === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        try { localStorage.setItem(key, next); } catch (e) {}
        setIcon();
    });
    setIcon();
})();
</script>
</body>
</html>
