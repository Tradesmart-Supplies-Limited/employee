@extends('layouts.app')
@section('title', 'HR Dashboard')

@section('content')
@php
    use App\Services\AttendanceService as Att;
    $a = $attendance;
    $first = trim(explode(' ', (string) ($user?->name ?? 'there'))[0]);
    $circ = 2 * M_PI * 46;
    $rate = $a['rate'] ?? null;
    $deptMax = max(1, $byDept->max() ?? 1);
    $palette = ['#185FA5', '#16a34a', '#d97706', '#7c3aed', '#0891b2', '#db2777', '#dc2626', '#65a30d'];
    $money = fn ($v) => 'K ' . number_format((float) $v, 0);
    $kmoney = fn ($v) => (float) $v >= 1e6 ? 'K ' . rtrim(rtrim(number_format($v / 1e6, 2), '0'), '.') . 'm' : ((float) $v >= 1e4 ? 'K ' . number_format($v / 1e3, 0) . 'k' : 'K ' . number_format((float) $v, 0));
    $actions = [
        ['Add employee', 'bi-person-plus', route('employees.create'), '#185FA5', null],
        ['Approve leave', 'bi-calendar-check', route('leave.index'), '#7c3aed', $counts['leave_hr']],
        ['Detail changes', 'bi-person-lines-fill', route('profile-changes.index'), '#0891b2', $counts['profile']],
        ['Attendance', 'bi-qr-code-scan', route('attendance.index'), '#16a34a', null],
        ['Post announcement', 'bi-megaphone', route('announcements.create'), '#db2777', null],
        ['Record leave', 'bi-journal-plus', route('leave.manage.record'), '#d97706', null],
        ['Bulk import', 'bi-cloud-arrow-up', route('employees.import'), '#0f766e', null],
        ['Leave planner', 'bi-calendar3-week', route('leave.manage.planner'), '#9333ea', null],
        ['QR badges', 'bi-person-badge', route('attendance.qr'), '#475569', null],
        ['Payroll', 'bi-cash-stack', route('payroll.runs.index'), '#65a30d', null],
        ['Contract reminders', 'bi-bell', route('settings.contract-reminders.index'), '#dc2626', null],
        ['Export staff list', 'bi-download', route('employees.export'), '#64748b', null],
    ];
    $sevColor = ['danger' => '#dc2626', 'warning' => '#d97706', 'primary' => '#185FA5', 'secondary' => '#64748b'];
@endphp

<style>
/* Greeting card: the gradient follows the time of day (same rule as the greeting).
   All stops are dark enough for white text (≥ 4.5:1). */
.db-hero { --g1: #0b3b6f; --g2: #185FA5; --g3: #0f766e; --glow1: rgba(242,151,58,.42); --glow2: rgba(45,212,191,.22); --accent: #F7B267;
    position: relative; overflow: hidden; isolation: isolate; border-radius: 22px; padding: 1.5rem 1.6rem; color: #fff;
    background:
        radial-gradient(620px 320px at 100% 0%, var(--glow1), transparent 62%),
        radial-gradient(520px 300px at 0% 100%, var(--glow2), transparent 60%),
        linear-gradient(125deg, var(--g1) 0%, var(--g2) 52%, var(--g3) 100%);
    box-shadow: 0 1px 0 rgba(255,255,255,.12) inset, 0 20px 44px -18px color-mix(in srgb, var(--g1) 75%, transparent);
    transition: background .8s ease; }
.db-hero.is-morning   { --g1: #0b3b6f; --g2: #185FA5; --g3: #0f766e; --glow1: rgba(242,151,58,.55); --glow2: rgba(45,212,191,.22); --accent: #F7B267; }
.db-hero.is-afternoon { --g1: #0c4a7d; --g2: #1666b0; --g3: #0e7490; --glow1: rgba(56,189,248,.38); --glow2: rgba(16,185,129,.24); --accent: #7DD3FC; }
.db-hero.is-evening   { --g1: #1e1b4b; --g2: #312e81; --g3: #0b3b6f; --glow1: rgba(167,139,250,.40); --glow2: rgba(242,151,58,.20); --accent: #C4B5FD; }
.db-hero::before { /* fine diagonal sheen for depth, never behind the text */
    content: ""; position: absolute; inset: 0; z-index: -1; pointer-events: none;
    background: repeating-linear-gradient(125deg, rgba(255,255,255,.035) 0 2px, transparent 2px 14px);
    mask-image: linear-gradient(90deg, transparent 35%, #000 100%); -webkit-mask-image: linear-gradient(90deg, transparent 35%, #000 100%); }
.db-hero::after { content: ""; position: absolute; right: -70px; bottom: -90px; width: 280px; height: 280px; border-radius: 50%; z-index: -1;
    background: radial-gradient(circle, rgba(255,255,255,.10), rgba(255,255,255,0) 70%); }
.db-hero .date { font-size: .8rem; letter-spacing: .06em; text-transform: uppercase; font-weight: 700; color: rgba(255,255,255,.82); }
.db-hero h1 { font-size: clamp(1.45rem, 3.2vw, 2.05rem); font-weight: 800; margin: .3rem 0 .15rem; color: #fff; letter-spacing: -.01em; text-shadow: 0 1px 2px rgba(0,0,0,.18); }
.db-hero .local { color: rgba(255,255,255,.82); font-style: italic; font-size: .93rem; }
.db-hero .sum { margin-top: .75rem; font-size: .96rem; color: rgba(255,255,255,.95); }
.db-hero .sum b { font-weight: 800; color: #fff; }
.db-clock { text-align: right; position: relative; z-index: 1; }
.db-clock .t { display: inline-block; font-size: clamp(2.2rem, 5vw, 3.3rem); font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; letter-spacing: -.02em;
    padding: .35rem .7rem; border-radius: 16px; background: rgba(255,255,255,.10); border: 1px solid rgba(255,255,255,.16); }
.db-clock .t small { font-size: .42em; color: var(--accent); font-weight: 700; }
.db-clock .z { font-size: .78rem; color: rgba(255,255,255,.8); margin-top: .45rem; }
.db-clock .z i { color: var(--accent); }
/* Proverb: a darker glass panel so long text stays easy to read on any gradient */
.db-quote { position: relative; z-index: 1; margin-top: 1.15rem; display: flex; gap: .9rem; align-items: flex-start;
    background: linear-gradient(135deg, rgba(4,16,36,.38), rgba(4,16,36,.22)); border: 1px solid rgba(255,255,255,.14); border-left: 4px solid var(--accent);
    border-radius: 16px; padding: .95rem 1.1rem; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
.db-quote .qi { font-size: 2.6rem; line-height: .8; color: var(--accent); font-family: Georgia, serif; }
.db-quote .qa { font-size: .72rem; letter-spacing: .04em; font-weight: 700; color: var(--accent); }
.db-quote .qt { font-size: 1.12rem; font-weight: 700; font-style: italic; color: #fff; margin-top: .1rem; }
.db-quote .qm { font-size: .88rem; color: rgba(255,255,255,.88); }
.db-quote .ql { font-size: .86rem; margin-top: .35rem; color: #fff; }
.db-quote .ql i { color: var(--accent); }
.db-quote button { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); color: #fff; border-radius: 10px; padding: .4rem .6rem; margin-left: auto; flex-shrink: 0; transition: background .15s, transform .15s; }
.db-quote button:hover { background: rgba(255,255,255,.26); }
.db-quote button:active { transform: rotate(-12deg); }
.db-quote button:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) { .db-hero { transition: none; } .db-quote button { transition: none; } }
.db-actions { display: grid; grid-template-columns: repeat(6, 1fr); gap: .6rem; }
.db-act { position: relative; display: flex; flex-direction: column; align-items: center; gap: .4rem; text-align: center; padding: .85rem .4rem .75rem; border-radius: 14px; border: 1px solid var(--admin-border, #e2e8f0); background: var(--admin-surface-soft, #f8fafc); color: inherit; text-decoration: none; font-size: .78rem; font-weight: 600; line-height: 1.2; transition: all .15s; }
.db-act:hover { border-color: var(--c); background: var(--admin-surface, #fff); transform: translateY(-2px); box-shadow: 0 8px 18px rgba(15,23,42,.08); }
.db-act i { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: var(--c); background: color-mix(in srgb, var(--c) 12%, transparent); }
.db-act .bdg { position: absolute; top: 6px; right: 8px; background: #dc2626; color: #fff; font-size: .66rem; font-weight: 800; border-radius: 999px; padding: .05rem .4rem; }
.db-att { display: flex; gap: 1rem; align-items: center; }
.db-att svg { flex-shrink: 0; }
.db-mini { display: grid; grid-template-columns: repeat(2, 1fr); gap: .45rem; flex: 1; }
.db-mini div { background: var(--admin-surface-soft, #f8fafc); border-radius: 10px; padding: .4rem .6rem; font-size: .76rem; }
.db-mini b { display: block; font-size: 1.05rem; }
.db-stage { display: grid; grid-template-columns: repeat(4, 1fr); gap: .45rem; margin-bottom: .8rem; }
.db-stage a { text-align: center; text-decoration: none; color: inherit; border-radius: 10px; padding: .5rem .3rem; background: var(--admin-surface-soft, #f8fafc); border-top: 3px solid var(--c); }
.db-stage b { display: block; font-size: 1.25rem; color: var(--c); }
.db-stage small { font-size: .64rem; font-weight: 700; text-transform: uppercase; color: var(--admin-muted, #64748b); letter-spacing: .03em; }
.db-att-item { display: flex; gap: .75rem; align-items: center; padding: .7rem 1.1rem; border-bottom: 1px solid var(--admin-border, #eef2f7); }
.db-att-item:last-child { border-bottom: 0; }
.db-att-item .ic { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--c); background: color-mix(in srgb, var(--c) 12%, transparent); flex-shrink: 0; }
.db-att-item .n { font-weight: 800; color: var(--c); }
.db-up { display: flex; gap: .75rem; align-items: center; padding: .55rem 1.1rem; text-decoration: none; color: inherit; border-bottom: 1px solid var(--admin-border, #eef2f7); }
.db-up:last-child { border-bottom: 0; }
.db-up:hover { background: var(--admin-surface-soft, #f8fafc); }
.db-up .d { width: 44px; text-align: center; line-height: 1.05; flex-shrink: 0; }
.db-up .d b { display: block; font-size: 1.05rem; }
.db-up .d small { font-size: .62rem; text-transform: uppercase; font-weight: 700; color: var(--admin-muted, #64748b); }
.db-up .ic { color: var(--c); font-size: 1.05rem; }
.db-feed { padding: .4rem 1.1rem .6rem; }
.db-feed a { display: flex; gap: .7rem; padding: .45rem 0; color: inherit; text-decoration: none; font-size: .84rem; }
.db-feed .dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; background: var(--c); flex-shrink: 0; font-size: .8rem; }
.db-dept { display: flex; align-items: center; gap: .55rem; padding: .28rem 0; font-size: .8rem; text-decoration: none; color: inherit; }
.db-dept .n { width: 36%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.db-dept .b { flex: 1; height: 8px; border-radius: 4px; background: var(--admin-surface-soft, #f1f5f9); overflow: hidden; }
.db-dept .b span { display: block; height: 100%; border-radius: 4px; }
.db-find { position: relative; }
.db-find i { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); color: var(--admin-muted, #94a3b8); }
.db-find input { padding-left: 2.2rem; border-radius: 12px; }
@media (max-width: 1399.98px) { .db-actions { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 767.98px) {
    .db-hero { padding: 1.1rem; border-radius: 18px; }
    .db-clock { text-align: left; margin-top: .4rem; }
    .db-actions { grid-template-columns: repeat(3, 1fr); gap: .45rem; }
    .db-act { font-size: .72rem; padding: .7rem .3rem .6rem; }
    .db-act i { width: 36px; height: 36px; font-size: 1.05rem; }
    .db-quote { padding: .75rem .85rem; }
    .db-quote .qi { display: none; }
    .db-find { max-width: none !important; width: 100%; }
}
</style>

{{-- ============ HERO ============ --}}
@php $dayPart = $now->hour >= 5 && $now->hour < 12 ? 'morning' : ($now->hour >= 12 && $now->hour < 17 ? 'afternoon' : 'evening'); @endphp
<section class="db-hero is-{{ $dayPart }} mb-3" id="dbHero">
    <div class="row g-2 align-items-start position-relative" style="z-index:1">
        <div class="col-md-8">
            <div class="date" id="dbDate">{{ $now->format('l, d F Y') }} · Week {{ $now->isoWeek }}</div>
            <h1>{{ $greeting }}, {{ $first }} 👋</h1>
            <div class="local">Muli shani? · Muli bwanji? Welcome back.</div>
            <div class="sum">
                @if($a && $a['expected'])
                    <b>{{ $a['attended'] }}</b> of {{ $a['expected'] }} staff are in{{ $a['late'] ? ' (' . $a['late'] . ' late)' : '' }} ·
                @elseif($a && $a['holiday'])
                    Public holiday: <b>{{ $a['holiday'] }}</b> ·
                @endif
                <b>{{ $stats['on_leave'] }}</b> on leave ·
                <b>{{ $stats['approvals'] }}</b> approval{{ $stats['approvals'] === 1 ? '' : 's' }} waiting{{ $attention->isNotEmpty() ? ' · ' . $attention->count() . ' item(s) need attention' : ' · all clear ✓' }}
            </div>
        </div>
        <div class="col-md-4 db-clock">
            <div class="t" id="dbClock">{{ $now->format('H:i') }}<small>:{{ $now->format('s') }}</small></div>
            <div class="z"><i class="bi bi-geo-alt"></i> Lusaka time (CAT)</div>
        </div>
    </div>
    <div class="db-quote" id="dbQuote" data-i="{{ $quote['index'] }}">
        <div class="qi">“</div>
        <div class="flex-grow-1" style="min-width:0">
            <div class="qa" id="qA">{{ $quote['lang'] }} · Zambian wisdom for HR</div>
            <div class="qt" id="qT">{{ $quote['text'] }}</div>
            <div class="qm" id="qM">“{{ $quote['meaning'] }}”</div>
            <div class="ql" id="qL"><i class="bi bi-lightbulb"></i> {{ $quote['lesson'] }}</div>
        </div>
        <button type="button" id="qNext" title="Another proverb" aria-label="Show another proverb"><i class="bi bi-shuffle"></i></button>
    </div>
</section>

{{-- ============ KPIs ============ --}}
<div class="row g-2 g-md-3 mb-3">
    @foreach([
        ['Active staff', $stats['active'], '#185FA5', 'bi-people', ($stats['new_month'] ? '+' . $stats['new_month'] . ' this month · ' : '') . $stats['departments'] . ' departments', route('employees.index')],
        ['In today', $a && $a['expected'] ? $a['attended'] . '/' . $a['expected'] : '—', '#16a34a', 'bi-qr-code-scan', $rate !== null ? $rate . '% attendance' : (($a['holiday'] ?? null) ? 'public holiday' : 'not a working day'), route('attendance.index')],
        ['On leave today', $stats['on_leave'], '#7c3aed', 'bi-airplane', $leavePipeline['approved_month'] . ' approved this month', route('leave.manage.index')],
        ['Approvals waiting', $stats['approvals'], $stats['approvals'] ? '#d97706' : '#64748b', 'bi-inbox', $counts['leave_hr'] . ' leave · ' . $counts['profile'] . ' details · ' . $counts['overtime'] . ' overtime', route('leave.index')],
        ['Contracts ending', $stats['contracts'], $stats['contracts'] ? '#dc2626' : '#64748b', 'bi-hourglass-split', 'next 30 days or overdue', route('settings.contract-reminders.index')],
        ['Net payroll', $payrollSummary ? $kmoney($payrollSummary['net_pay']) : '—', '#0f766e', 'bi-cash-stack', $payrollSummary ? $payrollSummary['period'] . ' · ' . $payrollSummary['status'] : 'no run yet', route('payroll.runs.index')],
    ] as [$l, $v, $c, $ic, $s, $url])
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ $url }}" class="hr-card hr-stat" style="--c:{{ $c }}">
                <div class="d-flex justify-content-between align-items-start gap-1"><div class="l">{{ $l }}</div><i class="bi {{ $ic }}" style="color:{{ $c }};opacity:.6"></i></div>
                <div class="v text-truncate">{{ $v }}</div>
                <div class="s text-truncate" title="{{ $s }}">{{ $s }}</div>
            </a>
        </div>
    @endforeach
</div>

{{-- ============ QUICK ACTIONS + ATTENTION ============ --}}
<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <div class="hr-card h-100">
            <div class="hr-card-h">
                <h6><i class="bi bi-lightning-charge"></i> Quick actions</h6>
                <form method="GET" action="{{ route('employees.index') }}" class="db-find flex-grow-1" style="max-width:300px" role="search">
                    <i class="bi bi-search"></i><input type="search" name="q" class="form-control form-control-sm" placeholder="Find an employee: name, EIN, NRC…" aria-label="Find an employee">
                </form>
            </div>
            <div class="hr-card-b">
                <div class="db-actions">
                    @foreach($actions as [$label, $icon, $url, $c, $badge])
                        <a href="{{ $url }}" class="db-act" style="--c:{{ $c }}"><i class="bi {{ $icon }}"></i>{{ $label }}@if($badge)<span class="bdg">{{ $badge }}</span>@endif</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-bell"></i> Needs your attention</h6><span class="small hr-muted">{{ $attention->count() ?: 'nothing' }}</span></div>
            <div style="max-height:330px; overflow:auto">
                @forelse($attention as $it)
                    <div class="db-att-item" style="--c:{{ $sevColor[$it['sev']] ?? '#64748b' }}">
                        <span class="ic"><i class="bi {{ $it['icon'] }}"></i></span>
                        <div class="flex-grow-1 small"><span class="n">{{ $it['n'] }}</span> {{ $it['msg'] }}</div>
                        @if($it['url'])<a href="{{ $it['url'] }}" class="btn btn-sm btn-light border text-nowrap">{{ $it['cta'] }}</a>@endif
                    </div>
                @empty
                    <div class="hr-empty"><i class="bi bi-check2-circle text-success"></i>All clear. Nothing is waiting on HR right now.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ============ ATTENDANCE / LEAVE / OUT TODAY ============ --}}
<div class="row g-3 mb-3">
    <div class="col-lg-6 col-xl-4">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-qr-code-scan"></i> Attendance today</h6><a href="{{ route('attendance.index') }}" class="small">Open <i class="bi bi-arrow-right"></i></a></div>
            <div class="hr-card-b">
                @if($a && $a['expected'])
                    <div class="db-att">
                        <svg width="110" height="110" viewBox="0 0 110 110" aria-label="Attendance rate">
                            <circle cx="55" cy="55" r="46" fill="none" stroke="#e2e8f0" stroke-width="12"/>
                            <circle cx="55" cy="55" r="46" fill="none" stroke="{{ $rate >= 90 ? '#16a34a' : ($rate >= 75 ? '#d97706' : '#dc2626') }}" stroke-width="12" stroke-linecap="round" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $circ * (1 - $rate / 100) }}" transform="rotate(-90 55 55)"/>
                            <text x="55" y="58" text-anchor="middle" font-size="22" font-weight="800" fill="currentColor">{{ round($rate) }}%</text>
                            <text x="55" y="74" text-anchor="middle" font-size="9" fill="#64748b">in today</text>
                        </svg>
                        <div class="db-mini">
                            <div><b class="text-success">{{ $a['present'] + $a['half_day'] }}</b>On time</div>
                            <div><b style="color:#d97706">{{ $a['late'] }}</b>Late</div>
                            <div><b class="hr-muted">{{ $a['not_in'] }}</b>Not in yet</div>
                            <div><b style="color:#7c3aed">{{ $a['on_leave'] }}</b>On leave</div>
                        </div>
                    </div>
                    @if($lateToday->isNotEmpty())
                        <div class="small fw-semibold mt-3 mb-1">Late arrivals</div>
                        @foreach($lateToday as $r)
                            <div class="d-flex justify-content-between small py-1 border-bottom gap-2"><span class="text-truncate">{{ $r->employee->full_name }}</span><span class="text-nowrap" style="color:#b45309">{{ $r->record?->in_time }} · +{{ $r->record?->minutes_late }}m</span></div>
                        @endforeach
                    @endif
                @else
                    <div class="hr-empty py-3"><i class="bi bi-moon-stars"></i>{{ ($a['holiday'] ?? null) ? 'Public holiday: ' . $a['holiday'] : 'Not a working day.' }}</div>
                @endif
                <div class="d-flex gap-2 mt-3 small flex-wrap">
                    @forelse($kiosks as $k)
                        <span class="hr-chip" style="{{ $k->is_online ? '--bg:#dcfce7;--c:#166534' : '--bg:#fee2e2;--c:#991b1b' }}"><i class="bi bi-tablet"></i> {{ $k->name }}: {{ $k->is_online ? 'online' : 'offline' }}</span>
                    @empty
                        <a href="{{ route('attendance.qr') }}" class="small"><i class="bi bi-plus-circle"></i> Set up the entrance kiosk</a>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-xl-4">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-calendar-check"></i> Leave workflow</h6><a href="{{ route('leave.manage.index') }}" class="small">Manage <i class="bi bi-arrow-right"></i></a></div>
            <div class="hr-card-b pb-2">
                <div class="db-stage">
                    <a href="{{ route('leave.index') }}" style="--c:#d97706"><b>{{ $leavePipeline['awaiting_supervisor'] }}</b><small>Supervisor</small></a>
                    <a href="{{ route('leave.index') }}" style="--c:#0891b2"><b>{{ $leavePipeline['awaiting_hr'] }}</b><small>HR</small></a>
                    <a href="{{ route('leave.manage.index') }}" style="--c:#16a34a"><b>{{ $leavePipeline['approved_month'] }}</b><small>Approved</small></a>
                    <a href="{{ route('leave.manage.index') }}" style="--c:#dc2626"><b>{{ $leavePipeline['rejected_month'] }}</b><small>Rejected</small></a>
                </div>
                <div class="small hr-muted mb-1">In progress · approved / rejected counts are this month</div>
                @forelse($leaveInProgress as $l)
                    <a href="{{ route('leave.show', $l) }}" class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom text-decoration-none" style="color:inherit">
                        <span class="small" style="min-width:0"><span class="fw-semibold d-block text-truncate">{{ $l->employee_name }}</span><span class="hr-muted">{{ $l->leave_type }} · {{ $l->date_from?->format('d M') }}–{{ $l->date_to?->format('d M') }} · {{ $l->total_days }}d</span></span>
                        <span class="hr-chip" style="{{ $l->supervisor_decision ? '--bg:#e0f2fe;--c:#075985' : '--bg:#fef3c7;--c:#92400e' }}">{{ $l->supervisor_decision ? 'With HR' : 'Supervisor' }}</span>
                    </a>
                @empty
                    <div class="small hr-muted py-2">No leave requests waiting.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-12 col-xl-4">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-airplane"></i> Who's away today</h6><span class="small hr-muted">{{ $onLeaveNow->count() }}</span></div>
            <div style="max-height:300px; overflow:auto">
                @forelse($onLeaveNow as $l)
                    <a href="{{ route('leave.show', $l) }}" class="hr-list-item">
                        <span class="hr-av">@if($l->employee?->passport_photo)<img src="{{ asset('storage/' . $l->employee->passport_photo) }}" alt="">@else{{ $l->employee?->initials ?? strtoupper(substr((string) $l->employee_name, 0, 2)) }}@endif</span>
                        <div class="flex-grow-1" style="min-width:0"><div class="fw-semibold text-truncate">{{ $l->employee_name }}</div><div class="small hr-muted">{{ $l->leave_type }} · back {{ $l->return_date ? \Carbon\Carbon::parse($l->return_date)->format('D d M') : $l->date_to->copy()->addDay()->format('D d M') }}</div></div>
                    </a>
                @empty
                    <div class="hr-empty"><i class="bi bi-emoji-smile"></i>Everyone is in. No approved leave today.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ============ UPCOMING / WORKFORCE / ACTIVITY ============ --}}
<div class="row g-3 mb-3">
    <div class="col-lg-6 col-xl-4">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-calendar-event"></i> Coming up</h6><span class="small hr-muted">next few weeks</span></div>
            @forelse($upcoming as $u)
                <a href="{{ $u['url'] }}" class="db-up" style="--c:{{ $u['c'] }}">
                    <div class="d"><b>{{ $u['date']->format('d') }}</b><small>{{ $u['date']->format('M') }}</small></div>
                    <i class="bi {{ $u['icon'] }} ic"></i>
                    <div class="flex-grow-1" style="min-width:0"><div class="fw-semibold small text-truncate">{{ $u['title'] }}</div><div class="small hr-muted text-truncate">{{ $u['sub'] }} · {{ $u['date']->isSameDay($now) ? 'today' : $u['date']->format('l') }}</div></div>
                </a>
            @empty
                <div class="hr-empty"><i class="bi bi-calendar2-check"></i>No birthdays, anniversaries, contract ends or holidays coming up.</div>
            @endforelse
        </div>
    </div>

    <div class="col-lg-6 col-xl-4">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-diagram-3"></i> Workforce</h6><a href="{{ route('departments.index') }}" class="small">Departments <i class="bi bi-arrow-right"></i></a></div>
            <div class="hr-card-b">
                @foreach($byDept->take(7) as $name => $n)
                    <a href="{{ $name === 'No department' ? route('employees.index') : route('departments.show', \App\Services\DepartmentStats::slug($name)) }}" class="db-dept">
                        <span class="n" title="{{ $name }}">{{ $name }}</span>
                        <span class="b"><span style="width:{{ $n / $deptMax * 100 }}%;background:{{ $palette[$loop->index % count($palette)] }}"></span></span>
                        <b style="width:24px;text-align:right">{{ $n }}</b>
                    </a>
                @endforeach
                @if($byDept->isEmpty())<div class="small hr-muted">No active employees yet.</div>@endif
                <hr class="my-2">
                <div class="d-flex justify-content-between small mb-1"><span><i class="bi bi-gender-male text-primary"></i> {{ $workforce['male'] }} men</span><span><i class="bi bi-gender-female" style="color:#db2777"></i> {{ $workforce['female'] }} women</span></div>
                <div class="hr-bar mb-2">@if($stats['active'])<span style="width:{{ $workforce['male'] / $stats['active'] * 100 }}%;background:#3b82f6"></span><span style="width:{{ $workforce['female'] / $stats['active'] * 100 }}%;background:#ec4899"></span>@endif</div>
                <div class="d-flex flex-wrap gap-1">
                    @foreach($workforce['natures'] as $k => $n)<span class="hr-chip">{{ $k }}: {{ $n }}</span>@endforeach
                    @if($workforce['avg_age'])<span class="hr-chip">Avg age {{ $workforce['avg_age'] }}</span>@endif
                    @if($stats['exited_year'])<span class="hr-chip" style="--bg:#fee2e2;--c:#991b1b">{{ $stats['exited_year'] }} left this year</span>@endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-12 col-xl-4">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-activity"></i> Recent activity</h6></div>
            <div class="db-feed">
                @forelse($activity as $x)
                    <a href="{{ $x['url'] }}" style="--c:{{ $x['c'] }}"><span class="dot"><i class="bi {{ $x['icon'] }}"></i></span><span style="min-width:0"><span class="d-block text-truncate">{{ $x['text'] }}</span><span class="small hr-muted">{{ $x['at']->diffForHumans() }}</span></span></a>
                @empty
                    <div class="hr-empty">Nothing yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ============ ANNOUNCEMENTS + PAYROLL ============ --}}
<div class="row g-3">
    <div class="col-lg-{{ $payrollSummary ? 7 : 12 }}">
        <div class="hr-card h-100">
            <div class="hr-card-h"><h6><i class="bi bi-megaphone"></i> Live announcements</h6><a href="{{ route('announcements.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New</a></div>
            @forelse($announcements as $an)
                @php [, $ci, $cc] = $an->category_meta; @endphp
                <a href="{{ route('announcements.show', $an) }}" class="an-item">
                    <span class="an-ic" style="--c:{{ $cc }}"><i class="bi {{ $ci }}"></i></span>
                    <div style="min-width:0" class="flex-grow-1"><div class="an-title text-truncate">@if($an->pinned)<i class="bi bi-pin-angle-fill text-primary"></i> @endif{{ $an->title }}</div><div class="small hr-muted text-truncate">{{ $an->audience_label }} · {{ $an->excerpt }}</div></div>
                    <span class="small hr-muted text-nowrap"><i class="bi bi-eye"></i> {{ $an->reads_count }}</span>
                </a>
            @empty
                <div class="hr-empty"><i class="bi bi-megaphone"></i>No live announcements. Share news with everyone or one department. Staff see it in their portal.</div>
            @endforelse
        </div>
    </div>
    @if($payrollSummary)
        <div class="col-lg-5">
            <div class="hr-card h-100">
                <div class="hr-card-h"><h6><i class="bi bi-cash-stack"></i> Latest payroll</h6><a href="{{ route('payroll.runs.show', $payrollSummary['run']) }}" class="small">Open <i class="bi bi-arrow-right"></i></a></div>
                <div class="hr-card-b">
                    <div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-bold">{{ $payrollSummary['period'] }}</span><span class="hr-chip" style="{{ in_array($payrollSummary['status'], ['Approved', 'Paid']) ? '--bg:#dcfce7;--c:#166534' : '--bg:#fef3c7;--c:#92400e' }}">{{ $payrollSummary['status'] }}</span></div>
                    <div class="row g-2 text-center">
                        <div class="col-4"><div class="small hr-muted">Employees</div><div class="fw-bold">{{ $payrollSummary['count'] }}</div></div>
                        <div class="col-4"><div class="small hr-muted">Gross</div><div class="fw-bold">{{ $money($payrollSummary['earnings']) }}</div></div>
                        <div class="col-4"><div class="small hr-muted">Net pay</div><div class="fw-bold text-success">{{ $money($payrollSummary['net_pay']) }}</div></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Live clock in Lusaka time, whatever the computer's own time zone is.
    const base = { server: Date.parse(@json($now->format('Y-m-d\TH:i:s')) + 'Z'), perf: performance.now() };
    const pad = n => String(n).padStart(2, '0');
    const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const week = @json($now->isoWeek);
    function tick() {
        const d = new Date(base.server + (performance.now() - base.perf));
        document.getElementById('dbClock').innerHTML = pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()) + '<small>:' + pad(d.getUTCSeconds()) + '</small>';
        document.getElementById('dbDate').textContent = DAYS[d.getUTCDay()] + ', ' + pad(d.getUTCDate()) + ' ' + MONTHS[d.getUTCMonth()] + ' ' + d.getUTCFullYear() + ' · Week ' + week;
        // Keep the card's colours in step with the time of day.
        const h = d.getUTCHours(), part = h >= 5 && h < 12 ? 'morning' : (h >= 12 && h < 17 ? 'afternoon' : 'evening');
        const hero = document.getElementById('dbHero');
        if (!hero.classList.contains('is-' + part)) { hero.classList.remove('is-morning', 'is-afternoon', 'is-evening'); hero.classList.add('is-' + part); }
    }
    tick(); setInterval(tick, 1000);

    // Shuffle proverbs
    const Q = @json($quotes);
    const box = document.getElementById('dbQuote');
    document.getElementById('qNext').addEventListener('click', () => {
        const i = (parseInt(box.dataset.i, 10) + 1) % Q.length; box.dataset.i = i; const q = Q[i];
        document.getElementById('qA').textContent = q.lang + ' · Zambian wisdom for HR';
        document.getElementById('qT').textContent = q.text;
        document.getElementById('qM').textContent = '“' + q.meaning + '”';
        document.getElementById('qL').innerHTML = '<i class="bi bi-lightbulb"></i> ' + q.lesson.replace(/[<>&]/g, '');
    });
})();
</script>
@endpush
