@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-calendar-plus"></i> Apply for Leave</h1>
        <div class="small text-muted">
            @if($employee)
                Your own leave request. It goes through the same approval steps as everyone else's{{ auth()->user()->isManager() ? ', and you can\'t approve it yourself' : '' }}.
            @else
                Your login isn't linked to an employee record, so you can't apply for leave here.
            @endif
        </div>
    </div>
    @if(auth()->user()->hasFullAccess())
        <a href="{{ route('leave.manage.record') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-journal-plus"></i> Record leave for someone else</a>
    @endif
</div>

@if($employee)
    @include('leave.partials.apply-form')
@else
    <div class="hr-card p-4 text-center">
        <i class="bi bi-person-x" style="font-size:2rem;color:var(--admin-muted,#64748b)"></i>
        <h6 class="mt-2 mb-1">No employee record linked to your login</h6>
        <p class="small text-muted mb-3">Leave is taken from an employee record's balance. Ask HR or the Admin to link your login under <strong>Users &amp; Roles</strong>@if(auth()->user()->hasFullAccess()), or use <strong>Record Leave</strong> to enter leave for a member of staff @endif.</p>
        @if(auth()->user()->hasFullAccess())
            <a href="{{ route('leave.manage.record') }}" class="btn btn-primary btn-sm"><i class="bi bi-journal-plus"></i> Record leave</a>
        @endif
    </div>
@endif

@endsection
