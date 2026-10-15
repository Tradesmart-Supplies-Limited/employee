@php
    $u = auth()->user();
    $home = $u?->canUseAdmin() ? route('dashboard') : ($u?->canUsePortal() ? route('portal.dashboard') : route('login'));
    $msg = isset($exception) && $exception->getMessage() && $exception->getMessage() !== 'Forbidden' ? $exception->getMessage() : 'Your role doesn\'t give access to this page.';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>No access · {{ config('app.name') }}</title>
    <style>
        :root { --bg: #f4f7fb; --card: #fff; --text: #0f172a; --muted: #64748b; --brand: #185FA5; }
        @media (prefers-color-scheme: dark) { :root { --bg: #0b1220; --card: #111a2e; --text: #e2e8f0; --muted: #94a3b8; } }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; padding: 16px; }
        .card { background: var(--card); border-radius: 18px; padding: 2rem 1.6rem; max-width: 440px; width: 100%; text-align: center; box-shadow: 0 18px 40px rgba(15,23,42,.08); }
        .ic { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; background: #fee2e2; color: #b91c1c; font-size: 1.8rem; font-weight: 800; }
        h1 { font-size: 1.3rem; margin: 0 0 .4rem; }
        p { color: var(--muted); margin: 0 0 1.3rem; line-height: 1.5; }
        a { display: inline-block; background: var(--brand); color: #fff; text-decoration: none; padding: .6rem 1.2rem; border-radius: 10px; font-weight: 600; }
        small { display: block; margin-top: 1rem; color: var(--muted); }
    </style>
</head>
<body>
    <div class="card">
        <div class="ic">!</div>
        <h1>You don't have access to this</h1>
        <p>{{ $msg }}</p>
        <a href="{{ $home }}">Back to {{ $u?->canUseAdmin() ? 'your dashboard' : ($u?->canUsePortal() ? 'your portal' : 'sign in') }}</a>
        @if($u)<small>Signed in as {{ $u->name }} · {{ $u->roleLabel() }}. If you need access, ask HR.</small>@endif
    </div>
</body>
</html>
