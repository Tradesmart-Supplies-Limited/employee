@extends('layouts.app')

@section('content')

@php $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.'); @endphp

<div class="mb-3">
    <a href="{{ route('leave.manage.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Leave reports</a>
    <h1 class="h4 fw-bold mb-0 mt-1"><i class="bi bi-journal-plus"></i> Record leave for an employee</h1>
    <div class="small text-muted">For employees without a portal login, leave agreed verbally or on paper, or backdated records.</div>
</div>

<form method="POST" action="{{ route('leave.manage.record.store') }}">
    @csrf

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" id="empSelect" class="form-select" required>
                        <option value="">Select employee…</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" data-balance="{{ (float) $e->leave_balance }}" data-sup="{{ $e->supervisor_name }}"
                                    @selected((int) old('employee_id', $selected) === $e->id)>
                                {{ $e->full_name }} — {{ $e->employee_id }} (bal {{ $n($e->leave_balance) }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted" id="empInfo"></small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Leave type <span class="text-danger">*</span></label>
                    <select name="leave_type" id="leaveType" class="form-select" required>
                        @foreach($types as $t)<option @selected(old('leave_type', 'Annual') === $t)>{{ $t }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3" id="otherWrap" style="display:none">
                    <label class="form-label">Specify</label>
                    <input type="text" name="other_leave_type" class="form-control" value="{{ old('other_leave_type') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            @include('leave.partials.day-picker', ['balance' => 0.0, 'typeSelect' => 'leaveType'])
            <div class="mt-3">
                <label class="form-label">Reason / notes</label>
                <textarea name="reason" rows="2" class="form-control">{{ old('reason') }}</textarea>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold">What should happen?</label>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="outcome" id="oApprove" value="approve" @checked(old('outcome', 'approve') === 'approve')>
                <label class="form-check-label" for="oApprove">
                    <strong>Approve now</strong> <span class="text-muted">— final HR approval straight away, the balance is deducted and the employee and supervisor get the approval email.</span>
                </label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="radio" name="outcome" id="oFlow" value="workflow" @checked(old('outcome') === 'workflow')>
                <label class="form-check-label" for="oFlow">
                    <strong>Send through approval</strong> <span class="text-muted">— goes to the supervisor, then HR, exactly like a portal request.</span>
                </label>
            </div>
            <div id="approveExtras">
                <input type="text" name="hr_comments" class="form-control form-control-sm mb-2" placeholder="Note to the employee (optional)" value="{{ old('hr_comments') }}">
                <div class="form-check small">
                    <input class="form-check-input" type="checkbox" name="override_balance" value="1" id="ovr" @checked(old('override_balance'))>
                    <label class="form-check-label text-danger" for="ovr">Approve even if it takes the balance below zero</label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Record leave</button>
        <a href="{{ route('leave.manage.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const emp = document.getElementById('empSelect'), info = document.getElementById('empInfo');
    const type = document.getElementById('leaveType'), other = document.getElementById('otherWrap');
    const extras = document.getElementById('approveExtras');
    const bal = document.getElementById('dpBalance'), balSub = document.getElementById('dpBalanceSub');

    function syncEmp() {
        const o = emp.selectedOptions[0];
        if (!o || !o.value) { info.textContent = ''; return; }
        info.textContent = `Balance ${o.dataset.balance} day(s) · reports to ${o.dataset.sup || 'nobody (HR reviews)'}`;
        // re-run the day picker maths against this employee's balance
        window.__dpBalance = parseFloat(o.dataset.balance);
        document.getElementById('dateTo').dispatchEvent(new Event('change'));
    }
    function syncOutcome() { extras.style.display = document.getElementById('oApprove').checked ? '' : 'none'; }
    emp.addEventListener('change', syncEmp);
    type.addEventListener('change', () => other.style.display = type.value === 'Other' ? '' : 'none');
    document.querySelectorAll('input[name="outcome"]').forEach(r => r.addEventListener('change', syncOutcome));
    other.style.display = type.value === 'Other' ? '' : 'none';
    syncEmp(); syncOutcome();
});
</script>
@endpush

@endsection
