@extends('layouts.auth')

@section('title', 'Create Account')
@section('subtitle', 'Create admin HR Management account')

@section('content')

<form method="POST"
      action="{{ route('register') }}"
      class="needs-validation"
      novalidate>

    @csrf

    <div class="mb-4">
        <p class="eyebrow mb-1">Secure Access</p>
        <h1 class="h3 mb-1">Create Account</h1>
        <p class="text-muted mb-0">
            Register a new admin HR system administrator.
        </p>
    </div>

    {{-- FULL NAME --}}
    <div class="mb-3">

        <label class="form-label small fw-semibold text-muted">
            Full Name
        </label>

        <div class="input-group">

            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-person"></i>
            </span>

            <input type="text"
                   name="name"
                   value="{{ old('name') }}"
                   class="form-control border-start-0"
                   placeholder="John Doe"
                   required>

        </div>

        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror

    </div>

    {{-- POSITION / ROLE --}}
    <div class="mb-3">

        <label class="form-label small fw-semibold text-muted">
            Position / Role
        </label>

        <div class="input-group">

            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-briefcase"></i>
            </span>

            <input type="text"
                   name="role"
                   value="{{ old('role') }}"
                   class="form-control border-start-0"
                   placeholder="HR Manager"
                   required>

        </div>

        @error('role')
            <small class="text-danger">{{ $message }}</small>
        @enderror

    </div>

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

        <label class="form-label small fw-semibold text-muted">
            Password
        </label>

        <div class="input-group">

            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-lock"></i>
            </span>

            <input type="password"
                   name="password"
                   class="form-control border-start-0"
                   placeholder="Create a secure password"
                   required>

        </div>

        <small class="text-muted">
            Minimum 8 characters recommended.
        </small>

        @error('password')
            <small class="text-danger d-block">{{ $message }}</small>
        @enderror

    </div>

    {{-- CONFIRM PASSWORD --}}
    <div class="mb-3">

        <label class="form-label small fw-semibold text-muted">
            Confirm Password
        </label>

        <div class="input-group">

            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-shield-lock"></i>
            </span>

            <input type="password"
                   name="password_confirmation"
                   class="form-control border-start-0"
                   placeholder="Confirm password"
                   required>

        </div>

    </div>

    {{-- TERMS --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div class="form-check">

            <input class="form-check-input"
                   type="checkbox"
                   id="terms"
                   required>

            <label class="form-check-label"
                   for="terms">
                I agree to company terms
            </label>

        </div>

        <span class="small text-muted">
            <i class="bi bi-shield-check"></i>
            Secure Registration
        </span>

    </div>

    {{-- SUBMIT --}}
    <button class="btn btn-primary w-100 py-2"
            type="submit">

        <i class="bi bi-person-plus me-2"></i>
        Create Account

    </button>

</form>

@endsection