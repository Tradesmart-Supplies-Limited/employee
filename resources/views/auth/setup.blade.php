<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $step === 'done' ? 'You\'re all set' : 'Set up the system' }} | Tradesmart Supplies HR</title>
    <script>
        try { var t = localStorage.getItem('adminHMD.colorTheme'); if (t) { document.documentElement.setAttribute('data-theme', t); } } catch (e) {}
        document.documentElement.classList.add('js');
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    @include('partials.welcome-styles')
    <style>
        .su { max-width: 1040px; margin: 0 auto; padding: 8px 16px 56px; display: grid; grid-template-columns: 300px 1fr; gap: 40px; align-items: start; }
        .su-side { position: sticky; top: 20px; }

        /* the badge being printed */
        .su-badge { --c: #B42318; width: 220px; margin: 0 auto 26px; position: relative; padding-top: 30px; }
        .su-badge .wl-strap { top: -6px; height: 44px; }
        .su-badge .wl-clip { top: 40px; }
        .su-badge .wl-strap { height: 48px; }
        .su-badge .wl-card { min-height: 300px; margin-top: 20px; }
        .su-badge .wl-card-role { min-height: 1.6em; transition: color .2s; }
        .su-badge .wl-card-role.empty { color: var(--ink-soft); font-weight: 600; font-style: italic; font-size: 1.05rem; }
        .su-line { display: flex; align-items: center; gap: 7px; padding: 3px 12px; font-size: .8rem; color: var(--ink-soft); min-height: 1.6em; }
        .su-line i { width: 16px; text-align: center; }
        .su-line.ok { color: var(--green); font-weight: 600; }
        .su-stamp { position: absolute; right: 10px; bottom: 54px; transform: rotate(-12deg) scale(.6); opacity: 0; border: 3px solid var(--green); color: var(--green);
            font-family: var(--display); font-weight: 800; font-size: 1.05rem; padding: 2px 10px; border-radius: 8px; letter-spacing: .06em; transition: transform .45s cubic-bezier(.3, 1.8, .5, 1), opacity .3s; }
        .su-stamp.on { opacity: 1; transform: rotate(-12deg) scale(1); }

        .su-steps { list-style: none; margin: 0; padding: 0; counter-reset: s; }
        .su-steps li { counter-increment: s; display: flex; gap: 12px; align-items: center; padding: 9px 0; color: var(--ink-soft); }
        .su-steps li::before { content: counter(s); width: 30px; height: 30px; flex-shrink: 0; border-radius: 50%; border: 2px solid var(--line); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; background: var(--panel); }
        .su-steps li.now { color: var(--ink); font-weight: 700; }
        .su-steps li.now::before { border-color: var(--copper); color: var(--copper); }
        .su-steps li.done::before { content: "\2713"; background: var(--green); border-color: var(--green); color: #fff; }

        .su-card { background: var(--panel); border: 1px solid var(--line); border-radius: 18px; padding: 30px; box-shadow: var(--shadow); animation: wl-open .35s ease both; }
        .su-card h1 { font-family: var(--display); font-size: clamp(1.6rem, 3.4vw, 2.1rem); line-height: 1.15; letter-spacing: -.02em; margin: 0 0 8px; }
        .su-card .intro { color: var(--ink-soft); margin: 0 0 24px; max-width: 58ch; }
        .su-field { margin-bottom: 18px; }
        .su-field label { display: block; font-weight: 600; margin-bottom: 6px; }
        .su-input { width: 100%; font: inherit; font-size: 1.05rem; padding: .78rem .95rem; border-radius: 11px; border: 1.5px solid var(--line); background: var(--paper); color: var(--ink); }
        .su-input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 4px color-mix(in srgb, var(--blue) 18%, transparent); }
        .su-input.bad { border-color: var(--red); }
        .su-err { color: var(--red); font-size: .9rem; margin-top: 6px; display: flex; gap: 6px; }
        .su-help { color: var(--ink-soft); font-size: .88rem; margin-top: 6px; }
        .su-go { font: inherit; font-weight: 700; font-size: 1.02rem; border: 0; border-radius: 12px; padding: .9rem 1.4rem; background: var(--blue); color: #fff; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; }
        .wl .su-go { color: #fff; }
        .su-go:hover { filter: brightness(1.08); }
        .su-go[disabled] { opacity: .5; cursor: not-allowed; filter: none; }
        .su-row { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; margin-top: 8px; }
        .su-plain { font: inherit; background: none; border: 0; color: var(--blue); font-weight: 600; cursor: pointer; padding: .4rem 0; }
        .su-plain[disabled] { color: var(--ink-soft); cursor: default; }
        .su-flash { padding: 11px 14px; border-radius: 11px; margin-bottom: 18px; display: flex; gap: 9px; font-size: .95rem; }
        .su-flash.ok { background: color-mix(in srgb, var(--green) 13%, var(--panel)); color: var(--green); }
        .su-flash.no { background: color-mix(in srgb, var(--red) 12%, var(--panel)); color: var(--red); }

        .su-otp { display: flex; gap: 10px; margin: 6px 0 4px; }
        .su-otp input { width: 54px; height: 64px; text-align: center; font-family: var(--display); font-size: 1.9rem; font-weight: 800; border-radius: 12px; border: 1.5px solid var(--line); background: var(--paper); color: var(--ink); }
        .su-otp input:focus { outline: none; border-color: var(--copper); box-shadow: 0 0 0 4px color-mix(in srgb, var(--copper) 20%, transparent); }
        .su-otp input.filled { border-color: var(--blue); }
        .su-otp.shake { animation: su-shake .4s; }
        @keyframes su-shake { 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }
        .su-mailto { display: inline-flex; gap: 8px; align-items: center; background: var(--paper); border: 1px solid var(--line); border-radius: 999px; padding: .35rem .85rem; font-weight: 600; }

        .su-pw { position: relative; }
        .su-pw button { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: 0; background: none; color: var(--ink-soft); font-size: 1.15rem; cursor: pointer; padding: 6px; }
        .su-meter { height: 7px; border-radius: 4px; background: var(--line); overflow: hidden; margin: 10px 0 4px; }
        .su-meter span { display: block; height: 100%; width: 0; background: var(--red); transition: width .25s, background .25s; }
        .su-checks { list-style: none; padding: 0; margin: 8px 0 18px; display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; font-size: .9rem; color: var(--ink-soft); }
        .su-checks li { display: flex; gap: 7px; align-items: center; }
        .su-checks li::before { content: "\25CB"; width: 14px; }
        .su-checks li.ok { color: var(--green); }
        .su-checks li.ok::before { content: "\2713"; font-weight: 800; }

        .su-next { list-style: none; padding: 0; margin: 4px 0 26px; }
        .su-next li { border-top: 1px solid var(--line); }
        .su-next a { display: flex; gap: 14px; align-items: center; padding: 14px 2px; text-decoration: none; color: var(--ink); }
        .su-next a:hover b { color: var(--blue); }
        .su-next i { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--blue) 12%, var(--panel)); color: var(--blue); font-size: 1.1rem; flex-shrink: 0; }
        .su-next small { display: block; color: var(--ink-soft); }
        .su-confetti { position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 5; }
        .su-confetti i { position: absolute; top: -12px; width: 9px; height: 14px; border-radius: 2px; animation: su-fall linear forwards; }
        @keyframes su-fall { to { transform: translateY(110vh) rotate(720deg); } }

        @media (max-width: 860px) {
            .su { grid-template-columns: 1fr; gap: 18px; }
            .su-side { position: static; display: grid; grid-template-columns: 150px 1fr; gap: 16px; align-items: center; }
            .su-badge { width: 150px; margin: 0; padding-top: 22px; }
            .su-badge .wl-card { min-height: 220px; }
            .su-badge .wl-card-role { font-size: 1.05rem; }
            .su-line { font-size: .72rem; padding: 2px 10px; }
            .su-stamp { font-size: .8rem; bottom: 40px; }
            .su-card { padding: 22px 18px; }
            .su-otp { gap: 7px; }
            .su-otp input { width: 100%; max-width: 50px; height: 58px; font-size: 1.6rem; }
            .su-checks { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="wl">

@php
    $stepIndex = ['details' => 0, 'confirm' => 1, 'password' => 2, 'done' => 3][$step];
    $steps = ['Your details', 'Confirm your email', 'Choose a password', 'Badge issued'];
    $first = $name ? explode(' ', trim($name))[0] : null;
@endphp

<header class="wl-top">
    <a class="wl-brand" href="{{ route('home') }}">
        <img src="{{ config('company.logo_url') }}" width="34" height="34" alt="">
        <span>Tradesmart<span class="wl-hide-sm"> Supplies</span> <b>HR</b></span>
    </a>
    <div class="wl-top-actions">
        <button class="wl-icon" type="button" data-theme-toggle aria-label="Switch light or dark mode"><i class="bi bi-moon-stars" data-theme-icon></i></button>
    </div>
</header>

<div class="su">
    <aside class="su-side" aria-label="Setup progress">
        <div class="su-badge" aria-hidden="true">
            <span class="wl-strap"></span><span class="wl-clip"></span>
            <span class="wl-card">
                <span class="wl-card-band"><i class="bi bi-shield-lock"></i><span>TSL</span></span>
                <span class="wl-card-photo"><i class="bi bi-person-fill"></i></span>
                <span class="wl-card-role {{ $name ? '' : 'empty' }}" id="badgeName">{{ $name ?: 'Your name' }}</span>
                <span class="su-line" style="color:var(--c);font-weight:700"><i class="bi bi-shield-lock"></i>Admin</span>
                <span class="su-line {{ $stepIndex >= 2 ? 'ok' : '' }}"><i class="bi {{ $stepIndex >= 2 ? 'bi-patch-check-fill' : 'bi-envelope' }}"></i>{{ $stepIndex >= 2 ? 'Email confirmed' : 'Email not confirmed' }}</span>
                <span class="su-line {{ $stepIndex >= 3 ? 'ok' : '' }}"><i class="bi {{ $stepIndex >= 3 ? 'bi-lock-fill' : 'bi-unlock' }}"></i>{{ $stepIndex >= 3 ? 'Password set' : 'No password yet' }}</span>
                <span class="wl-card-qr" style="margin-top:auto">@for($i = 0; $i < 49; $i++)<i class="{{ $stepIndex >= 3 ? ((crc32('admin' . $i) % 3) ? 'on' : '') : '' }}"></i>@endfor</span>
                <span class="su-stamp {{ $step === 'done' ? 'on' : '' }}" id="stamp">ISSUED</span>
            </span>
        </div>
        <ol class="su-steps">
            @foreach($steps as $i => $label)
                <li class="{{ $i < $stepIndex || $step === 'done' ? 'done' : ($i === $stepIndex ? 'now' : '') }}" @if($i === $stepIndex) aria-current="step" @endif>{{ $label }}</li>
            @endforeach
        </ol>
    </aside>

    <main class="su-main">
        <section class="su-card">
            @if(session('status') && $step !== 'done')<div class="su-flash ok" role="status"><i class="bi bi-check-circle"></i><span>{{ session('status') }}</span></div>@endif
            @if(session('error'))<div class="su-flash no" role="alert"><i class="bi bi-exclamation-triangle"></i><span>{{ session('error') }}</span></div>@endif

            @if($step === 'details')
                <h1>Let's make your Admin badge.</h1>
                <p class="intro">You're the first person here, so this login will be the system's Admin. It takes about two minutes: tell us who you are, confirm your email with a code, then choose a password.</p>
                <form method="POST" action="{{ route('setup.details') }}" novalidate>
                    @csrf
                    <div class="su-field">
                        <label for="name">Your full name</label>
                        <input class="su-input @error('name') bad @enderror" id="name" name="name" value="{{ old('name', $name) }}" autocomplete="name" required autofocus>
                        @error('name')<div class="su-err"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>@enderror
                    </div>
                    <div class="su-field">
                        <label for="email">Work email</label>
                        <input class="su-input @error('email') bad @enderror" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" inputmode="email" required>
                        @error('email')<div class="su-err"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>@enderror
                        <div class="su-help">You'll sign in with this address. We'll send a 6-digit code to it.</div>
                    </div>
                    <button class="su-go" type="submit"><i class="bi bi-envelope-paper"></i>Email me a code</button>
                </form>

            @elseif($step === 'confirm')
                <h1>Check your inbox{{ $first ? ', ' . $first : '' }}.</h1>
                <p class="intro">We sent a 6-digit code to <span class="su-mailto"><i class="bi bi-envelope"></i>{{ $email }}</span>. Enter it below. It expires in {{ $minutes }} minutes. Can't see it? Check spam or junk.</p>
                @if($expired)<div class="su-flash no"><i class="bi bi-hourglass-bottom"></i><span>This code has expired. Send a new one.</span></div>@endif
                <form method="POST" action="{{ route('setup.verify') }}" id="otpForm" novalidate>
                    @csrf
                    <label class="su-field" style="display:block;margin-bottom:6px;font-weight:600" for="d0">Confirmation code</label>
                    <div class="su-otp @error('code') shake @enderror" id="otp">
                        @for($i = 0; $i < 6; $i++)
                            <input id="d{{ $i }}" type="text" inputmode="numeric" maxlength="1" aria-label="Digit {{ $i + 1 }}" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" @if($i === 0) autofocus @endif>
                        @endfor
                    </div>
                    <input type="hidden" name="code" id="code">
                    @error('code')<div class="su-err"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>@enderror
                    <noscript><input class="su-input" name="code" inputmode="numeric" maxlength="6" placeholder="6-digit code"></noscript>
                    <div class="su-row" style="margin-top:20px">
                        <button class="su-go" type="submit" id="otpGo"><i class="bi bi-patch-check"></i>Confirm email</button>
                    </div>
                </form>
                <div class="su-row" style="margin-top:18px">
                    <form method="POST" action="{{ route('setup.resend') }}">@csrf<button class="su-plain" type="submit" id="resend" data-wait="{{ $resendIn }}" @if($resendIn) disabled @endif>Send a new code</button></form>
                    <form method="POST" action="{{ route('setup.restart') }}">@csrf<button class="su-plain" type="submit">Use a different email</button></form>
                </div>

            @elseif($step === 'password')
                <h1>Last step: lock it with a password.</h1>
                <p class="intro"><b style="color:var(--green)"><i class="bi bi-patch-check-fill"></i> {{ $email }} is confirmed.</b> Choose a password with at least 8 characters, including a letter and a number.</p>
                <form method="POST" action="{{ route('setup.password') }}" id="pwForm" novalidate>
                    @csrf
                    <input type="email" name="username" value="{{ $email }}" autocomplete="username" hidden>
                    <div class="su-field">
                        <label for="password">Password</label>
                        <div class="su-pw">
                            <input class="su-input @error('password') bad @enderror" id="password" name="password" type="password" autocomplete="new-password" required autofocus>
                            <button type="button" data-show="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="su-meter" aria-hidden="true"><span id="meter"></span></div>
                        <div class="su-help" id="meterText" aria-live="polite">&nbsp;</div>
                    </div>
                    <div class="su-field">
                        <label for="password_confirmation">Type it again</label>
                        <div class="su-pw">
                            <input class="su-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                            <button type="button" data-show="password_confirmation" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    @error('password')<div class="su-err" style="margin:-6px 0 12px"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>@enderror
                    <ul class="su-checks" id="checks">
                        <li data-check="len">At least 8 characters</li>
                        <li data-check="letter">A letter</li>
                        <li data-check="number">A number</li>
                        <li data-check="match">Both passwords match</li>
                    </ul>
                    <button class="su-go" type="submit" id="pwGo"><i class="bi bi-shield-lock"></i>Create my Admin login</button>
                </form>

            @else {{-- done --}}
                <h1>Your Admin badge is ready, {{ $first }}.</h1>
                <p class="intro">You're signed in as <b>{{ $email }}</b>. Here's what most people do next:</p>
                <ul class="su-next">
                    <li><a href="{{ route('employees.index') }}"><i class="bi bi-people"></i><span><b>Add your employees</b><small>One by one, or import a spreadsheet</small></span></a></li>
                    <li><a href="{{ route('users.index') }}"><i class="bi bi-shield-lock"></i><span><b>Give HR and managers a login</b><small>Under Users &amp; Roles</small></span></a></li>
                    <li><a href="{{ route('attendance.settings') }}"><i class="bi bi-clock"></i><span><b>Set working hours and shifts</b><small>Attendance settings and the shift rota</small></span></a></li>
                </ul>
                <a class="su-go" href="{{ route('dashboard') }}" style="text-decoration:none"><i class="bi bi-speedometer2"></i>Go to the dashboard</a>
            @endif
        </section>
        @if($step !== 'done')
            <p class="su-help" style="margin-top:14px">Already set up? <a href="{{ route('login') }}">Sign in</a> instead.</p>
        @endif
    </main>
</div>

<script>
(function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Name goes onto the badge as you type.
    var nameIn = document.getElementById('name'), badgeName = document.getElementById('badgeName');
    if (nameIn && badgeName) {
        nameIn.addEventListener('input', function () {
            var v = nameIn.value.trim();
            badgeName.textContent = v || 'Your name';
            badgeName.classList.toggle('empty', !v);
        });
    }

    // Code boxes: type, paste, backspace; submits itself when all six are in.
    var otp = document.getElementById('otp');
    if (otp) {
        var boxes = [].slice.call(otp.querySelectorAll('input')), hidden = document.getElementById('code'), form = document.getElementById('otpForm');
        var sync = function () {
            hidden.value = boxes.map(function (b) { return b.value; }).join('');
            boxes.forEach(function (b) { b.classList.toggle('filled', !!b.value); });
            return hidden.value.length === 6;
        };
        var fill = function (from, digits) {
            digits.split('').forEach(function (d, k) { if (boxes[from + k]) boxes[from + k].value = d; });
            var next = boxes[Math.min(from + digits.length, 5)]; next.focus();
            if (sync()) form.requestSubmit ? form.requestSubmit() : form.submit();
        };
        boxes.forEach(function (b, i) {
            b.addEventListener('input', function () {
                var d = b.value.replace(/\D/g, '');
                b.value = '';
                if (d) fill(i, d.slice(0, 6 - i)); else sync();
            });
            b.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !b.value && i > 0) { boxes[i - 1].value = ''; boxes[i - 1].focus(); sync(); e.preventDefault(); }
                if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
                if (e.key === 'ArrowRight' && i < 5) boxes[i + 1].focus();
            });
            b.addEventListener('paste', function (e) {
                var d = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (d) { e.preventDefault(); fill(0, d.slice(0, 6)); }
            });
        });
        form.addEventListener('submit', function (e) {
            if (!sync()) { e.preventDefault(); otp.classList.remove('shake'); void otp.offsetWidth; otp.classList.add('shake'); boxes.find(function (b) { return !b.value; }).focus(); }
        });
    }

    // Resend cool-down.
    var resend = document.getElementById('resend');
    if (resend) {
        var wait = parseInt(resend.dataset.wait || '0', 10);
        var label = 'Send a new code';
        var t = function () {
            if (wait > 0) { resend.disabled = true; resend.textContent = label + ' (' + wait + 's)'; wait--; setTimeout(t, 1000); }
            else { resend.disabled = false; resend.textContent = label; }
        };
        t();
    }

    // Password strength + checklist.
    var pw = document.getElementById('password'), pw2 = document.getElementById('password_confirmation');
    if (pw) {
        var meter = document.getElementById('meter'), meterText = document.getElementById('meterText'), go = document.getElementById('pwGo');
        var mark = function (k, ok) { document.querySelector('[data-check="' + k + '"]').classList.toggle('ok', ok); };
        var check = function () {
            var v = pw.value, c = {
                len: v.length >= 8, letter: /[A-Za-z]/.test(v), number: /\d/.test(v), match: v.length > 0 && v === pw2.value
            };
            Object.keys(c).forEach(function (k) { mark(k, c[k]); });
            var score = (v.length >= 8) + (v.length >= 12) + (/[a-z]/.test(v) && /[A-Z]/.test(v)) + /\d/.test(v) + /[^A-Za-z0-9]/.test(v);
            var levels = [['0%', 'var(--red)', ' '], ['25%', 'var(--red)', 'Too easy to guess'], ['45%', 'var(--copper)', 'Getting there'], ['70%', 'var(--copper)', 'Good'], ['88%', 'var(--green)', 'Strong'], ['100%', 'var(--green)', 'Very strong']];
            var l = v ? levels[Math.max(1, score)] : levels[0];
            meter.style.width = l[0]; meter.style.background = l[1]; meterText.textContent = l[2];
            go.disabled = !(c.len && c.letter && c.number && c.match);
        };
        pw.addEventListener('input', check); pw2.addEventListener('input', check); check();
        [].forEach.call(document.querySelectorAll('[data-show]'), function (b) {
            b.addEventListener('click', function () {
                var f = document.getElementById(b.dataset.show), show = f.type === 'password';
                f.type = show ? 'text' : 'password';
                b.innerHTML = '<i class="bi ' + (show ? 'bi-eye-slash' : 'bi-eye') + '"></i>';
                b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    }

    // Badge issued: a little confetti.
    if (document.getElementById('stamp').classList.contains('on') && !reduce) {
        var box = document.createElement('div'); box.className = 'su-confetti'; document.body.appendChild(box);
        var colors = ['#E07A10', '#1F8A3B', '#185FA5', '#B42318', '#F2C94C'];
        for (var i = 0; i < 90; i++) {
            var p = document.createElement('i');
            p.style.left = Math.random() * 100 + 'vw';
            p.style.background = colors[i % colors.length];
            p.style.animationDuration = (1.8 + Math.random() * 1.6) + 's';
            p.style.animationDelay = (Math.random() * .5) + 's';
            box.appendChild(p);
        }
        setTimeout(function () { box.remove(); }, 4500);
    }

    // Light / dark (same saved choice as the rest of the app).
    var key = 'adminHMD.colorTheme', icon = document.querySelector('[data-theme-icon]');
    var current = function () { return document.documentElement.getAttribute('data-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'); };
    var setIcon = function () { icon.className = current() === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars'; };
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
