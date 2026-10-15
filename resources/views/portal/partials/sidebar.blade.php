<aside class="admin-sidebar" id="adminSidebar">

    {{-- BRAND --}}
    <div class="sidebar-header">
        <a class="brand-mark" href="{{ route('portal.dashboard') }}">
            <img src="{{ config('company.logo_url') }}" width="36" height="36" alt="Logo" class="brand-logo">
            <span class="brand-copy">
                <span class="brand-title">Tradesmart Supplies</span>
                <span class="brand-subtitle">Employee Self Service</span>
            </span>
        </a>
    </div>

    {{-- NAVIGATION --}}
    <nav class="sidebar-nav">

        <span class="nav-group-label nav-text">Overview</span>

        <a class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}"
           href="{{ route('portal.dashboard') }}">
            <i class="bi bi-speedometer2 nav-icon"></i>
            <span class="nav-text">Dashboard</span>
        </a>

        <a class="nav-link {{ request()->routeIs('portal.profile.*') ? 'active' : '' }}"
           href="{{ route('portal.profile.show') }}">
            <i class="bi bi-person-vcard nav-icon"></i>
            <span class="nav-text">My Information</span>
        </a>

        @php
            try { $__unread = auth()->user()?->employee ? app(\App\Services\Announcements::class)->unreadCount(auth()->user()->employee) : 0; } catch (\Throwable $e) { $__unread = 0; }
        @endphp
        <a class="nav-link {{ request()->routeIs('portal.announcements.*') ? 'active' : '' }}"
           href="{{ route('portal.announcements.index') }}">
            <i class="bi bi-megaphone nav-icon"></i>
            <span class="nav-text">Announcements</span>
            @if($__unread)<span class="badge rounded-pill bg-primary ms-auto nav-text">{{ $__unread }}</span>@endif
        </a>

        @php
            try { $__rotaMgr = (bool) app(\App\Services\RotaService::class)->managedDepartments(auth()->user()); } catch (\Throwable $e) { $__rotaMgr = false; }
        @endphp
        <a class="nav-link {{ request()->routeIs('portal.shifts.*') ? 'active' : '' }}"
           href="{{ route('portal.shifts.index') }}">
            <i class="bi bi-calendar-week nav-icon"></i>
            <span class="nav-text">My Shifts</span>
        </a>
        @if($__rotaMgr)
        <a class="nav-link {{ request()->routeIs('portal.rota.*') ? 'active' : '' }}"
           href="{{ route('portal.rota.index') }}">
            <i class="bi bi-pencil-square nav-icon"></i>
            <span class="nav-text">Team Rota</span>
        </a>
        @endif

        <a class="nav-link {{ request()->routeIs('portal.attendance.*') ? 'active' : '' }}"
           href="{{ route('portal.attendance.index') }}">
            <i class="bi bi-qr-code nav-icon"></i>
            <span class="nav-text">My Attendance</span>
        </a>

        <a class="nav-link {{ request()->routeIs('portal.documents.*') ? 'active' : '' }}"
           href="{{ route('portal.documents.index') }}">
            <i class="bi bi-folder2-open nav-icon"></i>
            <span class="nav-text">My Documents</span>
        </a>

        <span class="nav-group-label nav-text">Requests</span>

        <a class="nav-link {{ request()->routeIs('portal.leave.*') ? 'active' : '' }}"
           href="{{ route('portal.leave.index') }}">
            <i class="bi bi-calendar-check nav-icon"></i>
            <span class="nav-text">My Leave</span>
        </a>

        @php
            $meEmp = auth()->user()->employee;
            $isSupervisor = $meEmp && ($meEmp->directReports()->exists() || $meEmp->leaveRequestsToReview()->exists());
            $teamPending = $isSupervisor
                ? $meEmp->leaveRequestsToReview()->stage(\App\Models\LeaveRequest::STAGE_SUPERVISOR)->count()
                : 0;
        @endphp
        @if($isSupervisor)
        <a class="nav-link {{ request()->routeIs('portal.team-leave.*') ? 'active' : '' }}"
           href="{{ route('portal.team-leave.index') }}">
            <i class="bi bi-people nav-icon"></i>
            <span class="nav-text">Team Approvals</span>
            @if($teamPending > 0)
                <span class="badge rounded-pill bg-warning text-dark ms-auto">{{ $teamPending }}</span>
            @endif
        </a>
        @endif

        <a class="nav-link {{ request()->routeIs('portal.overtime.*') ? 'active' : '' }}"
           href="{{ route('portal.overtime.index') }}">
            <i class="bi bi-alarm nav-icon"></i>
            <span class="nav-text">My Overtime</span>
        </a>

        <span class="nav-group-label nav-text">Pay</span>

        <a class="nav-link {{ request()->routeIs('portal.payslips.*') ? 'active' : '' }}"
           href="{{ route('portal.payslips.index') }}">
            <i class="bi bi-receipt nav-icon"></i>
            <span class="nav-text">My Payslips</span>
        </a>

        <span class="nav-group-label nav-text">Account</span>

        <a class="nav-link {{ request()->routeIs('portal.password.*') ? 'active' : '' }}"
           href="{{ route('portal.password.edit') }}">
            <i class="bi bi-shield-lock nav-icon"></i>
            <span class="nav-text">Change Password</span>
        </a>

        @if(auth()->user()?->canUseAdmin())
        <a class="nav-link" href="{{ route('dashboard') }}" title="Switch back to the {{ auth()->user()->isManager() ? 'manager' : 'HR' }} admin area" style="color:#0f766e">
            <i class="bi bi-arrow-left-right nav-icon"></i>
            <span class="nav-text">{{ auth()->user()->isManager() ? 'Manager View' : 'HR Admin' }}</span>
        </a>
        @endif

    </nav>

    {{-- USER --}}
    <div class="sidebar-user">

        @php
            $names = explode(' ', auth()->user()->name ?? 'Guest');
            $initials = strtoupper(substr($names[0] ?? '', 0, 1) . substr($names[1] ?? '', 0, 1));
        @endphp

        <div class="avatar-img avatar-sm rounded-circle d-flex align-items-center justify-content-center fw-semibold"
             style="width:36px;height:36px;background:linear-gradient(135deg,#0d6efd,#4f8cff);color:#fff;font-size:12px;">
            {{ $initials }}
        </div>

        <span class="sidebar-footer-text">
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ auth()->user()->employee->position ?? 'Employee' }}</small>
        </span>

        <form action="{{ route('portal.logout') }}" method="POST" class="sidebar-logout text-end text-danger">
            @csrf
            <button type="submit" class="nav-link btn btn-link btn-lg text-end p-0" title="Logout" aria-label="Logout">
                <i class="bi bi-box-arrow-right nav-icon fs-4"></i>
            </button>
        </form>

    </div>

</aside>
