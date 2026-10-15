@extends('layouts.app')
@section('title', 'Attendance register')

@section('content')
@php
    use App\Models\AttendanceRecord as AR;
    use App\Services\AttendanceService as Att;
    $rows = $day['rows']->sortBy(fn ($r) => $r->employee->first_name . ' ' . $r->employee->last_name);
@endphp

@include('dashboard.attendance.partials.nav', [
    'title' => 'Daily register · ' . $date->format('D d M Y'),
    'subtitle' => 'Type or correct times by hand: for people without a badge, a missed scan, or a day the tablet was off. Leave Status on <em>Auto</em> to work out on time / late / half day from the times.',
])

<style>
.rg-table td { padding-top: .45rem; padding-bottom: .45rem; }
.rg-table input[type=time] { width: 112px; }
.rg-table .form-select { width: 128px; }
.rg-row.dirty { background: #fffbeb; }
.rg-save { position: sticky; bottom: 0; z-index: 5; background: var(--admin-surface, #fff); border-top: 1px solid var(--admin-border, #e2e8f0); padding: .8rem 1.1rem; display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; border-radius: 0 0 14px 14px; box-shadow: 0 -6px 16px rgba(15,23,42,.06); }
</style>

<form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ Att::today()->toDateString() }}" class="form-control form-control-sm" style="width:160px" onchange="this.form.submit()">
    <select name="department" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
        @if(auth()->user()?->hasFullAccess())<option value="">All departments</option>@endif
        @foreach($departments as $d)<option @selected($department === $d)>{{ $d }}</option>@endforeach
    </select>
    @if($day['holiday'])<span class="badge rounded-pill" style="background:#e0f2fe;color:#075985"><i class="bi bi-flag"></i> {{ $day['holiday'] }}</span>
    @elseif(! $day['working'])<span class="badge rounded-pill bg-light text-secondary border">Not a working day</span>@endif
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-success" id="fillHours" title="Only rows with no times and no status"><i class="bi bi-magic"></i> Fill blanks: {{ $settings->work_start }}–{{ $settings->work_end }}</button>
        @unless($isToday)<button type="button" class="btn btn-sm btn-outline-danger" id="markAbsent"><i class="bi bi-x-circle"></i> Blanks = Absent</button>@endunless
    </div>
</form>

<form method="POST" action="{{ route('attendance.register.save') }}" class="at-card" id="regForm">
    @csrf
    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
    <input type="hidden" name="department" value="{{ $department }}">
    <div class="table-responsive">
        <table class="table at-table rg-table align-middle mb-0">
            <thead class="table-light"><tr><th>Employee</th><th>Now</th><th>Time in</th><th>Time out</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    @php $rec = $r->record; $id = $r->employee->id; @endphp
                    <tr class="rg-row" data-blank="{{ ! $rec ? 1 : 0 }}">
                        <td>
                            <div class="fw-semibold">{{ $r->employee->full_name }}</div>
                            <div class="small at-muted">{{ $r->employee->employee_id }}{{ $r->employee->department ? ' · ' . $r->employee->department : '' }}</div>
                        </td>
                        <td>
                            @include('dashboard.attendance.partials.chip', ['status' => $r->status])
                            @if($rec && ($rec->source_in || $rec->source_out))<div class="small at-muted mt-1"><i class="bi {{ AR::SOURCES[$rec->source_in ?? $rec->source_out]['icon'] ?? '' }}"></i> {{ AR::SOURCES[$rec->source_in ?? $rec->source_out]['label'] ?? '' }}{{ $rec->editor ? ' · ' . $rec->editor->name : '' }}</div>@endif
                        </td>
                        <td>
                            <input type="time" name="entries[{{ $id }}][in]" value="{{ $rec?->in_time }}" class="form-control form-control-sm" data-role="in">
                            <input type="hidden" name="entries[{{ $id }}][orig_in]" value="{{ $rec?->in_time }}">
                        </td>
                        <td>
                            <input type="time" name="entries[{{ $id }}][out]" value="{{ $rec?->out_time }}" class="form-control form-control-sm" data-role="out">
                            <input type="hidden" name="entries[{{ $id }}][orig_out]" value="{{ $rec?->out_time }}">
                            @if($rec?->missing_out)<div class="small text-danger">never scanned out</div>@endif
                        </td>
                        <td>
                            <select name="entries[{{ $id }}][status]" class="form-select form-select-sm" data-role="status">
                                <option value="">Auto</option>
                                @foreach(AR::STATUSES as $k => $m)<option value="{{ $k }}">{{ $m['label'] }}</option>@endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" name="entries[{{ $id }}][notes]" value="{{ $rec?->notes }}" maxlength="500" class="form-control form-control-sm" placeholder="{{ $r->note && ! $rec ? $r->note : 'Optional' }}" style="min-width:150px">
                            <input type="hidden" name="entries[{{ $id }}][orig_notes]" value="{{ $rec?->notes }}">
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center at-muted py-5">No active employees{{ $department ? ' in ' . $department : '' }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="rg-save">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
        <span class="small at-muted" id="dirtyTxt">Only the rows you change are saved. Clear a row completely to undo a manual entry.</span>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('regForm');
    const mark = tr => { tr.classList.add('dirty'); const n = form.querySelectorAll('tr.dirty').length; document.getElementById('dirtyTxt').textContent = n + ' row(s) changed: remember to save.'; };
    form.addEventListener('input', e => { const tr = e.target.closest('tr'); if (tr) mark(tr); });
    form.addEventListener('change', e => { const tr = e.target.closest('tr'); if (tr) mark(tr); });
    const blanks = () => [...form.querySelectorAll('tr.rg-row')].filter(tr => !tr.querySelector('[data-role=in]').value && !tr.querySelector('[data-role=out]').value && !tr.querySelector('[data-role=status]').value && tr.dataset.blank === '1');
    document.getElementById('fillHours').addEventListener('click', () => {
        const b = blanks(); b.forEach(tr => { tr.querySelector('[data-role=in]').value = @json($settings->work_start); tr.querySelector('[data-role=out]').value = @json($settings->work_end); mark(tr); });
        if (!b.length) document.getElementById('dirtyTxt').textContent = 'No empty rows to fill.';
    });
    const ab = document.getElementById('markAbsent');
    if (ab) ab.addEventListener('click', () => { blanks().forEach(tr => { tr.querySelector('[data-role=status]').value = 'absent'; mark(tr); }); });
    window.addEventListener('beforeunload', e => { if (form.querySelector('tr.dirty') && !form.dataset.saving) { e.preventDefault(); e.returnValue = ''; } });
    form.addEventListener('submit', () => { form.dataset.saving = 1; });
})();
</script>
@endpush
