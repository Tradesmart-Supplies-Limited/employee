@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                <i class="bi bi-person-circle me-1"></i> My Profile
            </h4>
            <small class="text-muted">Account & personal information</small>
        </div>

        <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer"></i> Print
        </button>

    </div>

    <div class="row g-3">

        {{-- LEFT: PROFILE CARD --}}
        <div class="col-md-4">

            <div class="card shadow-sm border-0 p-3 text-center">

                @php
                $names = explode(' ', auth()->user()->name);
                $initials = strtoupper(
                substr($names[0] ?? '', 0, 1) .
                substr($names[1] ?? '', 0, 1)
                );
                @endphp

                <div class="rounded-circle border d-flex align-items-center justify-content-center mx-auto mb-3" style="
        width:90px;
        height:90px;
        font-size:28px;
        font-weight:700;
        background:#f8f9fa;
        color:#495057;
     ">
                    {{ $initials }}
                </div>

                {{-- NAME --}}
                <h5 class="mb-0">{{ auth()->user()->name }}</h5>

                <small class="text-muted">
                    {{ auth()->user()->email }}
                </small>

                <hr>

                {{-- QUICK INFO --}}
                <div class="text-start small">

                    <p class="mb-2">
                        <i class="bi bi-shield-lock me-1 text-primary"></i>
                        Role: {{ auth()->user()->role ?? 'User' }}
                    </p>

                    <p class="mb-2">
                        <i class="bi bi-calendar-check me-1 text-success"></i>
                        Joined: {{ auth()->user()->created_at?->format('d M Y') }}
                    </p>

                    <p class="mb-0">
                        <i class="bi bi-circle-fill me-1 text-success small"></i>
                        Status: Active
                    </p>

                </div>

            </div>

        </div>

        {{-- RIGHT: DETAILS --}}
        <div class="col-md-8">

            <div class="card shadow-sm border-0 p-3">

                <h6 class="mb-3">
                    <i class="bi bi-person-lines-fill me-1"></i> Account Details
                </h6>

                <div class="row g-3 small">

                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <div class="text-muted">Full Name</div>
                            <div class="fw-semibold">{{ auth()->user()->name }}</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <div class="text-muted">Email</div>
                            <div class="fw-semibold">{{ auth()->user()->email }}</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <div class="text-muted">Role</div>
                            <div class="fw-semibold">{{ auth()->user()->role ?? 'User' }}</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <div class="text-muted">Account Created</div>
                            <div class="fw-semibold">
                                {{ auth()->user()->created_at?->format('d M Y H:i') }}
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            {{-- SECURITY CARD --}}
            <div class="card shadow-sm border-0 p-3 mt-3">

                <h6 class="mb-3">
                    <i class="bi bi-shield-lock me-1"></i> Security
                </h6>

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <div class="fw-semibold">Password</div>
                        <small class="text-muted">Last updated recently</small>
                    </div>

                    <button class="btn btn-outline-primary btn-sm">
                        Change Password
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection