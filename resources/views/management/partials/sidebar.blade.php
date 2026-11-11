<aside class="admin-sidebar" id="adminSidebar">

    {{-- BRAND --}}
    <div class="sidebar-header">
        <a class="brand-mark" href="{{ route('mgmt.dashboard') }}">
            <img src="{{ config('company.logo_url') }}" width="36" height="36" alt="Logo" class="brand-logo">
            <span class="brand-copy">
                <span class="brand-title">Tradesmart Supplies</span>
                <span class="brand-subtitle">Management Self Service</span>
            </span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <span class="nav-group-label nav-text">My portal</span>

        <a class="nav-link {{ request()->routeIs('mgmt.dashboard') ? 'active' : '' }}" href="{{ route('mgmt.dashboard') }}">
            <i class="bi bi-speedometer2 nav-icon"></i>
            <span class="nav-text">Overview</span>
        </a>
        <a class="nav-link {{ request()->routeIs('mgmt.profile') ? 'active' : '' }}" href="{{ route('mgmt.profile') }}">
            <i class="bi bi-person-badge nav-icon"></i>
            <span class="nav-text">My Profile</span>
        </a>
        <a class="nav-link {{ request()->routeIs('mgmt.attendance') ? 'active' : '' }}" href="{{ route('mgmt.attendance') }}">
            <i class="bi bi-clock-history nav-icon"></i>
            <span class="nav-text">My Attendance</span>
        </a>

        <span class="nav-group-label nav-text">Switch</span>
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="bi bi-grid-1x2 nav-icon"></i>
            <span class="nav-text">Management Admin</span>
        </a>
    </nav>

    <div class="sidebar-user">
        @include('management.partials.avatar', ['user' => auth()->user(), 'size' => 36])
        <span class="sidebar-footer-text">
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ auth()->user()->job_title ?: 'Management' }}</small>
        </span>
        <form action="{{ route('logout') }}" method="POST" class="sidebar-logout text-end text-danger">
            @csrf
            <button type="submit" class="nav-link btn btn-link btn-lg text-end p-0" title="Logout" aria-label="Logout">
                <i class="bi bi-box-arrow-right nav-icon fs-4"></i>
            </button>
        </form>
    </div>
</aside>
{{-- main.js fills the footer from this --}}
<script>window.adminHMDUser = @json(['name' => auth()->user()?->name ?? 'Guest', 'workspace' => (auth()->user()?->job_title ?: 'Management') . ' · Self Service', 'avatar' => auth()->user()?->photo_url]);</script>
