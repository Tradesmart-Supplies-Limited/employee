@extends('layouts.management')
@section('title', 'My Profile')

@section('content')
<div class="mb-3">
    <h1 class="h4 fw-bold mb-0"><i class="bi bi-person-badge"></i> My Profile</h1>
    <div class="small text-muted">Your details as staff see them across the system. Thank you for keeping them up to date.</div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="hr-card hr-card-b text-center">
            <div class="mx-auto mb-3" style="width:140px">@include('management.partials.avatar', ['user' => $user, 'size' => 140])</div>
            <h5 class="mb-0">{{ $user->name }}</h5>
            <div class="small text-muted">{{ $user->job_title ?: 'Management' }}</div>

            <form method="POST" action="{{ route('mgmt.photo.update') }}" enctype="multipart/form-data" class="mt-3" id="photoForm">
                @csrf
                <label class="mp-drop" id="photoDrop">
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" id="photoInput">
                    <i class="bi bi-camera"></i>
                    <span id="photoHint">{{ $user->photo_path ? 'Change photo' : 'Add a profile photo' }}<small class="d-block text-muted">JPG, PNG or WebP, up to 4 MB</small></span>
                </label>
                @error('photo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <button class="btn btn-primary btn-sm mt-2 d-none" id="photoSave"><i class="bi bi-upload me-1"></i> Save photo</button>
            </form>
            @if($user->photo_path)
                <form method="POST" action="{{ route('mgmt.photo.remove') }}" class="mt-2" onsubmit="return confirm('Remove your profile photo?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-link btn-sm text-danger p-0">Remove photo</button>
                </form>
            @endif

            <hr>
            <div class="text-start small">
                <div class="d-flex justify-content-between mb-1"><span>Profile complete</span><b>{{ $completion['percent'] }}%</b></div>
                <div class="progress" style="height:6px" aria-hidden="true"><div class="progress-bar bg-success" style="width: {{ $completion['percent'] }}%"></div></div>
                @if($completion['missing'])<div class="text-muted mt-2">Still to add: {{ implode(', ', array_map('strtolower', $completion['missing'])) }}.</div>@endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form method="POST" action="{{ route('mgmt.profile.update') }}" class="hr-card">
            @csrf @method('PUT')
            <div class="hr-card-h"><h6><i class="bi bi-person-lines-fill"></i> Your information</h6></div>
            <div class="hr-card-b">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="name">Full name</label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="job_title">Job title</label>
                        <input class="form-control @error('job_title') is-invalid @enderror" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}" placeholder="e.g. Managing Director">
                        @error('job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="email">Email (used to sign in)</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email" data-original="{{ $user->email }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="office_location">Office / location</label>
                        <input class="form-control @error('office_location') is-invalid @enderror" id="office_location" name="office_location" value="{{ old('office_location', $user->office_location) }}" placeholder="HQ, Ndola Zambia">
                        @error('office_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12" id="pwBox" @if(! $errors->has('current_password')) hidden @endif>
                        <label class="form-label small fw-semibold" for="current_password">Current password <span class="text-muted fw-normal">(needed to change your sign-in email)</span></label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" autocomplete="current-password" style="max-width:320px">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="hr-card-h justify-content-start" style="border-top:1px solid var(--admin-border,#e2e8f0);border-bottom:0">
                <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save changes</button>
                <a href="{{ route('mgmt.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.mp-drop { position: relative; display: flex; align-items: center; gap: 10px; justify-content: center; padding: 12px; border: 1.5px dashed var(--admin-border, #cbd5e1); border-radius: 12px; cursor: pointer; background: var(--admin-surface-soft, #f8fafc); margin: 0; text-align: left; }
.mp-drop:hover { border-color: var(--brand, #185FA5); }
.mp-drop i { font-size: 1.4rem; color: var(--brand-text, #185FA5); }
.mp-drop input { position: absolute; width: 1px; height: 1px; opacity: 0; }
</style>
<script>
(function () {
    var input = document.getElementById('photoInput'), save = document.getElementById('photoSave'), hint = document.getElementById('photoHint');
    input.addEventListener('change', function () {
        var f = input.files[0]; if (!f) return;
        hint.innerHTML = f.name + '<small class="d-block text-muted">Ready to save</small>';
        save.classList.remove('d-none');
        // Live preview in the round frame
        var wrap = document.querySelector('.col-lg-4 .mx-auto'), url = URL.createObjectURL(f);
        wrap.innerHTML = '<img src="' + url + '" alt="" class="rounded-circle" style="width:140px;height:140px;object-fit:cover;">';
    });
    var email = document.getElementById('email'), pw = document.getElementById('pwBox');
    email.addEventListener('input', function () { pw.hidden = email.value.trim().toLowerCase() === email.dataset.original.toLowerCase(); });
})();
</script>
@endsection
