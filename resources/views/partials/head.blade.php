<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>@yield('title', config('app.name'))</title>

@php
    $uiAccent = \App\Support\Themes::valid(auth()->user()?->ui_accent);
    $uiThemes = \App\Support\Themes::ALL;
@endphp
<script>
    // Colour theme (saved on the account) and light/dark (saved in this browser),
    // applied before the page draws so it never flashes the wrong colours.
    (function (d) {
        d.setAttribute('data-accent', @json($uiAccent));
        try {
            var m = localStorage.getItem('adminHMD.colorTheme');
            if (m === 'dark' || m === 'light') { d.setAttribute('data-theme', m); d.setAttribute('data-bs-theme', m); }
            else if (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) { d.setAttribute('data-theme', 'dark'); d.setAttribute('data-bs-theme', 'dark'); }
        } catch (e) {}
    })(document.documentElement);
</script>

<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/hr.css') }}?v=2">
<link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v=1">
<meta name="theme-color" content="{{ $uiThemes[$uiAccent][1] }}">

@stack('styles')
