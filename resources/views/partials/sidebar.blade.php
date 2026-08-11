<aside class="admin-sidebar" id="adminSidebar">

    {{-- BRAND --}}
    <div class="sidebar-header">
        <a class="brand-mark" href="{{ route('dashboard') }}">
            <img src="http://misc.tradesmartzm.com/logo.png" width="36" height="36" alt="Tradesmart Supplies Logo" class="brand-logo">
            <span class="brand-copy">
                <span class="brand-title">Tradesmart Supplies</span>
                <span class="brand-subtitle">Contract Reminder System</span>
            </span>
        </a>
    </div>

    {{-- NAVIGATION --}}
    <nav class="sidebar-nav">

        <span class="nav-group-label nav-text">Main</span>

        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2 nav-icon"></i>
            <span class="nav-text">Dashboard</span>
        </a>

        <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}"
           href="{{ route('employees.index') }}">
            <i class="bi bi-people nav-icon"></i>
            <span class="nav-text">Employees</span>
        </a>

        <span class="nav-group-label nav-text">System</span>

        <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
           href="{{ route('profile.index') }}">
            <i class="bi bi-person nav-icon"></i>
            <span class="nav-text">Profile</span>
        </a>

    

        

    </nav>

    {{-- USER --}}
    <div class="sidebar-user">

        @php
            $names = explode(' ', auth()->user()->name ?? 'Guest');
            $initials = strtoupper(
                substr($names[0] ?? '', 0, 1) .
                substr($names[1] ?? '', 0, 1)
            );
        @endphp

        <div class="avatar-img avatar-sm rounded-circle d-flex align-items-center justify-content-center fw-semibold"
             style="width:36px;height:36px;
             background:linear-gradient(135deg,#0d6efd,#4f8cff);
             color:#fff;font-size:12px;">
            {{ $initials }}
        </div>

        <span class="sidebar-footer-text">
            <strong>{{ Auth::user()->name ?? 'Guest' }}</strong>
            <small>{{ Auth::user()->role ?? 'User' }}</small>
        </span>

        <form action="{{ route('logout') }}" method="POST" class="sidebar-logout text-end text-danger">
            @csrf
            <button type="submit" class="nav-link btn btn-link btn-lg text-end p-0" title="Logout" aria-label="Logout">
                <i class="bi bi-box-arrow-right nav-icon fs-4"></i>
            </button>
        </form>

    </div>

</aside>