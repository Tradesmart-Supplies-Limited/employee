@extends('layouts.portal')
@section('title', 'Apply for Leave')

@section('content')

<div class="mb-4">
    <h4 class="mb-1"><i class="bi bi-calendar-plus me-1"></i> Apply for Leave</h4>
    <small class="text-muted">Your details are filled in automatically from your employee record.</small>
</div>

<form method="POST" action="{{ route('portal.leave.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            @php $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.'); @endphp
            <div class="row g-3 mb-2">
                <div class="col-md-3"><small class="text-muted d-block">Name</small>{{ $employee->full_name }}</div>
                <div class="col-md-3"><small class="text-muted d-block">Employee No.</small>{{ $employee->employee_id }}</div>
                <div class="col-md-3"><small class="text-muted d-block">Position</small>{{ $employee->position ?? '—' }}</div>
                <div class="col-md-3"><small class="text-muted d-block">Reviewed by</small>{{ $employee->supervisor_name ?? 'HR / Admin' }}</div>
            </div>
            <div class="d-flex flex-wrap gap-3 align-items-center border rounded p-2 mt-2 small" style="background:#f8fafc">
                <span><i class="bi bi-wallet2 text-success"></i> Balance: <strong>{{ $n($employee->leave_balance) }}</strong> day(s)</span>
                @if($pendingDays > 0)
                    <span style="color:#b45309"><i class="bi bi-hourglass-split"></i> {{ $n($pendingDays) }} day(s) in pending requests</span>
                @endif
                <span class="text-muted">Deducted on approval for: {{ implode(', ', $deductibleTypes) }}</span>
            </div>
            <div class="small text-muted mt-2">
                <i class="bi bi-diagram-3"></i> Your request goes to your supervisor for a recommendation, then to HR for the final decision. You'll get an email at each step.
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body"><div class="row g-3">

            <div class="col-md-4">
                <label class="form-label">Leave type <span class="text-danger">*</span></label>
                <select name="leave_type" id="leaveType" class="form-select" required>
                    <option value="">Select…</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}" @selected(old('leave_type') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4" id="otherWrap" style="display:none">
                <label class="form-label">Specify other type</label>
                <input type="text" name="other_leave_type" class="form-control" value="{{ old('other_leave_type') }}">
            </div>

            <div class="col-12"></div>

            <div class="col-12">
                @include('leave.partials.day-picker', [
                    'balance'    => (float) $employee->leave_balance - $pendingDays,
                    'typeSelect' => 'leaveType',
                ])
            </div>

            <div class="col-12">
                <label class="form-label">Reason</label>
                <textarea name="reason" rows="3" class="form-control">{{ old('reason') }}</textarea>
            </div>

            <div class="col-12" id="lateWrap" style="display:none">
                <label class="form-label">Why is this application late? <span class="text-danger">*</span></label>
                <textarea name="late_application_reason" rows="2" class="form-control">{{ old('late_application_reason') }}</textarea>
                <small class="text-muted">Required when the leave starts before today.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">Supporting document</label>
                <input type="file" name="supporting_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <small class="text-muted">e.g. medical certificate. PDF, image or Word, up to 5 MB.</small>
            </div>

        </div></div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-send"></i> Submit request</button>
        <a href="{{ route('portal.leave.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('leaveType');
    const other = document.getElementById('otherWrap');
    const from = document.getElementById('dateFrom');
    const late = document.getElementById('lateWrap');

    function refresh() {
        other.style.display = type.value === 'Other' ? '' : 'none';
        if (from.value) {
            const today = new Date(); today.setHours(0, 0, 0, 0);
            late.style.display = new Date(from.value + 'T00:00:00') < today ? '' : 'none';
        }
    }
    [type, from].forEach(el => el.addEventListener('change', refresh));
    refresh();
});
</script>
@endpush

@endsection
