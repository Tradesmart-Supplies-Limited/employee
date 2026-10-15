@extends('layouts.app')
@section('title', $a->exists ? 'Edit announcement' : 'New announcement')

@section('content')
@php
    use App\Models\Announcement as A;
    $selected = old('departments', $a->departments ?? []);
    $audience = old('audience', empty($a->departments) ? 'all' : 'departments');
@endphp

<div class="mb-3">
    <a href="{{ $a->exists ? route('announcements.show', $a) : route('announcements.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Back</a>
    <h1 class="h4 fw-bold mb-0 mt-1">{{ $a->exists ? 'Edit announcement' : 'New announcement' }}</h1>
</div>

<form method="POST" action="{{ $a->exists ? route('announcements.update', $a) : route('announcements.store') }}" enctype="multipart/form-data" class="row g-3" id="anForm">
    @csrf @if($a->exists) @method('PUT') @endif
    <div class="col-xl-7">
        <div class="hr-card hr-card-b">
            <div class="mb-3">
                <label class="form-label fw-semibold">Title</label>
                <input type="text" name="title" maxlength="150" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $a->title) }}" required placeholder="e.g. Office closed on Independence Day" id="fTitle">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Message</label>
                <textarea name="body" rows="9" maxlength="10000" class="form-control @error('body') is-invalid @enderror" required placeholder="Write the announcement. Links are made clickable." id="fBody">{{ old('body', $a->body) }}</textarea>
                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category" class="form-select" id="fCat">
                        @foreach(A::CATEGORIES as $k => [$l])<option value="{{ $k }}" @selected(old('category', $a->category) === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Priority</label>
                    <select name="priority" class="form-select" id="fPri">
                        @foreach(A::PRIORITIES as $k => [$l])<option value="{{ $k }}" @selected(old('priority', $a->priority) === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label fw-semibold">Attachment <span class="hr-muted fw-normal">(optional: policy PDF, poster, form…)</span></label>
                <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                @error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($a->attachment_name)
                    <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="ra"><label class="form-check-label small" for="ra">Remove current file: {{ $a->attachment_name }}</label></div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="hr-card hr-card-b mb-3">
            <label class="form-label fw-semibold">Who should see it?</label>
            <div class="btn-group w-100 mb-2" role="group">
                <input type="radio" class="btn-check" name="audience" value="all" id="auAll" @checked($audience === 'all')>
                <label class="btn btn-outline-primary" for="auAll"><i class="bi bi-globe2"></i> Everyone</label>
                <input type="radio" class="btn-check" name="audience" value="departments" id="auDep" @checked($audience === 'departments')>
                <label class="btn btn-outline-primary" for="auDep"><i class="bi bi-diagram-3"></i> Departments</label>
            </div>
            <div id="depBox" class="{{ $audience === 'departments' ? '' : 'd-none' }}">
                @forelse($names as $n)
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="departments[]" value="{{ $n }}" id="d{{ $loop->index }}" @checked(in_array($n, $selected, true))><label class="form-check-label" for="d{{ $loop->index }}">{{ $n }}</label></div>
                @empty
                    <div class="small hr-muted">No departments on employee records yet.</div>
                @endforelse
                @error('departments')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="hr-card hr-card-b mb-3">
            <div class="row g-2">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold small">Publish</label>
                    <input type="datetime-local" name="publish_at" class="form-control form-control-sm" value="{{ old('publish_at', $a->local('publish_at')?->format('Y-m-d\TH:i')) }}">
                    <div class="form-text">Blank = now</div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold small">Show until</label>
                    <input type="datetime-local" name="expires_at" class="form-control form-control-sm @error('expires_at') is-invalid @enderror" value="{{ old('expires_at', $a->local('expires_at')?->format('Y-m-d\TH:i')) }}">
                    @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Blank = keep showing</div>
                </div>
            </div>
            <div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" role="switch" name="pinned" value="1" id="pin" @checked(old('pinned', $a->pinned))><label class="form-check-label" for="pin"><i class="bi bi-pin-angle"></i> Pin to the top</label></div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" name="send_email" value="1" id="em" @checked(old('send_email', $a->send_email)) @disabled($a->emailed_at)>
                <label class="form-check-label" for="em"><i class="bi bi-envelope"></i> Also email it to these employees</label>
                @if($a->emailed_at)<div class="small hr-muted">Already emailed to {{ $a->emailed_count }} on {{ $a->local('emailed_at')->format('d M H:i') }}.</div>@endif
            </div>
        </div>

        <div class="hr-card mb-3">
            <div class="hr-card-h"><h6><i class="bi bi-phone"></i> How staff will see it</h6></div>
            <div class="an-item" id="prev">
                <span class="an-ic" id="pIc" style="--c:var(--brand-text, #185FA5)"><i class="bi bi-megaphone"></i></span>
                <div style="min-width:0" class="flex-grow-1">
                    <div class="an-title" id="pTitle">Title</div>
                    <div class="small hr-muted" id="pBody" style="overflow-wrap:anywhere">Message…</div>
                </div>
            </div>
        </div>

        <button class="btn btn-primary w-100"><i class="bi bi-send"></i> {{ $a->exists ? 'Save changes' : 'Publish' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const cats = @json(collect(A::CATEGORIES)->map(fn ($c) => ['icon' => $c[1], 'color' => $c[2]]));
    const $ = id => document.getElementById(id);
    const upd = () => {
        $('pTitle').textContent = $('fTitle').value || 'Title';
        $('pBody').textContent = ($('fBody').value || 'Message…').slice(0, 160);
        const c = cats[$('fCat').value] || cats.general;
        $('pIc').style.setProperty('--c', c.color); $('pIc').innerHTML = '<i class="bi ' + c.icon + '"></i>';
    };
    ['fTitle', 'fBody', 'fCat'].forEach(id => $(id).addEventListener('input', upd)); upd();
    document.querySelectorAll('[name=audience]').forEach(r => r.addEventListener('change', () => $('depBox').classList.toggle('d-none', !$('auDep').checked)));
})();
</script>
@endpush
