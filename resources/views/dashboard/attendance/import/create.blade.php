@extends('layouts.app')
@section('title', 'Import timesheets')

@section('content')
@include('dashboard.attendance.partials.nav', ['title' => 'Import timesheets', 'subtitle' => 'For sites or days recorded on paper or in Excel: download the timesheet, fill in the times, upload it back.'])

<style>
.ti-step { display: flex; gap: .7rem; align-items: center; margin-bottom: .9rem; }
.ti-step .n { width: 30px; height: 30px; border-radius: 50%; background: #185FA5; color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.ti-drop { border: 2px dashed var(--admin-border, #cbd5e1); border-radius: 14px; padding: 2rem 1rem; text-align: center; cursor: pointer; display: block; transition: all .15s; }
.ti-drop.over { border-color: #185FA5; background: rgba(24,95,165,.05); }
.ti-drop i { font-size: 2.4rem; color: #185FA5; }
.ti-cols span { display: inline-block; font-size: .72rem; font-weight: 700; padding: .15rem .5rem; border-radius: 6px; background: var(--admin-surface-soft, #f1f5f9); margin: 0 .2rem .3rem 0; }
.ti-cols span.y { background: #fef9c3; }
</style>

<div class="row g-3">
    <div class="col-lg-6">
        <form method="GET" action="{{ route('attendance.import.template') }}" class="at-card p-3 h-100" data-no-loader>
            <div class="ti-step"><span class="n">1</span><div><div class="fw-bold">Download the timesheet</div><div class="small at-muted">One row per employee per working day, ready to fill in.</div></div></div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label small fw-semibold">From</label><input type="date" name="from" value="{{ old('from', $from->toDateString()) }}" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small fw-semibold">To</label><input type="date" name="to" value="{{ old('to', $to->toDateString()) }}" class="form-control form-control-sm" required></div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Department</label>
                    <select name="department" class="form-select form-select-sm">
                        <option value="">Everyone</option>
                        @foreach($departments as $d)<option>{{ $d }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="format" value="xlsx" id="fx" @checked($excel) @disabled(! $excel)>
                        <label class="btn btn-sm btn-outline-success" for="fx"><i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)</label>
                        <input type="radio" class="btn-check" name="format" value="csv" id="fc" @checked(! $excel)>
                        <label class="btn btn-sm btn-outline-secondary" for="fc"><i class="bi bi-filetype-csv"></i> CSV</label>
                    </div>
                </div>
            </div>
            <div class="small at-muted mt-3 mb-1">Columns (fill the yellow ones):</div>
            <div class="ti-cols"><span>Employee ID</span><span>Employee Name</span><span>Department</span><span>Date</span><span>Day</span><span class="y">Time In</span><span class="y">Time Out</span><span class="y">Status</span><span class="y">Notes</span></div>
            <ul class="small at-muted mt-2 mb-3 ps-3">
                <li>Times like <strong>07:55</strong> or <strong>8:05 AM</strong>. Leave Status blank and on time / late / half day is worked out.</li>
                <li>No times? Put a Status: Absent, On leave, Holiday, Day off.</li>
                <li>Rows left empty are skipped. Days already recorded are pre-filled so you can correct them.</li>
                <li>Your own sheet works too, as long as it has <em>Employee ID</em> and <em>Date</em> plus <em>Time In / Time Out</em> or <em>Status</em>.</li>
            </ul>
            <button class="btn btn-success w-100"><i class="bi bi-download"></i> Download timesheet</button>
        </form>
    </div>

    <div class="col-lg-6">
        <form method="POST" action="{{ route('attendance.import.preview') }}" enctype="multipart/form-data" class="at-card p-3 h-100" id="upForm">
            @csrf
            <div class="ti-step"><span class="n">2</span><div><div class="fw-bold">Upload the filled timesheet</div><div class="small at-muted">You'll see a preview first. Nothing is saved until you confirm.</div></div></div>
            <label class="ti-drop" id="drop" for="file">
                <i class="bi bi-cloud-arrow-up"></i>
                <div class="fw-semibold mt-1" id="dropTxt">Drop the file here or click to choose</div>
                <div class="small at-muted">.xlsx, .xls or .csv · up to 10 MB · max 5,000 rows</div>
                <input type="file" name="file" id="file" class="d-none" accept=".csv,.txt,.xlsx,.xls,.ods" required>
            </label>
            @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            <button class="btn btn-primary w-100 mt-3" id="upBtn"><i class="bi bi-search"></i> Check the file</button>
            <div class="alert alert-light border small mt-3 mb-0">
                <i class="bi bi-shield-check text-success"></i> Kiosk scans are safe: on the preview you choose whether the file <strong>overwrites</strong> existing times or only <strong>fills the blanks</strong>. A blank cell never wipes a time that's already recorded.
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const drop = document.getElementById('drop'), input = document.getElementById('file'), txt = document.getElementById('dropTxt');
    const show = () => { if (input.files.length) txt.innerHTML = '<i class="bi bi-check-circle text-success"></i> ' + input.files[0].name; };
    input.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.add('over'); }));
    ['dragleave', 'drop'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.remove('over'); }));
    drop.addEventListener('drop', ev => { if (ev.dataTransfer.files.length) { input.files = ev.dataTransfer.files; show(); } });
    document.getElementById('upForm').addEventListener('submit', () => { const b = document.getElementById('upBtn'); b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Reading the file…'; });
})();
</script>
@endpush
