@extends('layouts.app')

@section('content')
<div class="container-fluid">

    {{-- ================= HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">HR Dashboard</h4>
            <small class="text-muted">System overview and workforce insights</small>
        </div>

        <div class="text-muted small">
            Last updated: {{ now()->format('D, d M Y H:i') }}
        </div>
    </div>

    {{-- ================= KPI CARDS ================= --}}
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted">Employees</small>
                        <h3 class="mb-0">{{ $stats['employees'] ?? 0 }}</h3>
                    </div>
                    <i class="bi bi-people fs-3 text-primary"></i>
                </div>
                <small class="text-success">+3 this month</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted">Active Contracts</small>
                        <h3 class="mb-0">128</h3>
                    </div>
                    <i class="bi bi-file-earmark-text fs-3 text-success"></i>
                </div>
                <small class="text-muted">Current workforce</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted">Pending Leave</small>
                        <h3 class="mb-0">14</h3>
                    </div>
                    <i class="bi bi-calendar-x fs-3 text-warning"></i>
                </div>
                <small class="text-warning">Needs approval</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 p-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted">Departments</small>
                        <h3 class="mb-0">{{ $stats['departments'] ?? 0 }}</h3>
                    </div>
                    <i class="bi bi-diagram-3 fs-3 text-info"></i>
                </div>
                <small class="text-muted">Organizational units</small>
            </div>
        </div>

    </div>

    {{-- ================= MAIN GRID ================= --}}
    <div class="row g-3">

        {{-- LEFT: HR ALERTS --}}
        <div class="col-md-6">

            <div class="card shadow-sm border-0 p-3">

                <h6 class="mb-3">
                    <i class="bi bi-bell me-1"></i> HR Alerts
                </h6>

                <div class="list-group list-group-flush">

                    <div class="list-group-item d-flex justify-content-between">
                        <span>3 employees contract expiring</span>
                        <span class="badge bg-danger">Urgent</span>
                    </div>

                    <div class="list-group-item d-flex justify-content-between">
                        <span>5 pending leave approvals</span>
                        <span class="badge bg-warning">Pending</span>
                    </div>

                    <div class="list-group-item d-flex justify-content-between">
                        <span>2 new employee onboarding</span>
                        <span class="badge bg-primary">New</span>
                    </div>

                </div>

            </div>

        </div>

        {{-- RIGHT: DEPARTMENT SNAPSHOT --}}
        <div class="col-md-6">

            <div class="card shadow-sm border-0 p-3">

                <h6 class="mb-3">
                    <i class="bi bi-pie-chart me-1"></i> Department Overview
                </h6>

                <div class="mb-2 d-flex justify-content-between">
                    <span>Operations</span>
                    <span>45</span>
                </div>

                <div class="mb-2 d-flex justify-content-between">
                    <span>Finance</span>
                    <span>18</span>
                </div>

                <div class="mb-2 d-flex justify-content-between">
                    <span>IT</span>
                    <span>12</span>
                </div>

                <div class="mb-2 d-flex justify-content-between">
                    <span>HR</span>
                    <span>6</span>
                </div>

            </div>

        </div>

    </div>

    {{-- ================= RECENT ACTIVITY ================= --}}
    <div class="row mt-3">

        <div class="col-12">

            <div class="card shadow-sm border-0 p-3">

                <h6 class="mb-3">
                    <i class="bi bi-clock-history me-1"></i> Recent Activity
                </h6>

                <div class="timeline">

                    <div class="mb-2">
                        <small class="text-muted">2 mins ago</small><br>
                        Employee <strong>John Doe</strong> was added
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">1 hour ago</small><br>
                        Leave request approved for <strong>Sarah M.</strong>
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Yesterday</small><br>
                        Payroll processed successfully
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
@endsection