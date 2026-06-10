@extends('layouts.app')

@section('content')

<div class="panel">

    {{-- HEADER --}}
    <div class="panel-header d-flex justify-content-between align-items-center">

        <h4 class="mb-0">
            <i class="bi bi-calendar-check me-2"></i>
            Leave Applications
        </h4>

        <span class="badge bg-primary">
            {{ $applications->count() }} Requests
        </span>

    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle">

            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Leave Info</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>

            <tbody>

                @forelse($applications as $leave)

                @php
                $start = \Carbon\Carbon::parse($leave->date_from);
                $end = \Carbon\Carbon::parse($leave->date_to);
                $days = $start->diffInDays($end) + 1;
                $daysToStart = now()->diffInDays($start, false);
                @endphp

                <tr onclick="window.location='{{ route('leave.show', $leave->id) }}';" style="cursor: pointer;">

                    {{-- EMPLOYEE --}}
                    <td>
                        <div class="fw-semibold">
                            {{ $leave->employee_name }}
                        </div>
                        <small class="text-muted">
                            #{{ $leave->employee_number ?? 'N/A' }}
                        </small>
                    </td>

                    {{-- LEAVE TYPE --}}
                    <td>
                        <span class="badge bg-light text-dark border mb-1">
                            {{ $leave->leave_type }}
                        </span>

                        @if($leave->other_leave_type)
                        <div class="small text-muted">
                            {{ $leave->other_leave_type }}
                        </div>
                        @endif

                        @if($leave->late_application_reason)
                        <div class="small text-muted mt-1" style="font-size: 0.5rem;">
                            Late Application Reason: {{ $leave->late_application_reason }}
                        </div>
                        @endif

                        @if($leave->reason)
                        <div class="small text-muted mt-1" style="font-size: 0.5rem;">
                            Supervisor Decision: {{ $leave->reason }}
                        </div>
                        @endif
                    </td>

                    {{-- DURATION --}}
                    <td>
                        <div class="small">
                            <i class="bi bi-calendar-event me-1"></i>
                            {{ $start->format('d M') }} → {{ $end->format('d M Y') }}
                        </div>

                        <div class="fw-semibold small text-primary mt-1">
                            {{ $days }} {{ $days == 1 ? 'day' : 'days' }}
                        </div>
                    </td>



                    {{-- STATUS --}}
                    <td>
                        <span class="badge px-3 py-2
                            @if($leave->status == 'Approved') bg-success
                            @elseif($leave->status == 'Rejected') bg-danger
                            @else bg-warning text-dark
                            @endif">

                            <i class="bi
                                @if($leave->status == 'Approved') bi-check-circle
                                @elseif($leave->status == 'Rejected') bi-x-circle
                                @else bi-hourglass-split
                                @endif me-1"></i>

                            {{ $leave->status }}
                        </span>

                        @if($leave->supervisor_decision)
                        <div class="small text-muted mt-1" style="font-size: 0.5rem;">
                            Supervisor Decision: {{ $leave->supervisor_decision }}
                        </div>
                        @endif
                    </td>

                    {{-- ACTION --}}
                    <td>
                        <div class="d-flex gap-1 align-items-center">

                            {{-- VIEW --}}
                            <a href="{{ route('leave.show',$leave->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>

                            {{-- PRINT --}}
                            <button type="button"
                                onclick="window.open('{{ route('leave.show',$leave->id) }}?print=1','_blank')"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-printer"></i>
                            </button>

                            {{-- DELETE --}}
                            <form id="delete-form-{{ $leave->id }}" action="{{ route('leave.destroy', $leave->id) }}"
                                method="POST" class="m-0">

                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to delete this leave application?');">
                                    <i class="bi bi-trash"></i>
                                </button>

                            </form>

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

</div>

@endsection