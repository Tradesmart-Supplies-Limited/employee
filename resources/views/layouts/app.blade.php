<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
</head>

<body>

<div class="admin-shell">

    <div class="sidebar-backdrop" data-sidebar-close></div>

    @include('partials.sidebar')

    <div class="admin-main">

        @include('partials.navbar')

        <main class="dashboard-content">

            <div class="container-fluid px-3 px-lg-4 py-4">

                @include('partials.alerts')

                @yield('content')

            </div>

        </main>

        @include('partials.footer')

    </div>

</div>

@include('partials.scripts')

</body>
</html>