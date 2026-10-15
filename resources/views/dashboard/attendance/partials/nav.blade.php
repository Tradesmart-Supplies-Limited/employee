{{-- Attendance module header + tabs. Params: $title, $subtitle (optional), slot 'actions' via $actions (html) --}}
@php
    $tabs = [
        'attendance.index'    => ['Overview', 'bi-speedometer2', 'attendance.view'],
        'attendance.register' => ['Daily register', 'bi-pencil-square', 'attendance.view'],
        'attendance.rota.index' => ['Shift rota', 'bi-calendar-week', 'rota.manage'],
        'attendance.summary'  => ['Monthly summary', 'bi-calendar3', 'attendance.view'],
        'attendance.import'   => ['Import timesheets', 'bi-file-earmark-arrow-up', 'full'],
        'attendance.qr'       => ['QR & kiosks', 'bi-qr-code', 'full'],
        'attendance.settings' => ['Settings', 'bi-sliders', 'full'],
    ];
    $u = auth()->user();
    $tabs = array_filter($tabs, fn ($t) => $u && ($t[2] === 'full' ? $u->hasFullAccess() : $u->hasPermission($t[2])));
    $current = request()->route()?->getName();
@endphp
<style>
.at-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; margin-bottom: .9rem; }
.at-kicker { color: #185FA5; font-weight: 700; font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; }
.at-tabs { display: flex; gap: .3rem; overflow-x: auto; border-bottom: 1px solid var(--admin-border, #e2e8f0); margin-bottom: 1.1rem; scrollbar-width: none; }
.at-tabs a { white-space: nowrap; padding: .55rem .85rem; font-size: .86rem; font-weight: 600; color: var(--admin-muted, #64748b); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; }
.at-tabs a:hover { color: #185FA5; }
.at-tabs a.active { color: #185FA5; border-bottom-color: #185FA5; }
.at-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; }
.at-card-h { display: flex; justify-content: space-between; align-items: center; gap: .6rem; padding: .85rem 1.1rem; border-bottom: 1px solid var(--admin-border, #e2e8f0); flex-wrap: wrap; }
.at-card-h h6 { margin: 0; font-weight: 700; font-size: .95rem; }
.at-chip { display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; font-weight: 700; padding: .18rem .6rem; border-radius: 999px; white-space: nowrap; background: var(--bg); color: var(--c); }
.at-av { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #185FA5, #4f8cff); color: #fff; font-weight: 700; font-size: .78rem; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
.at-av img { width: 100%; height: 100%; object-fit: cover; }
.at-table { font-size: .86rem; margin: 0; }
.at-table th { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: var(--admin-muted, #64748b); white-space: nowrap; font-weight: 700; }
.at-muted { color: var(--admin-muted, #64748b); }
.at-tile { padding: .85rem 1rem; border-left: 4px solid var(--c); }
.at-tile .v { font-size: 1.7rem; font-weight: 800; line-height: 1.1; color: var(--c); font-variant-numeric: tabular-nums; }
.at-tile .l { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--admin-muted, #64748b); }
.at-soon { font-size: .62rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; background: #fef3c7; color: #92400e; padding: .15rem .5rem; border-radius: 999px; }
</style>

<div class="at-head">
    <div>
        <div class="at-kicker">Attendance</div>
        <h1 class="h4 fw-bold mb-0">{{ $title }}</h1>
        @isset($subtitle)<div class="small at-muted">{!! $subtitle !!}</div>@endisset
    </div>
    @isset($actions)<div class="d-flex gap-2 flex-wrap align-items-center">{!! $actions !!}</div>@endisset
</div>

<nav class="at-tabs">
    @foreach($tabs as $route => [$label, $icon])
        <a href="{{ route($route) }}" class="{{ $current === $route || ($route === 'attendance.import' && str_starts_with((string) $current, 'attendance.import')) || ($route === 'attendance.qr' && $current === 'attendance.badges') || ($route === 'attendance.rota.index' && str_starts_with((string) $current, 'attendance.rota.')) ? 'active' : '' }}"><i class="bi {{ $icon }}"></i> {{ $label }}</a>
    @endforeach
</nav>
