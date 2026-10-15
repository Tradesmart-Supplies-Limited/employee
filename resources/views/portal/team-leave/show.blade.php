@extends('layouts.portal')
@section('title', 'Review Leave')

@section('content')

@php
    $n   = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $emp = $leave->employee;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1">{{ $leave->employee_name }} · {{ $leave->leave_type }} Leave</h4>
        <small class="text-muted">#LV-{{ str_pad($leave->id, 5, '0', STR_PAD_LEFT) }} · submitted {{ $leave->created_at->format('d M Y, H:i') }}</small>
    </div>
    <a href="{{ route('portal.team-leave.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-4">
        @include('leave.partials.tracker', ['leave' => $leave])
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-transparent"><strong>Request</strong></div>
            <div class="card-body"><div class="row g-3">
                <div class="col-sm-4"><small class="text-muted d-block">From</small>{{ $leave->date_from->format('D d M Y') }}</div>
                <div class="col-sm-4"><small class="text-muted d-block">To</small>{{ $leave->date_to->format('D d M Y') }}</div>
                <div class="col-sm-4"><small class="text-muted d-block">Days</small><strong>{{ $leave->total_days }}</strong></div>
                <div class="col-sm-4"><small class="text-muted d-block">Back on</small>{{ optional($leave->return_date)->format('D d M Y') ?? '—' }}</div>
                <div class="col-sm-4"><small class="text-muted d-block">Position</small>{{ $leave->position ?? '—' }}</div>
                @if($emp)
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Balance</small>
                        {{ $n($emp->leave_balance) }} d
                        @if($leave->isDeductible() && ! $leave->isFinal())
                            <span class="small {{ $emp->leave_balance - $leave->total_days < 0 ? 'text-danger' : 'text-muted' }}">→ {{ $n($emp->leave_balance - $leave->total_days) }}</span>
                        @endif
                    </div>
                @endif
                <div class="col-12"><small class="text-muted d-block">Reason</small>{!! nl2br(e($leave->reason ?: '—')) !!}</div>
                @if($leave->late_application_reason)
                    <div class="col-12"><small class="text-danger d-block">Late application reason</small>{!! nl2br(e($leave->late_application_reason)) !!}</div>
                @endif
                @if($leave->supporting_document)
                    <div class="col-12">
                        <a href="{{ asset('storage/' . $leave->supporting_document) }}" target="_blank"><i class="bi bi-paperclip"></i> Supporting document</a>
                    </div>
                @endif
            </div></div>
        </div>
    </div>

    <div class="col-lg-5">
        @if($leave->awaitingSupervisor())
            <div class="card shadow-sm border-0" style="border-top:3px solid var(--brand, #185FA5) !important;">
                <div class="card-header bg-transparent"><strong>Your recommendation</strong></div>
                <form method="POST" action="{{ route('portal.team-leave.decide', $leave) }}" class="card-body">
                    @csrf
                    <div class="d-grid gap-2 mb-3">
                        <input type="radio" class="btn-check" name="supervisor_decision" id="decApprove" value="Approved" @checked(old('supervisor_decision') === 'Approved') required>
                        <label class="btn btn-outline-success text-start" for="decApprove">
                            <i class="bi bi-hand-thumbs-up me-1"></i> Recommend approval
                        </label>
                        <input type="radio" class="btn-check" name="supervisor_decision" id="decReject" value="Rejected" @checked(old('supervisor_decision') === 'Rejected')>
                        <label class="btn btn-outline-danger text-start" for="decReject">
                            <i class="bi bi-hand-thumbs-down me-1"></i> Recommend rejection
                        </label>
                    </div>
                    <label class="form-label small text-muted">Comments <span id="reqHint">(optional)</span></label>
                    <textarea name="supervisor_comments" id="supComments" rows="3" class="form-control mb-3">{{ old('supervisor_comments') }}</textarea>
                    <button class="btn btn-primary w-100"><i class="bi bi-send-check me-1"></i> Submit to HR</button>
                    <small class="text-muted d-block mt-2">HR makes the final decision. {{ $leave->employee_name }} and HR will be emailed.</small>
                </form>
            </div>

            @push('scripts')
            <script>
            document.querySelectorAll('input[name="supervisor_decision"]').forEach(r => r.addEventListener('change', () => {
                const reject = document.getElementById('decReject').checked;
                document.getElementById('supComments').required = reject;
                document.getElementById('reqHint').textContent = reject ? '(required: why not?)' : '(optional)';
            }));
            </script>
            @endpush
        @else
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if($leave->supervisor_decision)
                        <div class="fw-semibold {{ $leave->supervisor_decision === 'Approved' ? 'text-success' : 'text-danger' }}">
                            You {{ $leave->supervisor_decision === 'Approved' ? 'recommended approval' : 'recommended rejection' }}
                        </div>
                        <small class="text-muted">{{ optional($leave->supervisor_acted_at)->format('d M Y, H:i') }}</small>
                        @if($leave->supervisor_comments)
                            <div class="small mt-2 p-2 bg-light rounded">{!! nl2br(e($leave->supervisor_comments)) !!}</div>
                        @endif
                    @else
                        <div class="text-muted">HR decided this request directly.</div>
                    @endif
                    <hr>
                    <small class="text-muted d-block">HR final decision</small>
                    @if($leave->isFinal())
                        <strong class="{{ $leave->status === 'Approved' ? 'text-success' : 'text-danger' }}">{{ $leave->status }}</strong> by {{ $leave->hr_name }}
                    @else
                        <span class="text-warning"><i class="bi bi-hourglass-split"></i> Waiting for HR</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
