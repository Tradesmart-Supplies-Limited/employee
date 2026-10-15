@extends('layouts.auth')

@section('title', 'Choose your password')
@section('subtitle', $forced ? 'One more step before you start' : 'Change your password')

@section('content')

@if($forced)
    <div class="alert alert-warning small py-2 d-flex gap-2 align-items-start">
        <i class="bi bi-key mt-1"></i>
        <span>You signed in with a temporary password. Choose your own to continue, {{ explode(' ', trim($user->name))[0] }}.</span>
    </div>
@endif

@include('auth.partials.flash')

<form method="POST" action="{{ route('account.password.update') }}" novalidate>
    @csrf
    @method('PUT')
    <input type="email" name="username" value="{{ $user->email }}" autocomplete="username" hidden>

    <div class="mb-3">
        <label class="form-label small fw-semibold text-muted" for="current_password">{{ $forced ? 'Temporary password (from the email)' : 'Current password' }}</label>
        <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required autofocus>
        @error('current_password')<small class="text-danger">{{ $message }}</small>@enderror
    </div>

    @include('auth.partials.new-password')

    <button class="btn btn-primary w-100 py-2" type="submit">
        <i class="bi bi-shield-lock me-2"></i> Save my password
    </button>
</form>

@endsection

@section('footer')
    <form method="POST" action="{{ route('logout') }}" class="text-center">@csrf<button class="btn btn-link btn-sm text-muted text-decoration-none">Sign out</button></form>
@endsection
