<nav class="navbar admin-navbar navbar-expand bg-white">

    <div class="container-fluid px-3 px-lg-4">

        <button class="sidebar-toggle"
                type="button"
                data-sidebar-toggle>
            <span></span>
            <span></span>
            <span></span>
        </button>

        <form class="d-none d-md-flex ms-3 flex-grow-1">

            <input class="form-control search-input"
                   type="search"
                   placeholder="Search">

        </form>

        <div class="navbar-actions ms-auto">

            <button class="icon-button"
                    type="button">
                <i class="bi bi-bell"></i>
            </button>

            <div class="dropdown">

                <button class="profile-button dropdown-toggle"
                        data-bs-toggle="dropdown">

                    <img class="avatar-img avatar-sm"
                         src="{{ asset('assets/images/avatar/avatar.jpg') }}">

                    <span class="profile-name d-none d-sm-inline">
                        {{ Auth::user()->name ?? 'Guest' }}
                    </span>

                </button>

                <ul class="dropdown-menu dropdown-menu-end">

                    <li>
                        <a class="dropdown-item"
                           href="{{ route('profile.index') }}">
                            Profile
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item"
                           href="{{ route('settings.index') }}">
                            Settings
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>

                        <form action="{{ route('logout') }}"
                              method="POST">

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