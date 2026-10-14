<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
</head>

<body data-user-name="{{ auth()->user()->name }}">

<div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<div class="admin-shell">

    <div class="sidebar-backdrop" data-sidebar-close></div>

    @include('portal.partials.sidebar')

    <div class="admin-main">

        @include('partials.navbar')

        <main class="dashboard-content page-transition" id="page-content">
            <div class="container-fluid px-3 px-lg-4 py-4">

                @include('partials.alerts')

                @yield('content')

            </div>
        </main>

        @include('partials.footer')

    </div>

</div>

@include('partials.scripts')

@stack('modals')

</body>
</html>
