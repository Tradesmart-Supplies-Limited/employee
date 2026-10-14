@extends('layouts.portal')
@section('title', 'My Leave')

@section('content')

@include('leave.partials.tracker-styles')
@php
    $n   = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $pct = $summary['entitlement'] > 0 ? max(0, min(100, $summary['balance'] / $summary['entitlement'] * 100)) : 0;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1"><i class="bi bi-calendar-check me-1"></i> My Leave</h4>
        <small class="text-muted">
            Your leave balance and where each application is.
            @if($employee->supervisor_name) Supervisor: <strong>{{ $employee->supervisor_name }}</strong>@endif
        </small>
    </div>
    <a href="{{ route('portal.leave.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-calendar-plus"></i> Apply for Leave
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3"><div class="card shadow-sm border-0 h-100"><div class="card-body d-flex align-items-center gap-3">
        <div class="lt-balance-ring" style="--pct: {{ round($pct) }}">
            <div><strong>{{ $n($summary['balance']) }}</strong><small>days left</small></div>
        </div>
        <div class="small">
            <div class="fw-semibold">Leave balance</div>
            <div class="text-muted">of {{ $n($summary['entitlement']) }} days / year</div>
        </div>
    </div></div></div>
    <div class="col-6 col-xl-3"><div class="card shadow-sm border-0 h-100"><div class="card-body">
        <small class="text-muted">Approved days ({{ now()->year }})</small>
        <h3 class="mb-0">{{ $n($summary['approved_days']) }}</h3>
    </div></div></div>
    <div class="col-6 col-xl-3"><div class="card shadow-sm border-0 h-100"><div class="card-body">
        <small class="text-muted">In progress</small>
        <h3 class="mb-0">{{ $summary['pending'] }}</h3>
        @if($summary['pending_days'] > 0)<small class="lt-pending">{{ $n($summary['pending_days']) }} day(s) on hold</small>@endif
    </div></div></div>
    <div class="col-6 col-xl-3"><div class="card shadow-sm border-0 h-100"><div class="card-body">
        <small class="text-muted">Rejected ({{ now()->year }})</small>
        <h3 class="mb-0">{{ $summary['rejected'] }}</h3>
    </div></div></div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="status" class="form-select form-select-sm" style="max-width:180px" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(['Pending' => 'In progress', 'Approved' => 'Approved', 'Rejected' => 'Rejected'] as $s => $label)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Type</th><th>Dates</th><th>Days</th><th>Applied</th><th style="min-width:180px">Progress</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($leaves as $l)
                    <tr>
                        <td>{{ $l->leave_type }}@if($l->leave_type === 'Other' && $l->other_leave_type) <small class="text-muted">({{ $l->other_leave_type }})</small>@endif</td>
                        <td>{{ $l->date_from->format('d M') }} – {{ $l->date_to->format('d M Y') }}</td>
                        <td>{{ $l->total_days }}</td>
                        <td>{{ $l->created_at->format('d M Y') }}</td>
                        <td>@include('leave.partials.mini-tracker', ['leave' => $l])</td>
                        <td class="text-end"><a href="{{ route('portal.leave.show', $l) }}" class="btn btn-sm btn-outline-secondary">Track</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No leave requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaves->hasPages())<div class="card-footer bg-transparent">{{ $leaves->links() }}</div>@endif
</div>

@endsection
