@extends('layouts.portal')
@section('title', 'Team Approvals')

@section('content')

@include('leave.partials.tracker-styles')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1"><i class="bi bi-people me-1"></i> Team Approvals</h4>
        <small class="text-muted">Leave requests from people who report to you. Your recommendation goes to HR for the final decision.</small>
    </div>
</div>

{{-- AWAITING --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex align-items-center gap-2">
        <strong>Waiting for your review</strong>
        <span class="badge bg-warning text-dark">{{ $awaiting->count() }}</span>
    </div>
    <div class="list-group list-group-flush">
        @forelse($awaiting as $l)
            <a href="{{ route('portal.team-leave.show', $l) }}" class="list-group-item list-group-item-action py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">{{ $l->employee_name }}</div>
                        <small class="text-muted">
                            {{ $l->leave_type }} · {{ $l->date_from->format('d M') }} – {{ $l->date_to->format('d M Y') }}
                            · <strong>{{ $l->total_days }} day(s)</strong>
                            · submitted {{ $l->created_at->diffForHumans() }}
                        </small>
                        @if($l->date_from->isPast())
                            <span class="badge bg-danger-subtle text-danger ms-1">Starts{{ $l->date_from->isToday() ? ' today' : 'ed' }}</span>
                        @elseif(now()->startOfDay()->diffInDays($l->date_from) <= 5)
                            <span class="badge bg-warning-subtle text-warning ms-1">Starts {{ $l->date_from->diffForHumans() }}</span>
                        @endif
                    </div>
                    <span class="btn btn-sm btn-primary">Review <i class="bi bi-chevron-right"></i></span>
                </div>
            </a>
        @empty
            <div class="list-group-item text-muted py-4 text-center">
                <i class="bi bi-check2-circle fs-4 d-block mb-1 text-success"></i>
                You're all caught up.
            </div>
        @endforelse
    </div>
</div>

<div class="row g-3">
    {{-- HISTORY --}}
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent"><strong>Reviewed</strong></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Employee</th><th>Leave</th><th>Your review</th><th style="min-width:170px">Progress</th></tr></thead>
                    <tbody>
                        @forelse($history as $l)
                            <tr onclick="window.location='{{ route('portal.team-leave.show', $l) }}'" style="cursor:pointer">
                                <td class="fw-semibold">{{ $l->employee_name }}</td>
                                <td class="small">{{ $l->leave_type }}<br><span class="text-muted">{{ $l->date_from->format('d M') }} – {{ $l->date_to->format('d M') }}</span></td>
                                <td class="small">
                                    @if($l->supervisor_decision)
                                        <span class="{{ $l->supervisor_decision === 'Approved' ? 'text-success' : 'text-danger' }}">
                                            <i class="bi {{ $l->supervisor_decision === 'Approved' ? 'bi-hand-thumbs-up' : 'bi-hand-thumbs-down' }}"></i>
                                            {{ $l->supervisor_decision === 'Approved' ? 'Recommended' : 'Not recommended' }}
                                        </span>
                                    @else
                                        <span class="text-muted">HR decided directly</span>
                                    @endif
                                </td>
                                <td>@include('leave.partials.mini-tracker', ['leave' => $l])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Nothing reviewed yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($history->hasPages())<div class="card-footer bg-transparent">{{ $history->links() }}</div>@endif
        </div>
    </div>

    {{-- TEAM --}}
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent"><strong>My team</strong> <small class="text-muted">({{ $team->count() }})</small></div>
            <ul class="list-group list-group-flush">
                @forelse($team as $member)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            {{ $member->full_name }}
                            <small class="text-muted d-block">{{ $member->position ?? '—' }}</small>
                        </span>
                        <span class="badge bg-light text-dark border" title="Leave balance">
                            {{ rtrim(rtrim(number_format((float) $member->leave_balance, 1), '0'), '.') }} d
                        </span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No one reports to you yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@endsection
