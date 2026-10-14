@extends('layouts.portal')
@section('title', 'Dashboard')

@section('content')

@include('leave.partials.tracker-styles')
@php $announcements = $announcements ?? collect(); $readIds = $readIds ?? []; $today = $today ?? null; $shiftToday = $shiftToday ?? null; $shiftTomorrow = $shiftTomorrow ?? null; @endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1">Hello, {{ $employee->first_name }}</h4>
        <small class="text-muted">
            {{ $employee->position ?? 'Employee' }}
            @if($employee->department) &middot; {{ $employee->department }} @endif
            &middot; EIN {{ $employee->employee_id }}
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($today && $today->status)
            @php $tm = \App\Services\AttendanceService::meta($today->status); @endphp
            <a href="{{ route('portal.attendance.index') }}" class="btn btn-sm btn-light border" title="My attendance today">
                <span class="hr-chip" style="--bg:{{ $tm['bg'] }};--c:{{ $tm['color'] }}"><i class="bi {{ $tm['icon'] }}"></i> {{ $tm['label'] }}</span>
                {{ $today->record?->in_time ? 'in ' . $today->record->in_time : 'My QR' }}
            </a>
        @endif
        <a href="{{ route('portal.leave.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-calendar-plus"></i> Apply for Leave
        </a>
        <a href="{{ route('portal.overtime.create') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-alarm"></i> Request Overtime
        </a>
    </div>
</div>

@if($shiftToday)
    @php
        $sc = fn ($p) => $p->kind === 'shift' && $p->expected ? ($p->shift?->color ?? '#185FA5') : ($p->kind === 'backup' ? '#ca8a04' : '#64748b');
        $sl = fn ($p) => $p->expected ? ($p->shift?->name ?? 'Work') . ($p->start ? ' · ' . $p->start . '–' . $p->end : '') : $p->label;
    @endphp
    <a href="{{ route('portal.shifts.index') }}" class="hr-card d-flex align-items-center gap-3 px-3 py-2 mb-3 text-decoration-none" style="color:inherit;border-left:4px solid {{ $sc($shiftToday) }}">
        <i class="bi bi-calendar-week fs-4" style="color:{{ $sc($shiftToday) }}"></i>
        <div class="flex-grow-1 small">
            <div><strong>Today:</strong> {{ $sl($shiftToday) }}@if($shiftToday->expected && $shiftToday->overtime) <span style="color:#b45309">(overtime)</span>@endif</div>
            @if($shiftTomorrow)<div class="text-muted">Tomorrow: {{ $sl($shiftTomorrow) }}</div>@endif
        </div>
        <span class="small">My shifts <i class="bi bi-arrow-right"></i></span>
    </a>
@endif

{{-- STAT CARDS --}}
<div class="row g-3 mb-4">
    @php
        $n   = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
        $pct = $leaveBalance['entitlement'] > 0 ? max(0, min(100, $leaveBalance['balance'] / $leaveBalance['entitlement'] * 100)) : 0;
    @endphp
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="lt-balance-ring" style="--pct: {{ round($pct) }}">
                <div><strong>{{ $n($leaveBalance['balance']) }}</strong><small>days left</small></div>
            </div>
            <div class="small">
                <div class="fw-semibold mb-1">Leave balance</div>
                <div class="text-muted">{{ $n($leaveBalance['entitlement']) }} / year</div>
                <div class="text-muted">{{ $n($leaveBalance['taken']) }} taken in {{ now()->year }}</div>
                @if($leaveBalance['pending'] > 0)
                    <div class="lt-pending">{{ $n($leaveBalance['pending']) }} pending</div>
                @endif
            </div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100"><div class="card-body">
            <small class="text-muted">Pending requests</small>
            <h3 class="mb-0">{{ $pendingLeave + $pendingOvertime }}</h3>
            <small class="text-muted">{{ $pendingLeave }} leave &middot; {{ $pendingOvertime }} overtime</small>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100"><div class="card-body">
            <small class="text-muted">Approved overtime ({{ now()->format('M') }})</small>
            <h3 class="mb-0">{{ rtrim(rtrim(number_format($overtimeThisMonth, 2), '0'), '.') ?: 0 }} <small class="fs-6 text-muted">hrs</small></h3>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100"><div class="card-body">
            <small class="text-muted">Latest payslip</small>
            @if($latestPayslip)
                <h5 class="mb-1">{{ $latestPayslip->pay_period }}</h5>
                <a href="{{ route('portal.payslips.show', $latestPayslip) }}" class="small">View payslip</a>
            @else
                <h5 class="mb-0 text-muted">None yet</h5>
            @endif
        </div></div>
    </div>
</div>

@if($announcements->isNotEmpty())
    <div class="hr-card mb-4">
        <div class="hr-card-h">
            <h6><i class="bi bi-megaphone"></i> Announcements @php $nNew = $announcements->reject(fn ($a) => isset($readIds[$a->id]))->count(); @endphp @if($nNew)<span class="badge rounded-pill bg-primary">{{ $nNew }} new</span>@endif</h6>
            <a href="{{ route('portal.announcements.index') }}" class="small">View all</a>
        </div>
        @foreach($announcements as $a)
            @php [$cl, $ci, $cc] = $a->category_meta; [$pl, $pc, $pbg] = $a->priority_meta; $new = ! isset($readIds[$a->id]); @endphp
            <a href="{{ route('portal.announcements.show', $a) }}" class="an-item {{ $new ? 'unread' : '' }}">
                <span class="an-ic" style="--c:{{ $cc }}"><i class="bi {{ $ci }}"></i></span>
                <div style="min-width:0" class="flex-grow-1">
                    <div class="d-flex gap-2 align-items-center flex-wrap"><span class="an-title">@if($a->pinned)<i class="bi bi-pin-angle-fill text-primary"></i> @endif{{ $a->title }}</span>@if($a->priority !== 'normal')<span class="hr-chip" style="--bg:{{ $pbg }};--c:{{ $pc }}">{{ $pl }}</span>@endif</div>
                    <div class="small text-muted text-truncate">{{ $a->excerpt }}</div>
                </div>
                <span class="small text-muted text-nowrap">{{ ($a->local('publish_at') ?? $a->local('created_at'))->format('d M') }}</span>
            </a>
        @endforeach
    </div>
@endif

@if($teamAwaiting->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #f59e0b !important;">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <i class="bi bi-person-check-fill fs-3 text-warning"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold">{{ $teamAwaiting->count() }} leave request(s) from your team need your review</div>
                <small class="text-muted">{{ $teamAwaiting->pluck('employee_name')->take(3)->implode(', ') }}{{ $teamAwaiting->count() > 3 ? ' and others' : '' }}</small>
            </div>
            <a href="{{ route('portal.team-leave.index') }}" class="btn btn-warning btn-sm">Review now</a>
        </div>
    </div>
@endif

@if($activeLeave)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>Where's my leave?</strong>
                <small class="text-muted ms-1">{{ $activeLeave->leave_type }} · {{ $activeLeave->date_from->format('d M') }} – {{ $activeLeave->date_to->format('d M Y') }} ({{ $activeLeave->total_days }}d)</small>
            </div>
            <a href="{{ route('portal.leave.show', $activeLeave) }}" class="small">Details</a>
        </div>
        <div class="card-body">
            @include('leave.partials.tracker', ['leave' => $activeLeave])
        </div>
    </div>
@endif

@if($contractDaysLeft !== null && $contractDaysLeft <= 60)
    <div class="alert {{ $contractDaysLeft < 0 ? 'alert-danger' : 'alert-warning' }}">
        <i class="bi bi-info-circle me-1"></i>
        @if($contractDaysLeft < 0)
            Your contract ended on {{ $employee->contract_end->format('d M Y') }}. Please contact HR.
        @else
            Your contract ends on {{ $employee->contract_end->format('d M Y') }} ({{ $contractDaysLeft }} day(s) left).
        @endif
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent d-flex justify-content-between">
                <strong>Recent leave</strong>
                <a href="{{ route('portal.leave.index') }}" class="small">View all</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentLeave as $l)
                    <a href="{{ route('portal.leave.show', $l) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>
                            {{ $l->leave_type }}
                            <small class="text-muted d-block">{{ $l->date_from->format('d M') }} &ndash; {{ $l->date_to->format('d M Y') }} ({{ $l->total_days }}d)</small>
                        </span>
                        @include('leave.partials.mini-tracker', ['leave' => $l])
                    </a>
                @empty
                    <div class="list-group-item text-muted">No leave requests yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent d-flex justify-content-between">
                <strong>Recent overtime</strong>
                <a href="{{ route('portal.overtime.index') }}" class="small">View all</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentOvertime as $o)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            {{ $o->work_date->format('d M Y') }}
                            <small class="text-muted d-block">{{ $o->hours + 0 }} hr &middot; {{ $o->day_type_label }}</small>
                        </span>
                        @include('portal.partials.status-badge', ['status' => $o->status])
                    </div>
                @empty
                    <div class="list-group-item text-muted">No overtime requests yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
