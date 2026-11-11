@extends('layouts.management')
@section('title', 'My Attendance')

@section('content')
@php
    $M = \App\Models\ManagementAttendance::class;
    $editDay = session('editDay') ?? (old('date') && $errors->any() ? old('date') : null);
@endphp
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-clock-history"></i> My Attendance</h1>
        <div class="small text-muted">Your own clock-in and clock-out record, kept separately from staff attendance reports. If you forget to clock out, you're clocked out automatically at midnight.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-primary" data-day-edit data-date="" data-in="" data-out="" data-note=""><i class="bi bi-plus-lg"></i> Add a day</button>
        <div class="btn-group">
            <a class="btn btn-sm btn-primary" href="{{ route('mgmt.attendance.export', ['month' => $month->format('Y-m')]) }}"><i class="bi bi-download"></i> Export {{ $month->format('M Y') }}</a>
            <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More export options"></button>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:260px">
                <form method="GET" action="{{ route('mgmt.attendance.export') }}">
                    <div class="small fw-semibold mb-2">Export a date range</div>
                    <label class="form-label small mb-1" for="exFrom">From</label>
                    <input type="date" id="exFrom" name="from" class="form-control form-control-sm mb-2" required max="{{ $today->toDateString() }}" value="{{ $month->toDateString() }}">
                    <label class="form-label small mb-1" for="exTo">To</label>
                    <input type="date" id="exTo" name="to" class="form-control form-control-sm mb-2" required max="{{ $today->toDateString() }}" value="{{ min($month->copy()->endOfMonth()->toDateString(), $today->toDateString()) }}">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-download"></i> Download (Excel / CSV)</button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($errors->any() && ! $errors->has('time'))
    <div class="alert alert-danger small py-2"><i class="bi bi-exclamation-circle me-1"></i> {{ $errors->first() }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        @include('management.partials.clock-card', ['todayRec' => $todayRec])
        <div class="small text-muted mt-2 px-1">
            <span class="ma-tag ma-tag--manual">entered</span> a time you typed in ·
            <span class="ma-tag ma-tag--auto">auto</span> clocked out at midnight ·
            <i class="bi bi-pencil-square"></i> edited later
        </div>
    </div>
    <div class="col-lg-7">
        <div class="hr-card">
            <div class="hr-card-h">
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-sm btn-light border" href="{{ route('mgmt.attendance', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" aria-label="Previous month"><i class="bi bi-chevron-left"></i></a>
                    <h6 class="mb-0">{{ $month->format('F Y') }}</h6>
                    @if($canNext)<a class="btn btn-sm btn-light border" href="{{ route('mgmt.attendance', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" aria-label="Next month"><i class="bi bi-chevron-right"></i></a>@endif
                </div>
                <div class="small text-muted">{{ $present }} day(s) · {{ $M::hm($total) }}</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 ma-table">
                    <thead><tr><th class="ps-3">Day</th><th>In</th><th>Out</th><th>Hours</th><th class="pe-3 text-end"><span class="visually-hidden">Edit</span></th></tr></thead>
                    <tbody>
                    @forelse($days as $d)
                        @php $r = $rows[$d->format('Y-m-d')] ?? null; $isToday = $d->isSameDay($today); @endphp
                        <tr class="{{ $d->isSunday() && ! $r ? 'text-muted' : '' }}">
                            <td class="ps-3 text-nowrap">
                                {{ $d->format('D d') }}@if($isToday) <span class="badge text-bg-primary">Today</span>@endif
                                @if($r?->edited_at)<i class="bi bi-pencil-square text-muted ms-1" title="Edited {{ $r->edited_at->timezone(\App\Services\AttendanceService::tz())->format('d M, H:i') }}"></i>@endif
                                @if($r?->note)<div class="small text-muted text-truncate" style="max-width:200px" title="{{ $r->note }}">{{ $r->note }}</div>@endif
                            </td>
                            <td class="text-nowrap">{{ $r?->in_time ?? '—' }}@if($r?->in_source === 'manual')<span class="ma-tag ma-tag--manual">entered</span>@endif</td>
                            <td class="text-nowrap">
                                @if($r?->clock_out){{ $r->out_time }}@if($r->auto_closed)<span class="ma-tag ma-tag--auto" title="Clocked out automatically at midnight">auto</span>@elseif($r->out_source === 'manual')<span class="ma-tag ma-tag--manual">entered</span>@endif
                                @elseif($r?->clock_in && $isToday)<span class="small text-success">At work</span>
                                @else — @endif
                            </td>
                            <td class="fw-semibold text-nowrap">{{ $r?->minutes !== null ? $M::hm($r->minutes) : '—' }}</td>
                            <td class="pe-3 text-end">
                                <button type="button" class="btn btn-sm btn-link p-0" data-day-edit
                                        data-date="{{ $d->format('Y-m-d') }}" data-label="{{ $d->format('l d M Y') }}"
                                        data-in="{{ $r?->in_time }}" data-out="{{ $r?->clock_out && ! $r->auto_closed ? $r->out_time : '' }}"
                                        data-note="{{ $r?->note }}" data-id="{{ $r?->id }}" data-today="{{ $isToday ? 1 : 0 }}"
                                        aria-label="{{ $r ? 'Edit' : 'Add' }} {{ $d->format('l d M') }}">
                                    {{ $r ? 'Edit' : 'Add' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nothing recorded for this month yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Edit / add a day --}}
<div class="modal fade" id="dayModal" tabindex="-1" aria-labelledby="dayModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('mgmt.attendance.save') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="dayModalTitle">Edit day</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="dmDate">Date</label>
                    <input type="date" class="form-control" id="dmDate" name="date" required max="{{ $today->toDateString() }}" value="{{ old('date') }}">
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="dmIn">Started</label>
                        <input type="time" class="form-control @error('clock_in') is-invalid @enderror" id="dmIn" name="clock_in" required value="{{ old('clock_in') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="dmOut">Finished</label>
                        <input type="time" class="form-control @error('clock_out') is-invalid @enderror" id="dmOut" name="clock_out" value="{{ old('clock_out') }}">
                        <div class="form-text" id="dmOutHint">Leave empty if you're still at work today.</div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label small fw-semibold" for="dmNote">Note <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" class="form-control" id="dmNote" name="note" maxlength="255" value="{{ old('note') }}" placeholder="e.g. Board meeting in Lusaka">
                </div>
                @if($errors->any() && ! $errors->has('time'))<div class="text-danger small mt-2">{{ $errors->first() }}</div>@endif
                <div class="small text-muted mt-3"><i class="bi bi-info-circle"></i> Times you enter are marked as entered, so the record stays clear.</div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link text-danger p-0" id="dmDelete" hidden>Remove this day</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
<form method="POST" id="dmDeleteForm" hidden>@csrf @method('DELETE')</form>

<style>
.ma-table th, .ma-table td { min-width: 0 !important; }
.ma-table td:last-child, .ma-table th:last-child { width: 1%; white-space: nowrap; }
.ma-tag { display: inline-block; margin-left: 5px; padding: 0 6px; border-radius: 999px; font-size: .66rem; font-weight: 700; vertical-align: middle; }
.ma-tag--manual { background: rgba(var(--brand-rgb, 24,95,165), .12); color: var(--brand-text, #185FA5); }
.ma-tag--auto { background: rgba(245,158,11,.15); color: #b45309; }
html[data-theme=dark] .ma-tag--auto { color: #fbbf24; }
@media (max-width: 575.98px) {
    /* phones: tags become small coloured dots so the Edit link stays in view */
    .ma-table { font-size: .84rem; }
    .ma-table .ma-tag { font-size: 0; width: 8px; height: 8px; padding: 0; margin-left: 3px; border-radius: 50%; }
    .ma-table .ma-tag--manual { background: var(--brand, #185FA5); }
    .ma-table .ma-tag--auto { background: #f59e0b; }
    .ma-table .badge { display: none; }
    .ma-table td:first-child, .ma-table th:first-child { padding-left: .6rem !important; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('dayModal');
    if (!el || !window.bootstrap) return;
    var modal = bootstrap.Modal.getOrCreateInstance(el);
    var $ = function (id) { return document.getElementById(id); };
    var today = @json($today->toDateString()), delUrl = @json(route('mgmt.attendance.delete', ['day' => '__ID__']));

    function hint() { $('dmOutHint').hidden = $('dmDate').value !== today; $('dmOut').required = $('dmDate').value !== today; }
    $('dmDate').addEventListener('change', hint);

    function open(b) {
        var d = b.dataset;
        $('dmDate').value = d.date || ''; $('dmDate').readOnly = !!d.date;
        $('dmIn').value = d.in || ''; $('dmOut').value = d.out || ''; $('dmNote').value = d.note || '';
        $('dayModalTitle').textContent = d.date ? (d.id ? 'Edit ' : 'Add ') + d.label : 'Add a day';
        $('dmDelete').hidden = !d.id;
        $('dmDelete').dataset.id = d.id || '';
        hint();
        modal.show();
    }
    document.querySelectorAll('[data-day-edit]').forEach(function (b) { b.addEventListener('click', function () { open(b); }); });
    $('dmDelete').addEventListener('click', function () {
        if (!confirm('Remove this day from your attendance?')) return;
        var f = $('dmDeleteForm'); f.action = delUrl.replace('__ID__', this.dataset.id); f.submit();
    });

    // Re-open the form after a validation message, with what was typed.
    @if($editDay)
        $('dmDate').value = @json(old('date', $editDay)); $('dmDate').readOnly = false; $('dayModalTitle').textContent = 'Edit day'; hint(); modal.show();
    @endif
});
</script>
@endsection
