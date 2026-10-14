@extends('layouts.auth')

@section('title', 'Reset Password')
@section('subtitle', 'Choose a new password')

@section('content')

@include('auth.partials.flash')

<form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label class="form-label small fw-semibold text-muted" for="email">Email Address</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" id="email" value="{{ old('email', $email) }}"
                   class="form-control border-start-0" required>
        </div>
        @error('email')<small class="text-danger">{{ $message }}</small>@enderror
    </div>

    @include('auth.partials.new-password')

    <button class="btn btn-primary w-100 py-2" type="submit">
        <i class="bi bi-shield-lock me-2"></i> Save new password
    </button>
</form>

@endsection

@section('footer')
    <div class="text-center small">
        <a href="{{ route('password.request') }}" class="text-decoration-none">Link expired? Request a new one</a>
    </div>
@endsection
