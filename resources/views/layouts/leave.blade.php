<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Leave Notification' }}</title>
</head>

<body style="margin:0;background:#f5f6fa;font-family:Arial,sans-serif;">

    {{-- HEADER --}}
    <div style="background:#0d6efd;padding:20px;text-align:center;color:#fff;">

        <img src="https://misc.tradesmartzm.com/logo.png"
             alt="Company Logo"
             style="height:45px;margin-bottom:8px;">

        <h2 style="margin:0;font-size:18px;">
            Tradesmart HR System
        </h2>

    </div>

    {{-- CONTENT --}}
    <div style="max-width:600px;margin:20px auto;background:#fff;padding:20px;border-radius:8px;">

        @yield('content')

    </div>

    {{-- FOOTER --}}
    <div style="text-align:center;font-size:12px;color:#888;padding:15px;">

        © {{ date('Y') }} Tradesmart Supplies Ltd | HR Management System

    </div>

</body>
</html>