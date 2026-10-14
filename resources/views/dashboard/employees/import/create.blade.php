@extends('layouts.app')

@section('content')

<style>
.im-steps { display: flex; gap: .6rem; margin-bottom: 1.2rem; flex-wrap: wrap; }
.im-step { flex: 1 1 180px; display: flex; gap: .7rem; align-items: center; padding: .8rem 1rem; border-radius: 12px; border: 1px solid var(--admin-border, #e2e8f0); background: var(--admin-surface, #fff); font-size: .85rem; }
.im-step .n { width: 30px; height: 30px; border-radius: 50%; background: var(--admin-surface-soft, #f1f5f9); font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.im-step.active { border-color: #185FA5; } .im-step.active .n { background: #185FA5; color: #fff; }
.im-drop { border: 2px dashed var(--admin-border, #cbd5e1); border-radius: 16px; padding: 2.4rem 1rem; text-align: center; background: var(--admin-surface, #fff); cursor: pointer; transition: all .15s; }
.im-drop.over { border-color: #185FA5; background: rgba(24, 95, 165, .05); }
.im-drop i { font-size: 2.4rem; color: #185FA5; }
.im-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: 1rem 1.2rem; }
.im-cols { columns: 2; font-size: .8rem; column-gap: 1.5rem; }
.im-cols div { break-inside: avoid; padding: .15rem 0; }
.im-cols code { font-size: .76rem; }
</style>

<div class="mb-3">
    <a href="{{ route('employees.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Employees</a>
    <h1 class="h4 fw-bold mb-0 mt-1"><i class="bi bi-cloud-upload"></i> Bulk import employees</h1>
    <div class="small text-muted">Add or update many employees at once from Excel or CSV. Nothing is saved until you've reviewed the preview.</div>
</div>

<div class="im-steps">
    <div class="im-step active"><span class="n">1</span><span><strong>Upload</strong><br><small class="text-muted">Excel or CSV file</small></span></div>
    <div class="im-step"><span class="n">2</span><span><strong>Preview</strong><br><small class="text-muted">See new, updated &amp; problem rows</small></span></div>
    <div class="im-step"><span class="n">3</span><span><strong>Confirm</strong><br><small class="text-muted">Save, optionally email portal logins</small></span></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('employees.import.preview') }}" enctype="multipart/form-data" id="importForm">
            @csrf
            <label class="im-drop d-block" id="drop" for="fileInput">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <div class="fw-bold mt-2" id="dropText">Drop your file here, or click to choose</div>
                <div class="small text-muted">.xlsx, .xls or .csv · up to 2,000 employees · 10 MB</div>
                <input type="file" name="file" id="fileInput" accept=".csv,.txt,.xlsx,.xls,.ods" class="d-none" required>
            </label>
            @error('file')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            <button class="btn btn-primary mt-3 w-100" id="goBtn" disabled><i class="bi bi-search"></i> Check file &amp; show preview</button>
        </form>

        <div class="im-card mt-3">
            <h6 class="fw-bold"><i class="bi bi-lightbulb"></i> Tips</h6>
            <ul class="small mb-0 ps-3">
                <li><strong>Row 1 must be column headings.</strong> Headings don't have to match exactly: "Surname", "NRC No.", "Mobile", "Date Joined" and similar are recognised.</li>
                <li>Only <strong>first name</strong> and <strong>last name</strong> are required. Leave any other column blank or remove it.</li>
                <li>Dates can be <code>2026-03-31</code>, <code>31/03/2026</code> or <code>31 Mar 2026</code>.</li>
                <li><strong>Existing employees are updated</strong> when the EIN, NRC or email matches. Blank cells never erase existing data.</li>
                <li>"supervisor" can be a name, EIN or email, even of someone in the same file.</li>
                <li>Phone numbers that Excel shortened (977123456) are fixed to 0977123456.</li>
            </ul>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="im-card mb-3">
            <h6 class="fw-bold"><i class="bi bi-download"></i> Start from the template</h6>
            <p class="small text-muted mb-2">Includes two example rows. Delete them before importing.</p>
            <a href="{{ route('employees.import.template') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-filetype-csv"></i> Download template (opens in Excel)</a>
            <a href="{{ route('employees.export') }}" class="btn btn-link btn-sm">or export current employees to edit &amp; re-import</a>
        </div>
        <div class="im-card">
            <h6 class="fw-bold"><i class="bi bi-table"></i> Columns you can use</h6>
            <div class="im-cols">
                @foreach($columns as $c)
                    <div><code>{{ $c }}</code> <span class="text-muted">{{ \App\Support\EmployeeFields::label($c) }}</span></div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const drop = document.getElementById('drop'), input = document.getElementById('fileInput'), go = document.getElementById('goBtn'), txt = document.getElementById('dropText');
    function picked() { if (input.files[0]) { txt.innerHTML = '<i class="bi bi-check-circle text-success"></i> ' + input.files[0].name; go.disabled = false; } }
    input.addEventListener('change', picked);
    ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('over'); }));
    ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('over'); }));
    drop.addEventListener('drop', e => { if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; picked(); } });
    document.getElementById('importForm').addEventListener('submit', () => { go.disabled = true; go.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Checking…'; });
})();
</script>
@endpush

@endsection
