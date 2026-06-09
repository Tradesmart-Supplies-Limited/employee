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
                <img src="http://misc.tradesmartzm.com/logo.png" width="36" height="36" alt="Tradesmart Supplies Logo"
                    class="brand-logo">

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

    <script>
    // Show loader manually
    function showLoader() {
        document.getElementById('global-loader').classList.remove('d-none');
    }

    // Hide loader
    function hideLoader() {
        document.getElementById('global-loader').classList.add('d-none');
    }

    // Show loader on any form submit
    document.addEventListener('submit', function () {
        showLoader();
    });

    // Show loader on page navigation clicks
    // document.addEventListener('click', function (e) {
    //     const link = e.target.closest('a');
    //     if (link && link.href && !link.target) {
    //         showLoader();
    //     }
    // });

    

    document.addEventListener("click", function (e) {
    const link = e.target.closest("a");

    if (link && link.href && !link.target && !link.hasAttribute("download")) {
        e.preventDefault();

        document.getElementById("page-content").style.opacity = "0";
        document.getElementById("page-content").style.transform = "translateY(6px)";

        setTimeout(() => {
            window.location = link.href;
        }, 150);
    }
});

</script>


<div id="global-loader" class="global-loader d-none">
    <div class="loader-box">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 mb-0">Loading...</p>
    </div>
</div>
</body>

</html>