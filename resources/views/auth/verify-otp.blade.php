@extends('layouts.auth')

@section('title', 'Enter Code')
@section('subtitle', 'Reset your password')

@section('content')

<style>
    .otp-boxes { display: flex; gap: .45rem; justify-content: space-between; }
    .otp-boxes input {
        width: 100%; max-width: 52px; height: 56px; text-align: center; font-size: 1.5rem; font-weight: 700;
        border: 1.5px solid var(--admin-border, #dbe2ea); border-radius: 10px; background: transparent; color: inherit;
    }
    .otp-boxes input:focus { border-color: var(--brand, #185FA5); outline: none; box-shadow: 0 0 0 3px rgba(var(--brand-rgb, 24, 95, 165), .15); }
</style>

<p class="small text-muted mb-3">
    We sent a 6-digit code to <strong>{{ $email ?: 'your email' }}</strong>. It expires in {{ $minutes }} minutes.
</p>

@include('auth.partials.flash')

<form method="POST" action="{{ route('password.otp.verify') }}" id="otpForm" novalidate>
    @csrf

    @if($email)
        <input type="hidden" name="email" value="{{ $email }}">
    @else
        <div class="mb-3">
            <label class="form-label small fw-semibold text-muted" for="email">Email Address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control" required>
            @error('email')<small class="text-danger">{{ $message }}</small>@enderror
        </div>
    @endif

    <div class="mb-3">
        <label class="form-label small fw-semibold text-muted">Verification Code</label>
        <div class="otp-boxes" id="otpBoxes">
            @for($i = 0; $i < 6; $i++)
                <input type="text" inputmode="numeric" maxlength="1" pattern="\d" aria-label="Digit {{ $i + 1 }}" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}">
            @endfor
        </div>
        <input type="hidden" name="code" id="code">
        @error('code')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
    </div>

    @include('auth.partials.new-password')

    <button class="btn btn-primary w-100 py-2" type="submit">
        <i class="bi bi-shield-check me-2"></i> Verify code &amp; save password
    </button>
</form>

<script>
(function () {
    const boxes = [...document.querySelectorAll('#otpBoxes input')], hidden = document.getElementById('code');
    const sync = () => hidden.value = boxes.map(b => b.value).join('');
    boxes.forEach((b, i) => {
        b.addEventListener('input', () => {
            b.value = b.value.replace(/\D/g, '').slice(0, 1);
            if (b.value && boxes[i + 1]) boxes[i + 1].focus();
            sync();
        });
        b.addEventListener('keydown', e => { if (e.key === 'Backspace' && !b.value && boxes[i - 1]) boxes[i - 1].focus(); });
        b.addEventListener('paste', e => {
            const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            if (!digits) return;
            e.preventDefault();
            boxes.forEach((x, j) => x.value = digits[j] || '');
            (boxes[digits.length] || boxes[5]).focus();
            sync();
        });
    });
    boxes[0].focus();
    document.getElementById('otpForm').addEventListener('submit', sync);
})();
</script>

@endsection

@section('footer')
    <div class="d-flex justify-content-between small">
        <a href="{{ route('password.request') }}" class="text-decoration-none"><i class="bi bi-arrow-repeat"></i> Send a new code</a>
        <a href="{{ route('login') }}" class="text-decoration-none">Back to sign in</a>
    </div>
@endsection
