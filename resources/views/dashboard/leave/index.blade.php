@extends('layouts.app')

@section('content')

@include('leave.partials.tracker-styles')

<style>
.lv-tabs { display: flex; gap: .5rem; flex-wrap: wrap; }
.lv-tab {
    display: inline-flex; align-items: center; gap: .45rem;
    padding: .45rem .85rem; border-radius: 999px;
    border: 1px solid var(--admin-border, #e2e8f0);
    color: var(--admin-text, #0f172a); text-decoration: none;
    font-size: .82rem; font-weight: 600; background: var(--admin-surface, #fff);
}
.lv-tab:hover { border-color: #185FA5; color: #185FA5; }
.lv-tab.active { background: #185FA5; border-color: #185FA5; color: #fff; }
.lv-tab .count { font-size: .72rem; padding: .05rem .45rem; border-radius: 999px; background: rgba(0,0,0,.07); }
.lv-tab.active .count { background: rgba(255,255,255,.25); }
.lv-tab .dot { width: 8px; height: 8px; border-radius: 50%; }
</style>

<div class="panel">

    {{-- HEADER --}}
    <div class="panel-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="mb-0">
            <i class="bi bi-calendar-check me-2"></i>
            Leave Applications
        </h4>

        <form method="GET" class="d-flex gap-2">
            @if($stage)<input type="hidden" name="stage" value="{{ $stage }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name or EIN" style="max-width:220px">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    {{-- STAGE TABS --}}
    @php
        $tabs = [
            'all'                                        => ['All', '#64748b'],
            \App\Models\LeaveRequest::STAGE_SUPERVISOR   => ['Awaiting supervisor', '#f59e0b'],
            \App\Models\LeaveRequest::STAGE_HR           => ['Awaiting HR', '#0ea5e9'],
            \App\Models\LeaveRequest::STAGE_APPROVED     => ['Approved', '#16a34a'],
            \App\Models\LeaveRequest::STAGE_REJECTED     => ['Rejected', '#dc2626'],
        ];
    @endphp
    <div class="lv-tabs mb-3">
        @foreach($tabs as $key => [$label, $color])
            <a href="{{ route('leave.index', array_filter(['stage' => $key === 'all' ? null : $key, 'q' => request('q')])) }}"
               class="lv-tab {{ ($stage ?: 'all') === $key ? 'active' : '' }}">
                <span class="dot" style="background:{{ $color }}"></span>
                {{ $label }}
                <span class="count">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle">

            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Leave</th>
                    <th>Dates</th>
                    <th>Supervisor</th>
                    <th style="min-width:180px">Progress</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>

            <tbody>

                @forelse($applications as $leave)

                <tr onclick="window.location='{{ route('leave.show', $leave->id) }}';" style="cursor: pointer;">

                    {{-- EMPLOYEE --}}
                    <td>
                        <div class="fw-semibold">{{ $leave->employee_name }}</div>
                        <small class="text-muted">#{{ $leave->employee_number ?? 'N/A' }}</small>
                        @if($leave->employee)
                            <div class="small text-muted">Balance: {{ rtrim(rtrim(number_format((float) $leave->employee->leave_balance, 1), '0'), '.') }} d</div>
                        @endif
                    </td>

                    {{-- LEAVE TYPE --}}
                    <td>
                        <span class="badge bg-light text-dark border">{{ $leave->leave_type }}</span>
                        @if($leave->other_leave_type)
                            <div class="small text-muted">{{ $leave->other_leave_type }}</div>
                        @endif
                        @if($leave->late_application_reason)
                            <div class="small text-danger mt-1"><i class="bi bi-clock-history"></i> Late application</div>
                        @endif
                    </td>

                    {{-- DURATION --}}
                    <td>
                        <div class="small">{{ $leave->date_from->format('d M') }} → {{ $leave->date_to->format('d M Y') }}</div>
                        <div class="fw-semibold small text-primary">{{ $leave->total_days }} {{ $leave->total_days == 1 ? 'day' : 'days' }}</div>
                    </td>

                    {{-- SUPERVISOR --}}
                    <td class="small">
                        {{ $leave->assigned_supervisor_name ?? '—' }}
                        @if($leave->supervisor_decision)
                            <div class="{{ $leave->supervisor_decision === 'Approved' ? 'text-success' : 'text-danger' }}">
                                <i class="bi {{ $leave->supervisor_decision === 'Approved' ? 'bi-hand-thumbs-up' : 'bi-hand-thumbs-down' }}"></i>
                                {{ $leave->supervisor_decision === 'Approved' ? 'Recommended' : 'Not recommended' }}
                            </div>
                        @endif
                    </td>

                    {{-- PROGRESS --}}
                    <td>@include('leave.partials.mini-tracker', ['leave' => $leave])</td>

                    {{-- ACTION --}}
                    <td onclick="event.stopPropagation()">
                        <div class="d-flex gap-1 align-items-center justify-content-end">
                            <a href="{{ route('leave.show', $leave->id) }}"
                               class="btn btn-sm {{ $leave->isFinal() ? 'btn-outline-primary' : 'btn-primary' }}">
                                {{ $leave->isFinal() ? 'View' : 'Review' }}
                            </a>
                            <button type="button"
                                onclick="window.open('{{ route('leave.print', $leave->id) }}','_blank')"
                                class="btn btn-sm btn-outline-secondary" title="Print">
                                <i class="bi bi-printer"></i>
                            </button>
                            @if(auth()->user()?->hasFullAccess())
                            <form action="{{ route('leave.destroy', $leave->id) }}" method="POST" class="m-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                                    onclick="return confirm('Delete this leave application?{{ $leave->status === 'Approved' && (float) $leave->balance_deducted > 0 ? ' The deducted days will be returned to the employee\'s balance.' : '' }}');">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        No leave applications found
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    @if($applications->hasPages())
        <div class="mt-3">{{ $applications->links() }}</div>
    @endif

</div>

@endsection
