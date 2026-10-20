<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Leave Request Form · {{ config('company.name', 'Tradesmart Supplies Limited') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/theme.css') }}" rel="stylesheet">
    <style>
        body { background: linear-gradient(180deg, #eaf1f8 0, #f4f6fb 320px); min-height: 100vh; padding: 28px 16px 48px; font-size: 14px; }
        .pf-wrap { max-width: 1080px; margin: 0 auto; }
        .pf-top { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .pf-top h1 { font-size: 1.25rem; font-weight: 800; margin: 0; }
        .pf-done { text-align: center; padding: 36px 20px; background: #fff; border-radius: 14px; border: 1px solid #dde3ea; margin-bottom: 16px; }
        .pf-done i { font-size: 2.6rem; color: #16a34a; }
        @media print { body { background: #fff; padding: 0; } .af-submit, .pf-top a { display: none !important; } }
    </style>
</head>
<body>
<div class="pf-wrap">
    <div class="pf-top">
        <div>
            <h1><i class="bi bi-calendar-plus"></i> Apply for leave</h1>
            <div class="small text-muted">Have a login? <a href="{{ route('login') }}">Sign in</a> and your details and balance are filled in for you.</div>
        </div>
    </div>

    @if(session('success'))
        <div class="pf-done">
            <i class="bi bi-check-circle-fill"></i>
            <h5 class="mt-2 mb-1">Leave request submitted</h5>
            <p class="text-muted small mb-3">{{ session('success') }} You'll be emailed at every approval step.</p>
            <a href="{{ route('leave.apply') }}" class="btn btn-outline-primary btn-sm">Submit another request</a>
        </div>
    @else
        @include('leave.partials.apply-form')
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
