<aside class="admin-sidebar" id="adminSidebar">

    {{-- BRAND --}}
    <div class="sidebar-header">

        <a class="brand-mark" href="{{ route('dashboard') }}">

            <img src="http://misc.tradesmartzm.com/logo.png" width="36" height="36" alt="Tradesmart Supplies Logo" class="brand-logo">

            <span class="brand-copy">
                <span class="brand-title">Tradesmart Supplies</span>
                <span class="brand-subtitle">HR Platform</span>
            </span>

        </a>

    </div>

    {{-- NAVIGATION --}}
    <nav class="sidebar-nav">

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

        <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}"
           href="{{ route('departments.index') }}">
            <i class="bi bi-diagram-3 nav-icon"></i>
            <span class="nav-text">Departments</span>
        </a>

        <a class="nav-link {{ request()->routeIs('leave.*') ? 'active' : '' }}"
           href="{{ route('leave.index') }}">
            <i class="bi bi-calendar-check nav-icon"></i>
            <span class="nav-text">Leave</span>
        </a>

        <a class="nav-link {{ request()->routeIs('attendance.*') ? 'active' : '' }}"
           href="{{ route('attendance.index') }}">
            <i class="bi bi-clock-history nav-icon"></i>
            <span class="nav-text">Attendance</span>
        </a>

        <a class="nav-link {{ request()->routeIs('payroll.*') ? 'active' : '' }}"
           href="{{ route('payroll.runs.index') }}">
            <i class="bi bi-cash-stack nav-icon"></i>
            <span class="nav-text">Payroll</span>
        </a>

        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
           href="{{ route('reports.index') }}">
            <i class="bi bi-bar-chart nav-icon"></i>
            <span class="nav-text">Reports</span>
        </a>

        <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"
           href="{{ route('settings.index') }}">
            <i class="bi bi-gear nav-icon"></i>
            <span class="nav-text">Settings</span>
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

        <div class="ms-2">
            <strong>{{ Auth::user()->name ?? 'Guest' }}</strong><br>
            <small class="text-muted">{{ Auth::user()->role ?? 'User' }}</small>
        </div>

    </div>

</aside>