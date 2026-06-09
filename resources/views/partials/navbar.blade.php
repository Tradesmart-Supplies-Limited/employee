<nav class="navbar admin-navbar navbar-expand bg-white">

    <div class="container-fluid px-3 px-lg-4">

        <button class="sidebar-toggle" type="button" data-sidebar-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <form class="d-none d-md-flex ms-3 flex-grow-1">

            <input class="form-control search-input" type="search" placeholder="Search">

        </form>

        <div class="navbar-actions ms-auto">

        {{-- Theme Toggle --}}
    
            <button class="icon-button" type="button" data-theme-toggle>
                <i class="bi bi-moon-stars " data-theme-icon></i>
            </button>

            <div class="dropdown" >

                <button class="profile-button dropdown-toggle" data-bs-toggle="dropdown">

                    @php
                    $names = explode(' ', auth()->user()->name ?? 'Guest');

                    $initials = strtoupper(
                    substr($names[0] ?? '', 0, 1) .
                    substr($names[1] ?? '', 0, 1)
                    );
                    @endphp

                    <div class="avatar-img avatar-sm rounded-circle d-flex align-items-center justify-content-center fw-semibold"
                        style="
        width:36px;
        height:36px;
        background:linear-gradient(135deg,#0d6efd,#4f8cff);
        color:#fff;
        font-size:12px;
        flex-shrink:0;
     ">
                        {{ $initials }}
                    </div>

                    <span class="profile-name d-none d-sm-inline">
                        <!-- {{ auth()->user()->name }} -->
                    </span>

                </button>

                <ul class="dropdown-menu dropdown-menu-end">

                    <li>
                        <a class="dropdown-item" href="{{ route('profile.index') }}">
                            Profile
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item" href="{{ route('settings.index') }}">
                            Settings
                        </a>
                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>

                        <form action="{{ route('logout') }}" method="POST">

                            @csrf

                            <button class="dropdown-item">
                                Logout
                            </button>

                        </form>

                    </li>

                </ul>

            </div>

        </div>

    </div>

</nav>