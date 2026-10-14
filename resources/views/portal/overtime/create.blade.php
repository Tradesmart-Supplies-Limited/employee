@extends('layouts.portal')
@section('title', 'Request Overtime')

@section('content')

<div class="mb-4">
    <h4 class="mb-1"><i class="bi bi-alarm me-1"></i> Request Overtime</h4>
    <small class="text-muted">Claim overtime you have already worked (within the last 60 days).</small>
</div>

<form method="POST" action="{{ route('portal.overtime.store') }}">
    @csrf

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body"><div class="row g-3">

            <div class="col-md-3">
                <label class="form-label">Date worked <span class="text-danger">*</span></label>
                <input type="date" name="work_date" id="workDate" class="form-control"
                       max="{{ now()->toDateString() }}" min="{{ now()->subDays(60)->toDateString() }}"
                       value="{{ old('work_date') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Day type <span class="text-danger">*</span></label>
                <select name="day_type" id="dayType" class="form-select" required>
                    @foreach($dayTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('day_type', 'weekday') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start time <span class="text-danger">*</span></label>
                <input type="time" name="start_time" id="startTime" class="form-control" value="{{ old('start_time') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End time <span class="text-danger">*</span></label>
                <input type="time" name="end_time" id="endTime" class="form-control" value="{{ old('end_time') }}" required>
            </div>

            <div class="col-12">
                <div class="alert alert-light border py-2 mb-0" id="hoursInfo">Enter start and end time to see the total hours.</div>
            </div>

            <div class="col-12">
                <label class="form-label">Reason / work done <span class="text-danger">*</span></label>
                <textarea name="reason" rows="3" class="form-control" required>{{ old('reason') }}</textarea>
            </div>

        </div></div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-send"></i> Submit for approval</button>
        <a href="{{ route('portal.overtime.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const date = document.getElementById('workDate');
    const type = document.getElementById('dayType');
    const s = document.getElementById('startTime');
    const e = document.getElementById('endTime');
    const info = document.getElementById('hoursInfo');

    function hours() {
        if (!s.value || !e.value) return;
        const [sh, sm] = s.value.split(':').map(Number);
        const [eh, em] = e.value.split(':').map(Number);
        let mins = (eh * 60 + em) - (sh * 60 + sm);
        if (mins <= 0) mins += 24 * 60;          // crosses midnight
        info.textContent = `${(mins / 60).toFixed(2)} hour(s)` + (eh * 60 + em <= sh * 60 + sm ? ' (finishes after midnight)' : '');
    }
    [s, e].forEach(el => el.addEventListener('input', hours));
    hours();

    // Suggest the day type from the date (public holidays must be chosen manually)
    date.addEventListener('change', () => {
        if (!date.value) return;
        const d = new Date(date.value + 'T00:00:00').getDay();
        if (type.value !== 'public_holiday') type.value = d === 0 ? 'sunday' : d === 6 ? 'saturday' : 'weekday';
    });
});
</script>
@endpush

@endsection
