@extends('layouts.portal')
@section('title', 'My Documents')

@section('content')

<style>
.md-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: 1rem 1.2rem; }
.md-drop { border: 2px dashed var(--admin-border, #cbd5e1); border-radius: 14px; padding: 1.6rem 1rem; text-align: center; cursor: pointer; transition: all .15s; display: block; }
.md-drop.over { border-color: #185FA5; background: rgba(24, 95, 165, .05); }
.md-drop i { font-size: 2rem; color: #185FA5; }
.md-doc { display: flex; align-items: center; gap: .8rem; padding: .7rem 0; border-bottom: 1px solid var(--admin-border, #eef2f7); }
.md-doc:last-child { border-bottom: 0; }
.md-ic { width: 42px; height: 42px; border-radius: 10px; background: var(--admin-surface-soft, #f1f5f9); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #185FA5; flex-shrink: 0; }
.md-chip { font-size: .68rem; font-weight: 700; padding: .1rem .5rem; border-radius: 999px; background: #f1f5f9; color: #475569; }
.md-chip.me { background: #e0f2fe; color: #075985; }
.md-filter a { font-size: .78rem; padding: .25rem .7rem; border-radius: 999px; border: 1px solid var(--admin-border, #e2e8f0); text-decoration: none; color: inherit; margin: 0 .2rem .3rem 0; display: inline-block; }
.md-filter a.active { background: #185FA5; border-color: #185FA5; color: #fff; }
</style>

@php
    $cat = request('category');
    $shown = $cat ? $documents->where('category', $cat) : $documents;
@endphp

<div class="mb-3">
    <h4 class="mb-1 fw-bold"><i class="bi bi-folder2-open"></i> My Documents</h4>
    <small class="text-muted">Your contract, letters and anything HR has on file, plus documents you upload yourself (NRC copy, certificates, medical notes, bank letter…).</small>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="md-card">
            <div class="md-filter mb-2">
                <a href="{{ route('portal.documents.index') }}" class="{{ ! $cat ? 'active' : '' }}">All ({{ $documents->count() }})</a>
                @foreach($documents->groupBy('category') as $c => $g)
                    <a href="{{ route('portal.documents.index', ['category' => $c]) }}" class="{{ $cat === $c ? 'active' : '' }}">{{ $c }} ({{ $g->count() }})</a>
                @endforeach
            </div>
            @forelse($shown as $doc)
                <div class="md-doc">
                    <span class="md-ic"><i class="bi {{ in_array($doc->ext, ['jpg', 'jpeg', 'png', 'webp']) ? 'bi-file-image' : ($doc->ext === 'pdf' ? 'bi-file-pdf' : (in_array($doc->ext, ['xls', 'xlsx']) ? 'bi-file-spreadsheet' : 'bi-file-earmark-text')) }}"></i></span>
                    <div class="flex-grow-1" style="min-width:0">
                        <div class="fw-semibold text-truncate">{{ $doc->name }}</div>
                        <div class="small text-muted">
                            <span class="md-chip {{ $doc->uploaded_by === 'employee' ? 'me' : '' }}">{{ $doc->uploaded_by === 'employee' ? 'Uploaded by you' : 'From HR' }}</span>
                            {{ $doc->category }}{{ $doc->uploaded_at ? ' · ' . $doc->uploaded_at->format('d M Y') : '' }}{{ $doc->size ? ' · ' . number_format($doc->size / 1024) . ' KB' : '' }}
                        </div>
                    </div>
                    <a href="{{ route('portal.documents.download', $doc->index) }}" class="btn btn-sm btn-outline-primary" title="Download"><i class="bi bi-download"></i></a>
                    @if($doc->uploaded_by === 'employee' && $canDelete)
                        <form method="POST" action="{{ route('portal.documents.destroy', $doc->index) }}" onsubmit="return confirm('Delete {{ addslashes($doc->name) }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-4"><i class="bi bi-folder fs-2 d-block"></i>No documents yet.</div>
            @endforelse
        </div>
    </div>

    <div class="col-lg-4">
        <form method="POST" action="{{ route('portal.documents.store') }}" enctype="multipart/form-data" class="md-card" id="upForm">
            @csrf
            <h6 class="fw-bold mb-3"><i class="bi bi-cloud-upload"></i> Upload a document</h6>
            <label class="md-drop mb-2" id="drop" for="files">
                <i class="bi bi-cloud-arrow-up"></i>
                <div class="small fw-semibold mt-1" id="dropText">Drop files here or tap to choose</div>
                <div class="small text-muted">PDF, photo, Word or Excel · 10 MB each</div>
                <input type="file" name="files[]" id="files" multiple class="d-none" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" required>
            </label>
            @error('files')<div class="text-danger small">{{ $message }}</div>@enderror
            @error('files.*')<div class="text-danger small">{{ $message }}</div>@enderror
            <label class="form-label small mb-1">What is it?</label>
            <select name="category" class="form-select form-select-sm mb-2" required>
                @foreach($categories as $c)<option @selected(old('category') === $c)>{{ $c }}</option>@endforeach
            </select>
            <input type="text" name="name" class="form-control form-control-sm mb-3" maxlength="150" placeholder="Title (optional), e.g. Degree certificate" value="{{ old('name') }}">
            <button class="btn btn-primary btn-sm w-100" id="upBtn"><i class="bi bi-upload"></i> Upload</button>
            <div class="form-text">HR is notified and can see your uploads. You can delete your own uploads; documents from HR stay.</div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const drop = document.getElementById('drop'), input = document.getElementById('files'), txt = document.getElementById('dropText');
    const show = () => { const n = input.files.length; if (n) txt.innerHTML = '<i class="bi bi-check-circle text-success"></i> ' + (n === 1 ? input.files[0].name : n + ' files selected'); };
    input.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.add('over'); }));
    ['dragleave', 'drop'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.remove('over'); }));
    drop.addEventListener('drop', ev => { if (ev.dataTransfer.files.length) { input.files = ev.dataTransfer.files; show(); } });
    document.getElementById('upForm').addEventListener('submit', () => { const b = document.getElementById('upBtn'); b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading…'; });
})();
</script>
@endpush

@endsection
