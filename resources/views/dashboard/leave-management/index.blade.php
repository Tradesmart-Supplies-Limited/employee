@extends('layouts.app')
@php $hr = (bool) auth()->user()?->hasFullAccess(); @endphp

@section('content')

@php
    $n = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $k = $r->kpis;
    $year = $f['year'];
    $qs = fn (array $extra = []) => array_filter(array_merge(request()->only(['year', 'department', 'branch', 'q', 'include_inactive', 'flag']), $extra), fn ($v) => $v !== null && $v !== '');
    $sortLink = function ($col, $label) use ($f, $qs) {
        $dir = ($f['sort'] === $col && $f['dir'] === 'asc') ? 'desc' : 'asc';
        $icon = $f['sort'] === $col ? ($f['dir'] === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill') : 'bi-chevron-expand';
        return '<a href="' . e(route('leave.manage.index', $qs(['sort' => $col, 'dir' => $dir]))) . '" class="lm-sort">' . e($label) . ' <i class="bi ' . $icon . '"></i></a>';
    };
    $maxMonth = max(1, collect($r->monthly)->map(fn ($m) => $m['approved'] + $m['pending'])->max());
    $maxType = max(1, collect($r->byType)->map(fn ($t) => $t['approved'] + $t['pending'])->max());
@endphp

@include('leave.partials.tracker-styles')

<style>
.lm * { box-sizing: border-box; }
.lm-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
.lm-eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--admin-primary, #185FA5); }
.lm-head h1 { font-size: 1.4rem; font-weight: 800; margin: .15rem 0 0; }
.lm-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: 1rem 1.1rem; }
.lm-card h2 { font-size: .9rem; font-weight: 700; margin: 0 0 .8rem; display: flex; align-items: center; gap: .45rem; }
.lm-card h2 .sub { font-weight: 500; font-size: .76rem; color: var(--admin-muted, #64748b); margin-left: auto; }
.lm-filters { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
.lm-filters .form-select, .lm-filters .form-control { width: auto; min-width: 130px; font-size: .82rem; }

.lm-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .7rem; margin-bottom: 1rem; }
.lm-kpi { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 12px; padding: .8rem .95rem; text-decoration: none; color: inherit; display: block; position: relative; overflow: hidden; }
.lm-kpi::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 3px; background: var(--c, #185FA5); }
.lm-kpi .l { font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--admin-muted, #64748b); }
.lm-kpi .v { font-size: 1.55rem; font-weight: 800; line-height: 1.15; color: var(--admin-text, #0f172a); }
.lm-kpi .s { font-size: .74rem; color: var(--admin-muted, #64748b); }
a.lm-kpi:hover { border-color: var(--c); }

/* monthly chart */
.lm-chart { display: grid; grid-template-columns: repeat(12, 1fr); gap: 6px; align-items: end; height: 180px; padding-top: 18px; border-bottom: 1px solid var(--admin-border, #e2e8f0); position: relative; }
.lm-col { display: flex; flex-direction: column-reverse; align-items: center; gap: 2px; height: 100%; position: relative; cursor: default; }
.lm-col .seg { width: 60%; max-width: 26px; border-radius: 0; }
.lm-col .seg.ap { background: #16a34a; }
.lm-col .seg.pe { background: #f59e0b; }
.lm-col .seg:last-child { border-radius: 4px 4px 0 0; }
.lm-col .val { font-size: .68rem; font-weight: 700; color: var(--admin-muted, #64748b); }
.lm-col.is-now .val { color: var(--admin-text, #0f172a); }
.lm-col:hover .seg { filter: brightness(1.08); }
.lm-col .tip { display: none; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #0f172a; color: #fff; font-size: .72rem; padding: .35rem .55rem; border-radius: 6px; white-space: nowrap; z-index: 3; pointer-events: none; }
.lm-col:hover .tip { display: block; }
.lm-months { display: grid; grid-template-columns: repeat(12, 1fr); gap: 6px; font-size: .7rem; color: var(--admin-muted, #64748b); text-align: center; margin-top: 4px; }
.lm-months .is-now { color: var(--admin-text, #0f172a); font-weight: 700; }
.lm-legend { display: flex; gap: .9rem; font-size: .74rem; color: var(--admin-muted, #64748b); }
.lm-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: .3rem; vertical-align: -1px; }

/* horizontal bars */
.lm-hbar { display: grid; grid-template-columns: 120px 1fr 64px; gap: .6rem; align-items: center; padding: .38rem 0; font-size: .8rem; }
.lm-hbar .track { height: 10px; background: var(--admin-surface-soft, #f1f5f9); border-radius: 5px; display: flex; overflow: hidden; gap: 2px; }
.lm-hbar .track span { height: 100%; }
.lm-hbar .num { text-align: right; font-weight: 700; }

/* lists */
.lm-away { display: flex; align-items: center; gap: .6rem; padding: .5rem 0; border-bottom: 1px solid var(--admin-border, #e2e8f0); font-size: .8rem; text-decoration: none; color: inherit; }
.lm-away:last-child { border-bottom: 0; }
.lm-av { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #185FA5, #4f8cff); color: #fff; font-weight: 700; font-size: .7rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.lm-badge { font-size: .68rem; font-weight: 700; padding: .12rem .5rem; border-radius: 999px; white-space: nowrap; }
.lm-badge.ok { background: #dcfce7; color: #166534; } .lm-badge.wait { background: #fef3c7; color: #92400e; }
.lm-badge.bad { background: #fee2e2; color: #991b1b; } .lm-badge.info { background: #e0f2fe; color: #075985; }
.lm-badge.mute { background: #f1f5f9; color: #475569; }

/* table */
.lm-table { font-size: .82rem; }
.lm-table th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
.lm-sort { color: inherit; text-decoration: none; }
.lm-sort i { font-size: .65rem; opacity: .6; }
.lm-util { display: flex; align-items: center; gap: .4rem; min-width: 110px; }
.lm-util .track { flex: 1; height: 6px; border-radius: 3px; background: var(--admin-surface-soft, #f1f5f9); overflow: hidden; }
.lm-util .track span { display: block; height: 100%; background: #185FA5; border-radius: 3px; }
.lm-util small { width: 34px; text-align: right; color: var(--admin-muted, #64748b); }
.lm-bal { font-weight: 800; }
.lm-flag-tabs { display: flex; gap: .35rem; flex-wrap: wrap; }
.lm-flag-tabs a { font-size: .74rem; padding: .25rem .65rem; border-radius: 999px; border: 1px solid var(--admin-border, #e2e8f0); text-decoration: none; color: var(--admin-text, #0f172a); }
.lm-flag-tabs a.active { background: #185FA5; color: #fff; border-color: #185FA5; }
</style>

<div class="lm">

    {{-- ===== HEADER ===== --}}
    <div class="lm-head">
        <div>
            <div class="lm-eyebrow">Leave Management</div>
            <h1>Leave Reports &amp; Balances · {{ $year }}</h1>
            <div class="small text-muted">Everyone's entitlement, days taken, balances and requests in one place.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($hr)<a href="{{ route('leave.manage.record') }}" class="btn btn-primary btn-sm"><i class="bi bi-journal-plus"></i> Record leave</a>@endif
            <a href="{{ route('leave.manage.planner') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar3-range"></i> Planner</a>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download"></i> Export</button>
                <ul class="dropdown-menu dropdown-menu-end small">
                    <li><a class="dropdown-item" href="{{ route('leave.manage.export.balances', $qs()) }}"><i class="bi bi-filetype-csv"></i> Balances (as filtered)</a></li>
                    <li><a class="dropdown-item" href="{{ route('leave.manage.export.requests', ['year' => $year, 'department' => $f['department']]) }}"><i class="bi bi-filetype-csv"></i> All requests {{ $year }}</a></li>
                    <li><a class="dropdown-item" href="{{ route('leave.manage.export.requests', ['year' => $year, 'status' => 'Approved']) }}"><i class="bi bi-filetype-csv"></i> Approved requests {{ $year }}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="window.print();return false;"><i class="bi bi-printer"></i> Print this report</a></li>
                </ul>
            </div>
            @if($hr)<button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#newYearModal"><i class="bi bi-arrow-repeat"></i> New leave year</button>@endif
        </div>
    </div>

    {{-- ===== FILTERS ===== --}}
    <form method="GET" class="lm-card mb-3">
        <div class="lm-filters">
            <i class="bi bi-funnel text-muted"></i>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($options['years'] as $y)<option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>@endforeach
            </select>
            <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                @if(auth()->user()?->hasFullAccess())<option value="">All departments</option>@endif
                @foreach($options['departments'] as $d)<option @selected($f['department'] === $d)>{{ $d }}</option>@endforeach
            </select>
            @if($options['branches']->isNotEmpty())
                <select name="branch" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All branches</option>
                    @foreach($options['branches'] as $b)<option @selected($f['branch'] === $b)>{{ $b }}</option>@endforeach
                </select>
            @endif
            <input type="search" name="q" value="{{ $f['q'] }}" class="form-control form-control-sm" placeholder="Search name / EIN">
            <div class="form-check small mb-0">
                <input class="form-check-input" type="checkbox" name="include_inactive" value="1" id="incInactive" @checked($f['include_inactive']) onchange="this.form.submit()">
                <label class="form-check-label" for="incInactive">Include exited / suspended</label>
            </div>
            <button class="btn btn-sm btn-primary">Apply</button>
            @if(request()->hasAny(['department', 'branch', 'q', 'include_inactive', 'flag']))
                <a href="{{ route('leave.manage.index', ['year' => $year]) }}" class="btn btn-sm btn-link">Clear</a>
            @endif
        </div>
    </form>

    {{-- ===== KPIs ===== --}}
    <div class="lm-kpis">
        <div class="lm-kpi" style="--c:#185FA5"><div class="l">Employees</div><div class="v">{{ $k['headcount'] }}</div><div class="s">{{ $n($k['entitlement']) }} days entitled</div></div>
        <div class="lm-kpi" style="--c:#16a34a"><div class="l">Days taken</div><div class="v">{{ $n($k['taken']) }}</div><div class="s">{{ $k['utilization'] }}% of entitlement used</div></div>
        <div class="lm-kpi" style="--c:#0ea5e9"><div class="l">Days remaining</div><div class="v">{{ $n($k['balance_total']) }}</div><div class="s">avg {{ $n($k['avg_balance']) }} per person</div></div>
        <a class="lm-kpi" style="--c:#f59e0b" href="{{ route('leave.index', ['stage' => 'awaiting_supervisor']) }}"><div class="l">Pending requests</div><div class="v">{{ $k['pending'] }}</div><div class="s">{{ $k['awaiting_supervisor'] }} supervisor · {{ $k['awaiting_hr'] }} HR</div></a>
        <a class="lm-kpi" style="--c:#16a34a" href="{{ route('leave.index', ['stage' => 'approved']) }}"><div class="l">Approved</div><div class="v">{{ $k['approved'] }}</div><div class="s">{{ $n($k['taken_all']) }} days, all types</div></a>
        <a class="lm-kpi" style="--c:#dc2626" href="{{ route('leave.index', ['stage' => 'rejected']) }}"><div class="l">Rejected</div><div class="v">{{ $k['rejected'] }}</div><div class="s">approval rate {{ $k['approval_rate'] === null ? '—' : $k['approval_rate'] . '%' }}</div></a>
        <div class="lm-kpi" style="--c:#7c3aed"><div class="l">Avg decision time</div><div class="v">{{ $k['avg_turnaround_h'] === null ? '—' : ($k['avg_turnaround_h'] >= 48 ? $n($k['avg_turnaround_h'] / 24) . 'd' : $n($k['avg_turnaround_h']) . 'h') }}</div><div class="s">submit → HR decision</div></div>
        <a class="lm-kpi" style="--c:#dc2626" href="{{ route('leave.manage.index', $qs(['flag' => 'low'])) }}"><div class="l">Low / negative</div><div class="v">{{ $k['low_balance'] }} <span class="fs-6 text-danger">/ {{ $k['negative_balance'] }}</span></div><div class="s">≤ {{ $n(config('leave.low_balance_threshold')) }} days left / below 0</div></a>
    </div>

    <div class="row g-3 mb-3">
        {{-- ===== MONTHLY ===== --}}
        <div class="col-xl-8">
            <div class="lm-card h-100">
                <h2><i class="bi bi-bar-chart"></i> Leave days by month <span class="sub">by first day of leave, {{ $year }}</span></h2>
                <div class="lm-legend mb-1"><span><i style="background:#16a34a"></i>Approved</span><span><i style="background:#f59e0b"></i>Pending</span></div>
                <div class="lm-chart" role="img" aria-label="Approved and pending leave days per month">
                    @foreach($r->monthly as $m => $row)
                        @php $isNow = $year == now()->year && $m == now()->month; $tot = $row['approved'] + $row['pending']; @endphp
                        <div class="lm-col {{ $isNow ? 'is-now' : '' }}">
                            @if($row['approved'] > 0)<span class="seg ap" style="height: {{ $row['approved'] / $maxMonth * 150 }}px"></span>@endif
                            @if($row['pending'] > 0)<span class="seg pe" style="height: {{ $row['pending'] / $maxMonth * 150 }}px"></span>@endif
                            @if($tot > 0)<span class="val">{{ $n($tot) }}</span>@endif
                            <span class="tip">{{ $row['label'] }}: {{ $n($row['approved']) }} approved · {{ $n($row['pending']) }} pending · {{ $n($row['rejected']) }} rejected</span>
                        </div>
                    @endforeach
                </div>
                <div class="lm-months">
                    @foreach($r->monthly as $m => $row)<span class="{{ $year == now()->year && $m == now()->month ? 'is-now' : '' }}">{{ $row['label'] }}</span>@endforeach
                </div>
                <details class="mt-2 small">
                    <summary class="text-muted">Show as table</summary>
                    <div class="table-responsive"><table class="table table-sm mt-2 mb-0">
                        <thead><tr><th>Month</th><th class="text-end">Approved</th><th class="text-end">Pending</th><th class="text-end">Rejected</th></tr></thead>
                        <tbody>@foreach($r->monthly as $row)<tr><td>{{ $row['label'] }}</td><td class="text-end">{{ $n($row['approved']) }}</td><td class="text-end">{{ $n($row['pending']) }}</td><td class="text-end">{{ $n($row['rejected']) }}</td></tr>@endforeach</tbody>
                    </table></div>
                </details>
            </div>
        </div>

        {{-- ===== WHO'S AWAY ===== --}}
        <div class="col-xl-4">
            <div class="lm-card h-100">
                <h2><i class="bi bi-airplane"></i> Who's away <span class="sub">today &amp; next 30 days</span></h2>
                @forelse($r->onLeaveToday as $l)
                    <a href="{{ route('leave.show', $l) }}" class="lm-away">
                        <span class="lm-av">{{ strtoupper(collect(explode(' ', $l->employee_name))->map(fn ($p) => substr($p, 0, 1))->take(2)->implode('')) }}</span>
                        <span class="flex-grow-1"><strong>{{ $l->employee_name }}</strong><br><span class="text-muted">{{ $l->leave_type }} · back {{ optional($l->return_date)->format('D d M') ?? $l->date_to->copy()->addDay()->format('D d M') }}</span></span>
                        <span class="lm-badge ok">Away now</span>
                    </a>
                @empty
                    <div class="small text-muted mb-2"><i class="bi bi-check2-circle text-success"></i> Nobody is on leave today.</div>
                @endforelse
                @foreach($r->upcoming->take(8) as $l)
                    <a href="{{ route('leave.show', $l) }}" class="lm-away">
                        <span class="lm-av" style="background:#cbd5e1;color:#334155">{{ $l->date_from->format('d') }}</span>
                        <span class="flex-grow-1"><strong>{{ $l->employee_name }}</strong><br><span class="text-muted">{{ $l->date_from->format('D d M') }} – {{ $l->date_to->format('d M') }} · {{ $l->total_days }}d {{ $l->leave_type }}</span></span>
                        <span class="lm-badge {{ $l->status === 'Approved' ? 'info' : 'wait' }}">{{ $l->status === 'Approved' ? 'Booked' : 'Pending' }}</span>
                    </a>
                @endforeach
                @if($r->upcoming->count() > 8)
                    <a href="{{ route('leave.manage.planner') }}" class="small">+{{ $r->upcoming->count() - 8 }} more in the planner</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- ===== BY TYPE ===== --}}
        <div class="col-lg-6">
            <div class="lm-card h-100">
                <h2><i class="bi bi-tags"></i> By leave type <span class="sub">days · {{ $year }}</span></h2>
                @forelse($r->byType as $t)
                    <div class="lm-hbar" title="{{ $t['type'] }}: {{ $n($t['approved']) }} approved, {{ $n($t['pending']) }} pending days · {{ $t['requests'] }} request(s), {{ $t['rejected'] }} rejected">
                        <span>{{ $t['type'] }} @if($t['deductible'])<i class="bi bi-wallet2 text-muted" title="Reduces the balance"></i>@endif</span>
                        <span class="track">
                            <span style="width: {{ $t['approved'] / $maxType * 100 }}%; background:#16a34a"></span>
                            <span style="width: {{ $t['pending'] / $maxType * 100 }}%; background:#f59e0b"></span>
                        </span>
                        <span class="num">{{ $n($t['approved']) }}<small class="text-muted fw-normal">{{ $t['pending'] > 0 ? ' +' . $n($t['pending']) : '' }}</small></span>
                    </div>
                @empty
                    <div class="small text-muted">No leave recorded for {{ $year }} yet.</div>
                @endforelse
                @if($r->byType)
                    <div class="lm-legend mt-2"><span><i style="background:#16a34a"></i>Approved</span><span><i style="background:#f59e0b"></i>Pending</span><span><i class="bi bi-wallet2" style="width:auto;height:auto;background:none"></i>reduces balance</span></div>
                @endif
            </div>
        </div>

        {{-- ===== BY DEPARTMENT ===== --}}
        <div class="col-lg-6">
            <div class="lm-card h-100">
                <h2><i class="bi bi-diagram-3"></i> By department</h2>
                <div class="table-responsive">
                    <table class="table table-sm lm-table mb-0 align-middle">
                        <thead><tr><th>Department</th><th class="text-end">Staff</th><th class="text-end">Taken</th><th class="text-end">Avg bal.</th><th class="text-end">Pend.</th><th style="min-width:120px">Used</th></tr></thead>
                        <tbody>
                            @forelse($r->byDepartment as $d)
                                <tr>
                                    <td><a href="{{ route('leave.manage.index', $qs(['department' => $d['department'] === 'Unassigned' ? null : $d['department']])) }}" class="text-decoration-none">{{ $d['department'] }}</a></td>
                                    <td class="text-end">{{ $d['headcount'] }}</td>
                                    <td class="text-end">{{ $n($d['taken']) }}</td>
                                    <td class="text-end">{{ $n($d['avg_balance']) }}</td>
                                    <td class="text-end">{{ $d['pending'] }}</td>
                                    <td><div class="lm-util"><span class="track"><span style="width: {{ min(100, $d['utilization']) }}%"></span></span><small>{{ $d['utilization'] }}%</small></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No employees match.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== PENDING QUEUE ===== --}}
    @if($r->pendingQueue->isNotEmpty())
        <div class="lm-card mb-3">
            <h2><i class="bi bi-hourglass-split"></i> Waiting for a decision <span class="sub">HR-ready first, then oldest</span></h2>
            <div class="table-responsive">
                <table class="table table-sm lm-table align-middle mb-0">
                    <thead><tr><th>Employee</th><th>Leave</th><th>Dates</th><th>Waiting</th><th>Supervisor</th><th style="min-width:170px">Stage</th><th></th></tr></thead>
                    <tbody>
                        @foreach($r->pendingQueue as $l)
                            @php $age = $l->created_at->diffInDays(now()); @endphp
                            <tr>
                                <td class="fw-semibold">{{ $l->employee_name }}</td>
                                <td>{{ $l->leave_type }} · {{ $l->total_days }}d</td>
                                <td>{{ $l->date_from->format('d M') }} – {{ $l->date_to->format('d M') }}</td>
                                <td><span class="lm-badge {{ $age >= 3 ? 'bad' : 'mute' }}">{{ $age < 1 ? 'today' : floor($age) . ' day(s)' }}</span></td>
                                <td>{{ $l->assigned_supervisor_name ?? '—' }}</td>
                                <td>@include('leave.partials.mini-tracker', ['leave' => $l])</td>
                                <td class="text-end"><a href="{{ route('leave.show', $l) }}" class="btn btn-sm btn-primary">Review</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== BALANCES TABLE ===== --}}
    <div class="lm-card mb-3">
        <h2>
            <i class="bi bi-people"></i> Employee leave balances
            <span class="sub">{{ $r->employees->count() }} employee(s)</span>
        </h2>

        @php
            $flags = [null => 'Everyone', 'pending' => 'Has pending', 'on_leave' => 'Away today', 'low' => 'Low balance', 'negative' => 'Negative', 'unused' => 'No leave taken'];
        @endphp
        <div class="lm-flag-tabs mb-2">
            @foreach($flags as $key => $label)
                <a href="{{ route('leave.manage.index', $qs(['flag' => $key])) }}" class="{{ ($f['flag'] ?? null) === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-hover lm-table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{!! $sortLink('name', 'Employee') !!}</th>
                        <th>{!! $sortLink('department', 'Department') !!}</th>
                        <th class="text-end">{!! $sortLink('entitlement', 'Entitled') !!}</th>
                        <th class="text-end">{!! $sortLink('taken', 'Taken') !!}</th>
                        <th class="text-end">{!! $sortLink('other_taken', 'Other') !!}</th>
                        <th class="text-end">{!! $sortLink('pending_days', 'Pending d') !!}</th>
                        <th class="text-end">{!! $sortLink('balance', 'Balance') !!}</th>
                        <th>{!! $sortLink('utilization', 'Used') !!}</th>
                        <th class="text-center" title="Approved / Pending / Rejected requests">A / P / R</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($r->employees as $row)
                        <tr>
                            <td>
                                @if($hr)<a href="{{ route('leave.manage.employee', $row->employee) }}" class="fw-semibold text-decoration-none">{{ $row->name }}</a>@else<span class="fw-semibold">{{ $row->name }}</span>@endif
                                <div class="text-muted" style="font-size:.72rem">{{ $row->employee->employee_id }}{{ $row->employee->supervisor_name ? ' · reports to ' . $row->employee->supervisor_name : '' }}</div>
                            </td>
                            <td>{{ $row->department ?? '—' }}</td>
                            <td class="text-end">{{ $n($row->entitlement) }}</td>
                            <td class="text-end">{{ $n($row->taken) }}</td>
                            <td class="text-end text-muted">{{ $n($row->other_taken) }}</td>
                            <td class="text-end">{!! $row->pending_days > 0 ? '<span class="text-warning fw-semibold" style="color:#b45309!important">' . $n($row->pending_days) . '</span>' : '0' !!}</td>
                            <td class="text-end lm-bal {{ $row->is_negative ? 'text-danger' : ($row->is_low ? '' : 'text-success') }}" style="{{ $row->is_low ? 'color:#b45309' : '' }}">{{ $n($row->balance) }}</td>
                            <td><div class="lm-util"><span class="track"><span style="width: {{ min(100, $row->utilization) }}%"></span></span><small>{{ $row->utilization }}%</small></div></td>
                            <td class="text-center text-nowrap"><span class="text-success">{{ $row->approved }}</span> / <span style="color:#b45309">{{ $row->pending }}</span> / <span class="text-danger">{{ $row->rejected }}</span></td>
                            <td class="text-nowrap">
                                @if($row->on_leave)<span class="lm-badge ok" title="Back {{ optional($row->on_leave->return_date)->format('d M') }}"><i class="bi bi-airplane"></i> Away</span>
                                @elseif($row->next_leave)<span class="lm-badge info" title="{{ $row->next_leave->leave_type }}">From {{ $row->next_leave->date_from->format('d M') }}</span>@endif
                                @if($row->is_negative)<span class="lm-badge bad">Negative</span>
                                @elseif($row->is_low)<span class="lm-badge wait">Low</span>@endif
                            </td>
                            <td class="text-end text-nowrap">
@if($hr)
                                <a href="{{ route('leave.manage.employee', $row->employee) }}" class="btn btn-sm btn-outline-secondary" title="Leave record &amp; adjust balance"><i class="bi bi-sliders"></i></a>
                                <a href="{{ route('leave.manage.record', ['employee' => $row->employee->id]) }}" class="btn btn-sm btn-outline-primary" title="Record leave"><i class="bi bi-journal-plus"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">No employees match these filters.</td></tr>
                    @endforelse
                </tbody>
                @if($r->employees->isNotEmpty())
                    <tfoot class="table-light fw-semibold">
                        <tr>
                            <td colspan="2">Totals</td>
                            <td class="text-end">{{ $n($r->employees->sum('entitlement')) }}</td>
                            <td class="text-end">{{ $n($r->employees->sum('taken')) }}</td>
                            <td class="text-end">{{ $n($r->employees->sum('other_taken')) }}</td>
                            <td class="text-end">{{ $n($r->employees->sum('pending_days')) }}</td>
                            <td class="text-end">{{ $n($r->employees->sum('balance')) }}</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        <div class="small text-muted mt-2">
            <strong>Taken</strong> = approved {{ implode(' / ', config('leave.deductible_types')) }} days that reduce the balance ·
            <strong>Other</strong> = approved sick, maternity, compassionate etc. ·
            <strong>Used</strong> = taken ÷ entitlement.
        </div>
    </div>
</div>

{{-- ===== NEW LEAVE YEAR MODAL ===== --}}
@push('modals')
@if($hr)
<div class="modal fade" id="newYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('leave.manage.new-year') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Start a new leave year</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body small">
                <p>For every <strong>active</strong> employee, the balance becomes:</p>
                <p class="p-2 rounded" style="background:#f1f5f9"><strong>yearly entitlement + unused days carried over</strong> (up to the cap). Negative balances are carried in full.</p>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Leave year</label>
                        @php $suggest = now()->month >= 11 ? now()->year + 1 : now()->year; @endphp
                        <select name="year" class="form-select form-select-sm">
                            @foreach([now()->year, now()->year + 1] as $y)
                                <option value="{{ $y }}" @selected($y == $suggest)>{{ $y }}{{ in_array($y, $startedYears) ? ' (already started)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Max days carried over</label>
                        <input type="number" name="carry_max" step="0.5" min="0" class="form-control form-control-sm" value="{{ $carryMax }}" placeholder="blank = no limit">
                    </div>
                </div>
                <p class="mt-3 mb-2 text-muted">Each change is recorded in the employee's balance history. Employees already started for that year are skipped, so running it twice is safe.</p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="confirm" value="1" id="nyConfirm" required>
                    <label class="form-check-label" for="nyConfirm">I understand this updates every active employee's balance.</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success btn-sm"><i class="bi bi-check2"></i> Start leave year</button>
            </div>
        </form>
    </div>
</div>
@endif
@endpush

<style media="print">
    .admin-sidebar, .admin-topbar, .navbar, .lm-head .btn, .lm-head .dropdown, form.lm-card, .lm-flag-tabs, .lm-table td:last-child { display: none !important; }
    .lm-card { break-inside: avoid; }
</style>

@endsection
