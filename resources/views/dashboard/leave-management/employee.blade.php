@extends('layouts.app')

@section('content')

@php
    $n = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $ent = (float) $employee->annual_leave_entitlement;
    $bal = (float) $employee->leave_balance;
    $pct = $ent > 0 ? max(0, min(100, $bal / $ent * 100)) : 0;
    $thisYear = $years[$year] ?? ['taken' => 0, 'other' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0];
    $pendingDays = $employee->leavePendingDays();
@endphp

@include('leave.partials.tracker-styles')

<style>
.le-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: 1rem 1.1rem; }
.le-card h2 { font-size: .9rem; font-weight: 700; margin: 0 0 .8rem; }
.le-stat small { display: block; font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--admin-muted, #64748b); }
.le-stat strong { font-size: 1.35rem; }
.le-ledger td { font-size: .8rem; }
.le-chip { font-size: .68rem; font-weight: 700; padding: .1rem .5rem; border-radius: 999px; background: #f1f5f9; color: #475569; white-space: nowrap; }
.le-plus { color: #15803d; font-weight: 700; } .le-minus { color: #b91c1c; font-weight: 700; }
.le-mode .btn { font-size: .78rem; }
</style>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <a href="{{ route('leave.manage.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Leave reports</a>
        <h1 class="h4 fw-bold mb-0 mt-1">{{ $employee->full_name }}</h1>
        <div class="small text-muted">
            {{ $employee->employee_id }} · {{ $employee->position ?? '—' }}{{ $employee->department ? ' · ' . $employee->department : '' }}
            · reports to <strong>{{ $employee->supervisor_name ?? 'nobody (HR reviews)' }}</strong>
            · {{ $employee->user ? 'has portal login' : 'no portal login' }}
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('leave.manage.record', ['employee' => $employee->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-journal-plus"></i> Record leave</a>
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Edit employee</a>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- BALANCE --}}
    <div class="col-lg-4">
        <div class="le-card h-100 d-flex align-items-center gap-3">
            <div class="lt-balance-ring" style="--pct: {{ round($pct) }}; width:110px; height:110px">
                <div style="width:86px;height:86px"><strong>{{ $n($bal) }}</strong><small>days left</small></div>
            </div>
            <div class="small">
                <div class="fw-semibold mb-1">Leave balance</div>
                <div class="text-muted">Entitlement {{ $n($ent) }} / year</div>
                <div class="text-muted">{{ $n($thisYear['taken']) }} taken in {{ $year }}</div>
                @if($pendingDays > 0)<div style="color:#b45309">{{ $n($pendingDays) }} in pending requests</div>@endif
                @if($bal < 0)<div class="text-danger fw-semibold">Overdrawn</div>@endif
            </div>
        </div>
    </div>

    {{-- YEAR STATS --}}
    <div class="col-lg-8">
        <div class="le-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="mb-0">{{ $year }} at a glance</h2>
                <form method="GET">
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach(collect($years->keys())->push(now()->year)->unique()->sortDesc() as $y)<option @selected($y == $year)>{{ $y }}</option>@endforeach
                    </select>
                </form>
            </div>
            <div class="row g-2">
                <div class="col-6 col-md-3 le-stat"><small>Taken</small><strong>{{ $n($thisYear['taken']) }}</strong></div>
                <div class="col-6 col-md-3 le-stat"><small>Other leave</small><strong>{{ $n($thisYear['other']) }}</strong></div>
                <div class="col-6 col-md-3 le-stat"><small>Requests A / P / R</small><strong><span class="text-success">{{ $thisYear['approved'] }}</span> / <span style="color:#b45309">{{ $thisYear['pending'] }}</span> / <span class="text-danger">{{ $thisYear['rejected'] }}</span></strong></div>
                <div class="col-6 col-md-3 le-stat"><small>Used</small><strong>{{ $ent > 0 ? round($thisYear['taken'] / $ent * 100) : 0 }}%</strong></div>
            </div>
            @if($byType->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @foreach($byType as $type => $d)<span class="le-chip">{{ $type }}: {{ $n($d) }}d</span>@endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- REQUESTS --}}
    <div class="col-xl-8">
        <div class="le-card mb-3">
            <h2>Leave history</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.82rem">
                    <thead class="table-light"><tr><th>Type</th><th>Dates</th><th class="text-end">Days</th><th style="min-width:170px">Progress</th><th class="text-end">Deducted</th><th></th></tr></thead>
                    <tbody>
                        @forelse($requests as $l)
                            <tr>
                                <td>{{ $l->leave_type }}</td>
                                <td>{{ $l->date_from->format('d M') }} – {{ $l->date_to->format('d M Y') }}</td>
                                <td class="text-end">{{ $l->total_days }}</td>
                                <td>@include('leave.partials.mini-tracker', ['leave' => $l])</td>
                                <td class="text-end">{{ (float) $l->balance_deducted > 0 ? '−' . $n($l->balance_deducted) : '—' }}</td>
                                <td class="text-end"><a href="{{ route('leave.show', $l) }}" class="btn btn-sm btn-outline-secondary">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">No leave requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ADJUST + LEDGER --}}
    <div class="col-xl-4">
        <div class="le-card mb-3">
            <h2><i class="bi bi-sliders"></i> Adjust balance</h2>
            <form method="POST" action="{{ route('leave.manage.adjust', $employee) }}">
                @csrf
                <div class="btn-group btn-group-sm w-100 mb-2 le-mode" role="group">
                    <input type="radio" class="btn-check" name="mode" id="mAdd" value="add" @checked(old('mode', 'add') === 'add')>
                    <label class="btn btn-outline-success" for="mAdd"><i class="bi bi-plus-lg"></i> Add</label>
                    <input type="radio" class="btn-check" name="mode" id="mDeduct" value="deduct" @checked(old('mode') === 'deduct')>
                    <label class="btn btn-outline-danger" for="mDeduct"><i class="bi bi-dash-lg"></i> Deduct</label>
                    <input type="radio" class="btn-check" name="mode" id="mSet" value="set" @checked(old('mode') === 'set')>
                    <label class="btn btn-outline-primary" for="mSet"><i class="bi bi-pencil"></i> Set to</label>
                </div>
                <input type="number" name="days" step="0.5" min="0" class="form-control form-control-sm mb-2" placeholder="Days" value="{{ old('days') }}" required>
                <input type="text" name="reason" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Reason, e.g. carry-forward approved by MD" value="{{ old('reason') }}" required>
                <button class="btn btn-primary btn-sm w-100">Save adjustment</button>
                <small class="text-muted d-block mt-1">Logged below with your name.</small>
            </form>
        </div>

        <div class="le-card">
            <h2><i class="bi bi-journal-text"></i> Balance history</h2>
            <div class="table-responsive" style="max-height:420px; overflow:auto">
                <table class="table table-sm le-ledger mb-0">
                    <tbody>
                        @forelse($ledger as $a)
                            <tr>
                                <td>
                                    <span class="le-chip">{{ $a->type_label }}</span>
                                    <div class="text-muted mt-1" style="font-size:.74rem">{{ $a->reason }}</div>
                                    <div class="text-muted" style="font-size:.7rem">{{ $a->created_at->format('d M Y, H:i') }}{{ $a->user ? ' · ' . $a->user->name : '' }}</div>
                                </td>
                                <td class="text-end text-nowrap">
                                    <span class="{{ $a->days >= 0 ? 'le-plus' : 'le-minus' }}">{{ $a->days >= 0 ? '+' : '−' }}{{ $n(abs($a->days)) }}</span>
                                    <div class="text-muted" style="font-size:.72rem">→ {{ $n($a->balance_after) }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-muted">No balance changes recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
