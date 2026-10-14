@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('subtitle', 'Reset your password')

@section('content')

<style>
    .rp-methods { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
    .rp-method { position: relative; margin: 0; cursor: pointer; }
    .rp-method input { position: absolute; opacity: 0; }
    .rp-method .rp-card {
        display: block; height: 100%; padding: .75rem .8rem; border: 1.5px solid var(--admin-border, #dbe2ea);
        border-radius: 10px; transition: border-color .15s, background .15s;
    }
    .rp-method .rp-card i { font-size: 1.25rem; color: #185FA5; }
    .rp-method .rp-card strong { display: block; font-size: .86rem; margin-top: .25rem; }
    .rp-method .rp-card small { display: block; font-size: .74rem; color: var(--admin-muted, #64748b); line-height: 1.35; }
    .rp-method input:checked + .rp-card { border-color: #185FA5; background: rgba(24, 95, 165, .07); }
    .rp-method input:focus-visible + .rp-card { outline: 2px solid #185FA5; outline-offset: 2px; }
</style>

<p class="small text-muted mb-3">
    Enter the email you sign in with. We'll send you a way to choose a new password.
</p>

@include('auth.partials.flash')

<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label class="form-label small fw-semibold text-muted" for="email">Email Address</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   class="form-control border-start-0" placeholder="name@company.com" required autofocus>
        </div>
        @error('email')<small class="text-danger">{{ $message }}</small>@enderror
    </div>

    <div class="mb-4">
        <label class="form-label small fw-semibold text-muted">How should we help you reset it?</label>
        <div class="rp-methods">
            <label class="rp-method">
                <input type="radio" name="method" value="link" @checked(old('method', 'link') === 'link')>
                <span class="rp-card">
                    <i class="bi bi-link-45deg"></i>
                    <strong>Email me a link</strong>
                    <small>Click the link in your inbox to set a new password.</small>
                </span>
            </label>
            <label class="rp-method">
                <input type="radio" name="method" value="otp" @checked(old('method') === 'otp')>
                <span class="rp-card">
                    <i class="bi bi-123"></i>
                    <strong>Email me a code</strong>
                    <small>Get a 6-digit one-time code to enter here.</small>
                </span>
            </label>
        </div>
        @error('method')<small class="text-danger">{{ $message }}</small>@enderror
    </div>

    <button class="btn btn-primary w-100 py-2" type="submit">
        <i class="bi bi-send me-2"></i> Send reset instructions
    </button>
</form>

@endsection

@section('footer')
    <div class="text-center small">
        <a href="{{ route('login') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to sign in</a>
    </div>
@endsection
