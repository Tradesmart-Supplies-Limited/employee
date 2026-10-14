@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1"><i class="bi bi-alarm me-1"></i> Overtime Requests</h4>
        <small class="text-muted">Review overtime submitted by employees through the portal</small>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    @foreach(['Pending', 'Approved', 'Rejected', 'All'] as $tab)
        <li class="nav-item">
            <a class="nav-link {{ $status === $tab ? 'active' : '' }}"
               href="{{ route('overtime.index', ['status' => $tab]) }}">
                {{ $tab }}
                @if(isset($counts[$tab])) <span class="badge bg-light text-dark ms-1">{{ $counts[$tab] }}</span> @endif
            </a>
        </li>
    @endforeach
</ul>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="max-width:260px" placeholder="Search name or EIN">
            <button class="btn btn-sm btn-outline-secondary">Search</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Employee</th><th>Date</th><th>Time</th><th>Hours</th><th>Day type</th><th>Reason</th><th>Status</th><th class="text-end">Action</th></tr>
            </thead>
            <tbody>
                @forelse($requests as $o)
                    <tr>
                        <td>
                            {{ $o->employee->full_name ?? 'Deleted employee' }}
                            <small class="d-block text-muted">{{ $o->employee->employee_id ?? '' }}</small>
                        </td>
                        <td>{{ $o->work_date->format('d M Y') }}</td>
                        <td>{{ substr($o->start_time, 0, 5) }} &ndash; {{ substr($o->end_time, 0, 5) }}</td>
                        <td>{{ $o->hours + 0 }}</td>
                        <td>{{ $o->day_type_label }}</td>
                        <td style="max-width:240px" title="{{ $o->reason }}" class="text-truncate">{{ $o->reason }}</td>
                        <td>
                            @include('portal.partials.status-badge', ['status' => $o->status])
                            @if($o->reviewer)
                                <small class="d-block text-muted">by {{ $o->reviewer->name }}</small>
                            @endif
                            @if($o->review_comment)
                                <small class="d-block text-muted">{{ $o->review_comment }}</small>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if($o->status === 'Pending')
                                <form method="POST" action="{{ route('overtime.approve', $o) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal" data-bs-target="#rejectModal"
                                        data-action="{{ route('overtime.reject', $o) }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No overtime requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())<div class="card-footer bg-transparent">{{ $requests->links() }}</div>@endif
</div>

@push('modals')
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rejectForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reject overtime request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Reason (shown to the employee)</label>
                <textarea name="review_comment" rows="3" class="form-control" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger">Reject</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
// The modal markup is rendered after this script (@stack order), so wait for the DOM.
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('rejectModal').addEventListener('show.bs.modal', e => {
        document.getElementById('rejectForm').action = e.relatedTarget.dataset.action;
    });
});
</script>
@endpush

@endsection
