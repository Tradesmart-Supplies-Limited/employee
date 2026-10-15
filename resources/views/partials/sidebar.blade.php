<aside class="admin-sidebar" id="adminSidebar">

    {{-- BRAND --}}
    <div class="sidebar-header">
        <a class="brand-mark" href="{{ route('dashboard') }}">
            <img src="http://misc.tradesmartzm.com/logo.png" width="36" height="36" alt="Tradesmart Supplies Logo" class="brand-logo">
            <span class="brand-copy">
                <span class="brand-title">Tradesmart Supplies</span>
                <span class="brand-subtitle">HR Workspace</span>
            </span>
        </a>
    </div>

    {{-- NAVIGATION --}}
        {{-- NAVIGATION --}}
    <nav class="sidebar-nav">

        @php $me = auth()->user(); $full = (bool) $me?->hasFullAccess(); @endphp
        <span class="nav-group-label nav-text">Main</span>

        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2 nav-icon"></i>
            <span class="nav-text">Dashboard</span>
        </a>

        @if($full)
        <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}"
           href="{{ route('employees.index') }}">
            <i class="bi bi-people nav-icon"></i>
            <span class="nav-text">Employees</span>
        </a>

        @php $pendingProfileChanges = \App\Models\ProfileChangeRequest::where('status', 'pending')->count(); @endphp
        <a class="nav-link {{ request()->routeIs('profile-changes.*') ? 'active' : '' }}"
           href="{{ route('profile-changes.index') }}">
            <i class="bi bi-person-gear nav-icon"></i>
            <span class="nav-text">Detail Changes</span>
            @if($pendingProfileChanges)<span class="badge rounded-pill bg-warning text-dark ms-auto">{{ $pendingProfileChanges }}</span>@endif
        </a>

        <a class="nav-link {{ request()->routeIs('employees.import*') ? 'active' : '' }}"
           href="{{ route('employees.import') }}">
            <i class="bi bi-cloud-upload nav-icon"></i>
            <span class="nav-text">Bulk Import</span>
        </a>

        <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}"
           href="{{ route('departments.index') }}">
            <i class="bi bi-building nav-icon"></i>
            <span class="nav-text">Departments</span>
        </a>

        <a class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}"
           href="{{ route('announcements.index') }}">
            <i class="bi bi-megaphone nav-icon"></i>
            <span class="nav-text">Announcements</span>
        </a>

        @endif

        <a class="nav-link {{ request()->routeIs('attendance.*') && ($full || ! request()->routeIs('attendance.rota.*')) ? 'active' : '' }}"
           href="{{ route('attendance.index') }}">
            <i class="bi bi-clock-history nav-icon"></i>
            <span class="nav-text">Attendance</span>
        </a>

        @if(! $full && $me?->hasPermission('rota.manage'))
        <a class="nav-link {{ request()->routeIs('attendance.rota.*') ? 'active' : '' }}"
           href="{{ route('attendance.rota.index') }}">
            <i class="bi bi-calendar-week nav-icon"></i>
            <span class="nav-text">Shift Rota</span>
        </a>
        @endif

        <span class="nav-group-label nav-text">Leave</span>

        <a class="nav-link {{ request()->routeIs('leave.index', 'leave.show') ? 'active' : '' }}"
           href="{{ route('leave.index') }}">
            <i class="bi bi-calendar-check nav-icon"></i>
            <span class="nav-text">Leave Requests</span>
        </a>

        <a class="nav-link {{ request()->routeIs('leave.manage.index', 'leave.manage.employee') ? 'active' : '' }}"
           href="{{ route('leave.manage.index') }}">
            <i class="bi bi-bar-chart-line nav-icon"></i>
            <span class="nav-text">{{ $full ? 'Leave Reports & Balances' : 'Team Leave Balances' }}</span>
        </a>

        <a class="nav-link {{ request()->routeIs('leave.manage.planner') ? 'active' : '' }}"
           href="{{ route('leave.manage.planner') }}">
            <i class="bi bi-calendar3-range nav-icon"></i>
            <span class="nav-text">Leave Planner</span>
        </a>

        @if($full)
        <a class="nav-link {{ request()->routeIs('leave.manage.record') ? 'active' : '' }}"
           href="{{ route('leave.manage.record') }}">
            <i class="bi bi-journal-plus nav-icon"></i>
            <span class="nav-text">Record Leave</span>
        </a>

        <a class="nav-link {{ request()->routeIs('leave.manage.holidays') ? 'active' : '' }}"
           href="{{ route('leave.manage.holidays') }}">
            <i class="bi bi-flag nav-icon"></i>
            <span class="nav-text">Public Holidays</span>
        </a>

        <a class="nav-link {{ request()->routeIs('leave.apply') ? 'active' : '' }}"
           href="{{ route('leave.apply') }}">
            <i class="bi bi-calendar-plus nav-icon"></i>
            <span class="nav-text">Apply for Leave</span>
        </a>
        @endif

        <!-- <span class="nav-group-label nav-text">Payroll</span>

        <a class="nav-link {{ request()->routeIs('payroll.runs.*') ? 'active' : '' }}"
           href="{{ route('payroll.runs.index') }}">
            <i class="bi bi-cash-stack nav-icon"></i>
            <span class="nav-text">Payroll Runs</span>
        </a>

        <a class="nav-link {{ request()->routeIs('payroll.index', 'payroll.create', 'payroll.show', 'payroll.edit') ? 'active' : '' }}"
           href="{{ route('payroll.index') }}">
            <i class="bi bi-receipt nav-icon"></i>
            <span class="nav-text">Payslips</span>
        </a>

        <a class="nav-link {{ request()->routeIs('payroll.rules.*') ? 'active' : '' }}"
           href="{{ route('payroll.rules.index') }}">
            <i class="bi bi-sliders nav-icon"></i>
            <span class="nav-text">Payroll Rules</span>
        </a>

        <a class="nav-link {{ request()->routeIs('payroll.reports.*') ? 'active' : '' }}"
           href="{{ route('payroll.reports.index') }}">
            <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
            <span class="nav-text">Payroll Reports</span>
        </a> -->
<!-- 
        <span class="nav-group-label nav-text">Insights</span>

        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
           href="{{ route('reports.index') }}">
            <i class="bi bi-bar-chart-line nav-icon"></i>
            <span class="nav-text">Reports</span>
        </a> -->

        <span class="nav-group-label nav-text">{{ $full ? 'System' : 'Me' }}</span>

        @if($full)
        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
           href="{{ route('users.index') }}">
            <i class="bi bi-shield-lock nav-icon"></i>
            <span class="nav-text">Users &amp; Roles</span>
        </a>


        <a class="nav-link {{ request()->routeIs('settings.contract-reminders.*') ? 'active' : '' }}"
           href="{{ route('settings.contract-reminders.index') }}">
            <i class="bi bi-bell nav-icon"></i>
            <span class="nav-text">Contract Reminders</span>
        </a>

        <a class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}"
           href="{{ route('settings.index') }}">
            <i class="bi bi-gear nav-icon"></i>
            <span class="nav-text">Settings</span>
        </a>
        @endif

        @if($me?->canUsePortal())
        <a class="nav-link" href="{{ route('portal.dashboard') }}" title="Switch to your own self-service portal">
            <i class="bi bi-arrow-left-right nav-icon"></i>
            <span class="nav-text">My Self-Service</span>
        </a>
        @endif

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
            <small>{{ Auth::user()?->roleLabel() ?? 'User' }}</small>
        </span>

        <form action="{{ route('logout') }}" method="POST" class="sidebar-logout text-end text-danger">
            @csrf
            <button type="submit" class="nav-link btn btn-link btn-lg text-end p-0" title="Logout" aria-label="Logout">
                <i class="bi bi-box-arrow-right nav-icon fs-4"></i>
            </button>
        </form>

    </div>

</aside>
{{-- main.js fills the footer from this: show the signed-in role instead of a fixed label --}}
<script>window.adminHMDUser = @json(['name' => auth()->user()?->name ?? 'Guest', 'workspace' => (auth()->user()?->roleLabel() ?? 'User') . ' · HR Workspace', 'avatar' => null]);</script>