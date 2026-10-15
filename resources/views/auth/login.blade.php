@extends('layouts.auth')

@section('title', 'Sign In')
@section('subtitle', 'Human Resource Workspace')

@section('content')

@if($needsSetup ?? false)
    <a href="{{ route('setup') }}" class="alert alert-warning small py-2 d-flex gap-2 align-items-center text-decoration-none">
        <i class="bi bi-stars"></i><span><strong>No logins exist yet.</strong> Set up the system to create the first Admin.</span>
    </a>
@endif

@if($asRole ?? null)
    <div class="d-flex gap-2 align-items-start p-2 mb-3 rounded-3" style="background:color-mix(in srgb, {{ $asRole['color'] }} 10%, transparent);border-left:4px solid {{ $asRole['color'] }}">
        <i class="bi {{ $asRole['icon'] }} fs-5" style="color:{{ $asRole['color'] }}"></i>
        <div class="small">
            <div><strong>Signing in as {{ ['employee' => 'an employee', 'manager' => 'a manager', 'hr' => 'HR', 'director' => 'a director', 'admin' => 'an admin'][$as] }}</strong> &middot; <a href="{{ route('home') }}#pickTitle" class="text-decoration-none">change</a></div>
            <div class="text-muted">{{ $asGuide['tip'] }}</div>
        </div>
    </div>
@endif

@include('auth.partials.flash')

<form method="POST"
      action="{{ route('login') }}"
      class="needs-validation"
      novalidate>

    @csrf

    {{-- EMAIL --}}
    <div class="mb-3">

        <label class="form-label small fw-semibold text-muted">
            Email Address
        </label>

        <div class="input-group">

            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-envelope"></i>
            </span>

            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   class="form-control border-start-0"
                   placeholder="name@company.com"
                   required>

        </div>

        @error('email')
            <small class="text-danger">{{ $message }}</small>
        @enderror

    </div>

    {{-- PASSWORD --}}
    <div class="mb-3">

        <div class="d-flex justify-content-between">

            <label class="form-label small fw-semibold text-muted">
                Password
            </label>

            <a href="{{ route('password.request') }}"
               class="small text-decoration-none">
                Forgot Password?
            </a>

        </div>

        <div class="input-group">

            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-lock"></i>
            </span>

            <input type="password"
                   name="password"
                   class="form-control border-start-0"
                   placeholder="Enter your password"
                   required>

        </div>

        @error('password')
            <small class="text-danger">{{ $message }}</small>
        @enderror

    </div>

    {{-- REMEMBER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div class="form-check">

            <input class="form-check-input"
                   type="checkbox"
                   name="remember"
                   id="remember">

            <label class="form-check-label"
                   for="remember">
                Remember me
            </label>

        </div>

        <span class="small text-muted">
            <i class="bi bi-shield-check"></i>
            Secure Login
        </span>

    </div>

    {{-- BUTTON --}}
    <button class="btn btn-primary w-100 py-2"
            type="submit">

        <i class="bi bi-box-arrow-in-right me-2"></i>
        Sign In

    </button>

    

</form>

<p class="small text-muted text-center mt-3 mb-0">
    Not sure which login you have? <a href="{{ route('home') }}" class="text-decoration-none">Find your badge</a>
</p>

@endsection