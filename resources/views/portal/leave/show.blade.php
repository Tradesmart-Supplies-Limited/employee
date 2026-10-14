@extends('layouts.portal')
@section('title', 'Leave Request')

@section('content')

@php $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.'); @endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1">{{ $leave->leave_type }} Leave <small class="text-muted fs-6">#LV-{{ str_pad($leave->id, 5, '0', STR_PAD_LEFT) }}</small></h4>
        <small class="text-muted">Submitted {{ $leave->created_at->format('d M Y, H:i') }}</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-{{ $leave->stage_color }} {{ in_array($leave->stage_color, ['warning','info']) ? 'text-dark' : '' }}">
            <i class="bi {{ $leave->stage_icon }} me-1"></i>{{ $leave->stage_label }}
        </span>
        <a href="{{ route('portal.leave.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

{{-- TRACKER --}}
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-4">
        @include('leave.partials.tracker', ['leave' => $leave])
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-transparent"><strong>Request details</strong></div>
            <div class="card-body"><div class="row g-3">
                <div class="col-sm-4"><small class="text-muted d-block">From</small>{{ $leave->date_from->format('d M Y') }}</div>
                <div class="col-sm-4"><small class="text-muted d-block">To</small>{{ $leave->date_to->format('d M Y') }}</div>
                <div class="col-sm-4"><small class="text-muted d-block">Leave days</small>{{ $leave->total_days }}</div>
                <div class="col-12">@include('leave.partials.day-breakdown', ['leave' => $leave])</div>
                <div class="col-sm-4"><small class="text-muted d-block">Return date</small>{{ optional($leave->return_date)->format('d M Y') ?? '—' }}</div>
                @if($leave->other_leave_type)
                    <div class="col-sm-4"><small class="text-muted d-block">Other type</small>{{ $leave->other_leave_type }}</div>
                @endif
                <div class="col-sm-4"><small class="text-muted d-block">Supervisor</small>{{ $leave->assigned_supervisor_name ?? 'HR / Admin' }}</div>
                <div class="col-12"><small class="text-muted d-block">Reason</small>{!! nl2br(e($leave->reason ?: '—')) !!}</div>
                @if($leave->late_application_reason)
                    <div class="col-12"><small class="text-muted d-block">Late application reason</small>{!! nl2br(e($leave->late_application_reason)) !!}</div>
                @endif
                @if($leave->supporting_document)
                    <div class="col-12">
                        <small class="text-muted d-block">Supporting document</small>
                        <a href="{{ asset('storage/' . $leave->supporting_document) }}" target="_blank"><i class="bi bi-paperclip"></i> View attachment</a>
                    </div>
                @endif
            </div></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-transparent"><strong>Decisions</strong></div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <small class="text-muted d-block">Supervisor</small>
                    @if($leave->supervisor_decision)
                        <span class="{{ $leave->supervisor_decision === 'Approved' ? 'text-success' : 'text-danger' }} fw-semibold">
                            <i class="bi {{ $leave->supervisor_decision === 'Approved' ? 'bi-hand-thumbs-up' : 'bi-hand-thumbs-down' }}"></i>
                            {{ $leave->supervisor_decision === 'Approved' ? 'Recommended' : 'Not recommended' }}
                        </span>
                        <div class="small text-muted">{{ $leave->supervisor_name }}</div>
                        @if($leave->supervisor_comments)
                            <div class="small mt-1 p-2 bg-light rounded">{!! nl2br(e($leave->supervisor_comments)) !!}</div>
                        @endif
                    @elseif($leave->isFinal())
                        <span class="text-muted">Skipped</span>
                    @else
                        <span class="text-warning fw-semibold"><i class="bi bi-hourglass-split"></i> Waiting</span>
                        <div class="small text-muted">{{ $leave->assigned_supervisor_name ?? 'HR / Admin' }}</div>
                    @endif
                </li>
                <li class="list-group-item">
                    <small class="text-muted d-block">HR final decision</small>
                    @if($leave->isFinal())
                        <span class="{{ $leave->status === 'Approved' ? 'text-success' : 'text-danger' }} fw-semibold">{{ $leave->status }}</span>
                        <div class="small text-muted">{{ $leave->hr_name }} · {{ optional($leave->hr_acted_at ?? $leave->hr_signature_date)->format('d M Y') }}</div>
                        @if($leave->hr_comments)
                            <div class="small mt-1 p-2 bg-light rounded">{!! nl2br(e($leave->hr_comments)) !!}</div>
                        @endif
                    @else
                        <span class="text-muted"><i class="bi bi-clock"></i> {{ $leave->supervisor_decision ? 'Waiting for HR' : 'After supervisor review' }}</span>
                    @endif
                </li>
                <li class="list-group-item">
                    <small class="text-muted d-block">Leave balance</small>
                    @if($leave->status === 'Approved' && (float) $leave->balance_deducted > 0)
                        {{ $n($leave->days_available) }} → <strong class="text-success">{{ $n($leave->days_balance) }}</strong> days
                        <span class="text-muted small">(−{{ $n($leave->balance_deducted) }})</span>
                    @elseif($leave->status === 'Approved')
                        <span class="text-muted small">Not deducted ({{ $leave->leave_type }} leave)</span>
                    @elseif($leave->employee)
                        Current: <strong>{{ $n($leave->employee->leave_balance) }}</strong> days
                        @if($leave->isDeductible() && ! $leave->isFinal())
                            <span class="text-muted small">→ {{ $n($leave->employee->leave_balance - $leave->total_days) }} if approved</span>
                        @endif
                    @endif
                </li>
            </ul>
        </div>

        @if(! $leave->isFinal())
            <form method="POST" action="{{ route('portal.leave.destroy', $leave) }}"
                  onsubmit="return confirm('Withdraw this leave request?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle"></i> Withdraw request</button>
            </form>
        @endif
    </div>
</div>

@endsection
