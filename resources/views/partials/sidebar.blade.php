<aside class="admin-sidebar" id="adminSidebar">

    <div class="sidebar-header">

        <a class="brand-mark" href="{{ route('dashboard') }}">

            <span class="brand-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </span>

            <span class="brand-copy">
                <span class="brand-title">Tradesmart Supplies</span>
                <span class="brand-subtitle">HR Platform</span>
            </span>

        </a>

    </div>

    <nav class="sidebar-nav">

        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           href="">
            <span class="nav-icon">
                <i class="bi bi-speedometer2"></i>
            </span>
            <span class="nav-text">Dashboard</span>
        </a>

        <a class="nav-link"
           href="{{ route('employees.index') }}">
            <span class="nav-icon">
                <i class="bi bi-people"></i>
            </span>
            <span class="nav-text">Employees</span>
        </a>

        <a class="nav-link"
           href="">
            <span class="nav-icon">
                <i class="bi bi-diagram-3"></i>
            </span>
            <span class="nav-text">Departments</span>
        </a>

        <a class="nav-link"
           href="">
            <span class="nav-icon">
                <i class="bi bi-calendar-check"></i>
            </span>
            <span class="nav-text">Leave</span>
        </a>

        <a class="nav-link"
           href="">
            <span class="nav-icon">
                <i class="bi bi-clock-history"></i>
            </span>
            <span class="nav-text">Attendance</span>
        </a>

        <a class="nav-link"
           href="">
            <span class="nav-icon">
                <i class="bi bi-cash-stack"></i>
            </span>
            <span class="nav-text">Payroll</span>
        </a>

        <a class="nav-link"
           href="">
            <span class="nav-icon">
                <i class="bi bi-bar-chart"></i>
            </span>
            <span class="nav-text">Reports</span>
        </a>

        <a class="nav-link"
           href="">
            <span class="nav-icon">
                <i class="bi bi-gear"></i>
            </span>
            <span class="nav-text">Settings</span>
        </a>

    </nav>

    <div class="sidebar-user">

        <img class="avatar-img avatar-md sidebar-user-avatar"
             src="{{ asset('assets/images/avatar/avatar.jpg') }}"
             alt="User">

        <strong>{{ Auth::user()->name ?? 'Guest' }}</strong>

        <small>{{ Auth::user()->role ?? 'User' }}</small>

    </div>

</aside>