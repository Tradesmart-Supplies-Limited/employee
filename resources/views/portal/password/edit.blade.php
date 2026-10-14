@extends('layouts.portal')
@section('title', 'Change Password')

@section('content')

<div class="mb-4">
    <h4 class="mb-1"><i class="bi bi-shield-lock me-1"></i> Change Password</h4>
    @if($forced)
        <div class="alert alert-warning mt-3 mb-0">
            You are using a temporary password. Please choose your own password to continue.
        </div>
    @else
        <small class="text-muted">Use a strong password you don't use anywhere else.</small>
    @endif
</div>

<div class="card shadow-sm border-0" style="max-width:520px">
    <div class="card-body">
        <form method="POST" action="{{ route('portal.password.update') }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Current password</label>
                <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
            </div>

            <div class="mb-3">
                <label class="form-label">New password</label>
                <input type="password" name="password" class="form-control" required autocomplete="new-password">
                <small class="text-muted">At least 8 characters, with letters and numbers.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm new password</label>
                <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
            </div>

            <button class="btn btn-primary"><i class="bi bi-check2"></i> Update password</button>
        </form>
    </div>
</div>

@endsection
