@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                <i class="bi bi-gear me-1"></i> Settings
            </h4>
            <small class="text-muted">Manage system configuration and preferences</small>
        </div>

    </div>

    {{-- SETTINGS GRID --}}
    <div class="row g-3">

        {{-- PROFILE SETTINGS --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100 p-3">

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-person-circle fs-5 text-primary"></i>
                    <h6 class="mb-0">Account Settings</h6>
                </div>

                <p class="text-muted small mb-3">
                    Update your personal details and login credentials.
                </p>

                <button class="btn btn-outline-primary btn-sm w-100">
                    Manage Account
                </button>

            </div>

        </div>

        {{-- SECURITY --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100 p-3">

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-shield-lock fs-5 text-success"></i>
                    <h6 class="mb-0">Security</h6>
                </div>

                <p class="text-muted small mb-3">
                    Password, login sessions, and authentication controls.
                </p>

                <button class="btn btn-outline-success btn-sm w-100">
                    Security Settings
                </button>

            </div>

        </div>

        {{-- NOTIFICATIONS --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100 p-3">

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-bell fs-5 text-warning"></i>
                    <h6 class="mb-0">Notifications</h6>
                </div>

                <p class="text-muted small mb-3">
                    Email alerts, system notifications, and updates.
                </p>

                <button class="btn btn-outline-warning btn-sm w-100">
                    Notification Settings
                </button>

            </div>

        </div>

        {{-- SYSTEM SETTINGS --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100 p-3">

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-cpu fs-5 text-info"></i>
                    <h6 class="mb-0">System</h6>
                </div>

                <p class="text-muted small mb-3">
                    Company info, system configuration, and environment settings.
                </p>

                <button class="btn btn-outline-info btn-sm w-100">
                    System Settings
                </button>

            </div>

        </div>

        {{-- USERS / ROLES --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100 p-3">

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-people fs-5 text-dark"></i>
                    <h6 class="mb-0">User Management</h6>
                </div>

                <p class="text-muted small mb-3">
                    Manage users, roles, and permissions.
                </p>

                <button class="btn btn-outline-dark btn-sm w-100">
                    Manage Users
                </button>

            </div>

        </div>

        {{-- BACKUP / DATA --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100 p-3">

                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-database fs-5 text-danger"></i>
                    <h6 class="mb-0">Data & Backup</h6>
                </div>

                <p class="text-muted small mb-3">
                    Backup system data and restore when needed.
                </p>

                <button class="btn btn-outline-danger btn-sm w-100">
                    Backup Tools
                </button>

            </div>

        </div>

    </div>

</div>

@endsection