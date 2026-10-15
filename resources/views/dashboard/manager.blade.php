@extends('layouts.app')
@section('title', 'My department')

@section('content')
<style>
.mg-hero { --g1: #0b3b4f; --g2: #0f766e; --g3: #155e75; --glow1: rgba(45,212,191,.35); --glow2: rgba(56,189,248,.18); --accent: #5EEAD4;
    position: relative; overflow: hidden; border-radius: 22px; padding: 1.4rem 1.5rem; color: #fff;
    background: radial-gradient(620px 320px at 100% 0%, var(--glow1), transparent 62%), radial-gradient(520px 300px at 0% 100%, var(--glow2), transparent 60%), linear-gradient(125deg, var(--g1) 0%, var(--g2) 55%, var(--g3) 100%);
    box-shadow: 0 1px 0 rgba(255,255,255,.12) inset, 0 20px 44px -18px color-mix(in srgb, var(--g1) 75%, transparent); }
.mg-hero .date { font-size: .8rem; letter-spacing: .06em; text-transform: uppercase; opacity: .85; font-weight: 700; }
.mg-hero h1 { font-size: clamp(1.35rem, 3vw, 1.9rem); font-weight: 800; margin: .25rem 0 .2rem; color: #fff; }
.mg-hero .sum { opacity: .95; }
.mg-hero .sum b { font-weight: 800; }
.mg-dept { display: inline-flex; align-items: center; gap: .3rem; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.25); border-radius: 999px; padding: .15rem .65rem; font-size: .78rem; font-weight: 700; margin: .15rem .2rem 0 0; }
.mg-quote { margin-top: .9rem; font-size: .85rem; color: rgba(255,255,255,.9); border-left: 3px solid var(--accent); padding-left: .7rem; }
.mg-row { display: flex; align-items: center; gap: .7rem; padding: .6rem 1rem; border-bottom: 1px solid var(--admin-border, #eef2f7); }
.mg-row:last-child { border-bottom: 0; }
.mg-av { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--brand, #0f766e), color-mix(in srgb, var(--brand, #0f766e) 55%, #ffffff)); color: #fff; font-weight: 700; font-size: .74rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mg-pill { font-size: .7rem; font-weight: 700; padding: .15rem .55rem; border-radius: 999px; background: var(--bg); color: var(--c); white-space: nowrap; }
.mg-links a { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem; border-bottom: 1px solid var(--admin-border, #eef2f7); text-decoration: none; color: inherit; font-weight: 600; font-size: .9rem; }
.mg-links a:last-child { border-bottom: 0; }
.mg-links a:hover { background: color-mix(in srgb, #0f766e 6%, transparent); }
.mg-links i.lead { width: 30px; height: 30px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--c) 14%, transparent); color: var(--c); }
</style>

@php
    $first = explode(' ', trim($user->employee?->first_name ?: $user->name))[0];
    $t = $totals;
@endphp

<section class="mg-hero mb-3">
    <div class="date">{{ $today->format('l, d F Y') }} · Manager</div>
    <h1>{{ $greeting }}, {{ $first }}</h1>
    <div>
        @forelse($depts as $d)<span class="mg-dept"><i class="bi bi-diagram-3"></i> {{ $d }}</span>@empty<span class="mg-dept"><i class="bi bi-exclamation-triangle"></i> No department set</span>@endforelse
    </div>
    <div class="sum mt-2">
        @if($t['expected'])<b>{{ $t['attended'] }}</b> of {{ $t['expected'] }} in today{{ $t['late'] ? ' (' . $t['late'] . ' late)' : '' }} · @endif
        <b>{{ $away->count() }}</b> on leave ·
        <b>{{ $toReview->count() }}</b> leave request{{ $toReview->count() === 1 ? '' : 's' }} for you to review
    </div>
    <div class="mg-quote">“{{ $quote['meaning'] }}” <span style="opacity:.75">· {{ $quote['lang'] }}</span></div>
</section>

@if(! $depts)
    <div class="alert alert-warning"><i class="bi bi-info-circle"></i> Your login isn't linked to an employee record with a department, and nobody reports to you yet, so there is nothing to show. Ask HR to link your login under <strong>Users &amp; Roles</strong>.</div>
@endif

<div class="row g-2 g-md-3 mb-3">
    @foreach([
        ['Team', $team, 'var(--brand-text, #0f766e)', 'bi-people', 'active staff', route('attendance.summary')],
        ['In today', $t['expected'] ? $t['attended'] . '/' . $t['expected'] : '—', '#16a34a', 'bi-qr-code-scan', $t['rate'] !== null ? $t['rate'] . '% attendance' : 'not a working day', route('attendance.index')],
        ['Late / absent', $t['late'] + $t['absent'], ($t['late'] + $t['absent']) ? '#d97706' : '#64748b', 'bi-alarm', $t['late'] . ' late · ' . $t['absent'] . ' absent', route('attendance.register')],
        ['To review', $toReview->count(), $toReview->count() ? '#dc2626' : '#64748b', 'bi-inbox', $withHr . ' waiting for HR', route('leave.index', ['stage' => 'awaiting_supervisor'])],
    ] as [$l, $v, $c, $ic, $s, $url])
        <div class="col-6 col-xl-3">
            <a href="{{ $url }}" class="hr-card hr-stat" style="--c:{{ $c }}">
                <div class="d-flex justify-content-between align-items-start gap-1"><div class="l">{{ $l }}</div><i class="bi {{ $ic }}" style="color:{{ $c }};opacity:.6"></i></div>
                <div class="v text-truncate">{{ $v }}</div>
                <div class="s text-truncate" title="{{ $s }}">{{ $s }}</div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-inbox"></i> Leave waiting for your recommendation</h6><a href="{{ route('leave.index') }}" class="small">All requests <i class="bi bi-arrow-right"></i></a></div>
            @forelse($toReview as $l)
                <a href="{{ route('leave.show', $l) }}" class="mg-row text-decoration-none" style="color:inherit">
                    <span class="mg-av">{{ $l->employee?->initials ?? strtoupper(substr($l->employee_name, 0, 2)) }}</span>
                    <div class="flex-grow-1" style="min-width:0">
                        <div class="fw-semibold text-truncate">{{ $l->employee?->full_name ?? $l->employee_name }}</div>
                        <div class="small hr-muted">{{ $l->leave_type }} · {{ $l->date_from->format('d M') }} – {{ $l->date_to->format('d M') }} · {{ rtrim(rtrim(number_format((float) $l->total_days, 1), '0'), '.') }} day(s)</div>
                    </div>
                    <span class="btn btn-sm btn-primary">Review</span>
                </a>
            @empty
                <div class="hr-empty"><i class="bi bi-check2-circle text-success"></i>Nothing waiting for you.</div>
            @endforelse
        </div>
    </div>
    <div class="col-xl-5">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-grid"></i> Quick links</h6></div>
            <div class="mg-links">
                <a href="{{ route('attendance.register') }}" style="--c:var(--brand-text, #185FA5)"><i class="bi bi-pencil-square lead"></i> Daily register <span class="ms-auto small hr-muted">correct times</span></a>
                @if($user->hasPermission('rota.manage'))<a href="{{ route('attendance.rota.index') }}" style="--c:#7c3aed"><i class="bi bi-calendar-week lead"></i> Shift rota <span class="ms-auto small hr-muted">plan &amp; publish</span></a>@endif
                <a href="{{ route('leave.manage.planner') }}" style="--c:#0f766e"><i class="bi bi-calendar3-range lead"></i> Leave planner</a>
                <a href="{{ route('attendance.summary') }}" style="--c:#d97706"><i class="bi bi-calendar3 lead"></i> Monthly attendance</a>
                @if($user->canUsePortal())<a href="{{ route('portal.dashboard') }}" style="--c:#475569"><i class="bi bi-person-badge lead"></i> My self-service portal <span class="ms-auto small hr-muted">my leave, payslips…</span></a>@endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-qr-code-scan"></i> Needs a look today</h6><a href="{{ route('attendance.index') }}" class="small">Attendance <i class="bi bi-arrow-right"></i></a></div>
            @forelse($attention->take(10) as $r)
                @php $m = \App\Services\AttendanceService::meta($r->status); @endphp
                <div class="mg-row">
                    <span class="mg-av">{{ $r->employee->initials }}</span>
                    <div class="flex-grow-1" style="min-width:0">
                        <div class="fw-semibold text-truncate">{{ $r->employee->full_name }}</div>
                        <div class="small hr-muted text-truncate">{{ $r->record?->in_time ? 'In at ' . $r->record->in_time . ($r->record->minutes_late ? ' · ' . $r->record->minutes_late . ' min late' : '') : ($r->note ?: '') }}</div>
                    </div>
                    <span class="mg-pill" style="--bg:{{ $m['bg'] }};--c:{{ $m['color'] }}">{{ $m['label'] }}</span>
                </div>
            @empty
                <div class="hr-empty"><i class="bi bi-check2-circle text-success"></i>{{ $t['expected'] ? 'Everyone expected is in.' : 'Nobody is expected today.' }}</div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-airplane"></i> Away &amp; coming up</h6><a href="{{ route('leave.manage.planner') }}" class="small">Planner <i class="bi bi-arrow-right"></i></a></div>
            @forelse($away as $l)
                <div class="mg-row"><span class="mg-av" style="background:#7c3aed">{{ $l->employee?->initials }}</span><div class="flex-grow-1"><div class="fw-semibold">{{ $l->employee?->full_name ?? $l->employee_name }}</div><div class="small hr-muted">{{ $l->leave_type }} · back {{ optional($l->return_date)->format('D d M') ?? $l->date_to->copy()->addDay()->format('D d M') }}</div></div><span class="mg-pill" style="--bg:#ede9fe;--c:#7c3aed">Away</span></div>
            @empty
            @endforelse
            @foreach($upcoming as $l)
                <div class="mg-row"><span class="mg-av" style="background:#94a3b8">{{ $l->employee?->initials }}</span><div class="flex-grow-1"><div class="fw-semibold">{{ $l->employee?->full_name ?? $l->employee_name }}</div><div class="small hr-muted">{{ $l->leave_type }} · {{ $l->date_from->format('D d M') }} – {{ $l->date_to->format('d M') }}</div></div><span class="mg-pill" style="--bg:{{ $l->status === 'Approved' ? '#dcfce7' : '#fef3c7' }};--c:{{ $l->status === 'Approved' ? '#166534' : '#92400e' }}">{{ $l->status }}</span></div>
            @endforeach
            @if($away->isEmpty() && $upcoming->isEmpty())<div class="hr-empty"><i class="bi bi-calendar-check"></i>Nobody away in the next two weeks.</div>@endif
        </div>
    </div>
</div>

@if($rotas->isNotEmpty())
    <div class="hr-card mb-3">
        <div class="hr-card-h"><h6><i class="bi bi-calendar-week"></i> Shift rota</h6><a href="{{ route('attendance.rota.index') }}" class="small">All weeks <i class="bi bi-arrow-right"></i></a></div>
        @foreach($rotas as $w)
            <div class="mg-row flex-wrap">
                <div class="flex-grow-1"><div class="fw-semibold">{{ $w->department }} · {{ $loop->index % 2 === 0 ? 'This week' : 'Next week' }}</div><div class="small hr-muted">{{ $w->week->format('d M') }} – {{ $w->week->copy()->addDays(6)->format('d M') }}</div></div>
                @if(! $w->rota)<span class="mg-pill" style="--bg:#fee2e2;--c:#b91c1c">Not started</span>
                @elseif($w->pending)<span class="mg-pill" style="--bg:#fef3c7;--c:#92400e">Changes not published</span>
                @elseif($w->rota->is_published)<span class="mg-pill" style="--bg:#dcfce7;--c:#166534">Published</span>
                @else<span class="mg-pill" style="--bg:#f1f5f9;--c:#475569">Draft</span>@endif
                @if($w->rota)<a href="{{ route('attendance.rota.edit', $w->rota) }}" class="btn btn-sm btn-outline-primary">Open</a>
                @else
                    <form method="POST" action="{{ route('attendance.rota.store') }}" class="m-0">@csrf<input type="hidden" name="department" value="{{ $w->department }}"><input type="hidden" name="week" value="{{ $w->week->toDateString() }}"><input type="hidden" name="copy" value="1"><button class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Create</button></form>
                @endif
            </div>
        @endforeach
    </div>
@endif
@endsection
