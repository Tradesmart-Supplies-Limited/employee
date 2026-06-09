<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Tradesmart Supplies Limited - HR Management System">
  <title>@yield('title', 'Auth') | Tradesmart Supplies Limited - HR Management System</title>

  <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body class="auth-body">

  {{-- Theme Toggle --}}
  <button class="icon-button theme-toggle auth-theme-toggle" type="button" data-theme-toggle>
    <i class="bi bi-moon-stars" data-theme-icon></i>
  </button>

  <main class="auth-page">
    <section class="auth-card">

      {{-- BRAND --}}
      <a class="auth-brand" href="#">
        <span class="brand-icon">
          <i class="bi bi-grid-1x2-fill"></i>
        </span>
        <span>
          <strong>Tradesmart Supplies Limited</strong>
          <small>@yield('subtitle', 'HR Management System')</small>
        </span>
      </a>

      {{-- FORM CONTENT --}}
      @yield('content')

      {{-- FOOTER --}}
      <div class="auth-footer mt-3">
        @yield('footer')
      </div>

    </section>
  </main>

  <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>