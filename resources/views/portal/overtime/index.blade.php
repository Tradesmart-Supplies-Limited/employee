@extends('layouts.portal')
@section('title', 'My Overtime')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1"><i class="bi bi-alarm me-1"></i> My Overtime</h4>
        <small class="text-muted">Overtime you have claimed and its approval status</small>
    </div>
    <a href="{{ route('portal.overtime.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Request Overtime
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body">
        <small class="text-muted">Approved this month</small>
        <h3 class="mb-0">{{ $summary['approved_month'] + 0 }} <small class="fs-6 text-muted">hrs</small></h3>
    </div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body">
        <small class="text-muted">Approved this year</small>
        <h3 class="mb-0">{{ $summary['approved_year'] + 0 }} <small class="fs-6 text-muted">hrs</small></h3>
    </div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body">
        <small class="text-muted">Pending</small>
        <h3 class="mb-0">{{ $summary['pending'] }}</h3>
    </div></div></div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent">
        <form method="GET">
            <select name="status" class="form-select form-select-sm" style="max-width:180px" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(['Pending','Approved','Rejected'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Date</th><th>Time</th><th>Hours</th><th>Day type</th><th>Reason</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($requests as $o)
                    <tr>
                        <td>{{ $o->work_date->format('d M Y') }}</td>
                        <td>{{ substr($o->start_time, 0, 5) }} &ndash; {{ substr($o->end_time, 0, 5) }}</td>
                        <td>{{ $o->hours + 0 }}</td>
                        <td>{{ $o->day_type_label }}</td>
                        <td style="max-width:240px" class="text-truncate" title="{{ $o->reason }}">{{ $o->reason }}</td>
                        <td>
                            @include('portal.partials.status-badge', ['status' => $o->status])
                            @if($o->status === 'Rejected' && $o->review_comment)
                                <small class="d-block text-muted">{{ $o->review_comment }}</small>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($o->status === 'Pending')
                                <form method="POST" action="{{ route('portal.overtime.destroy', $o) }}"
                                      onsubmit="return confirm('Withdraw this overtime request?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Withdraw</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No overtime requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())<div class="card-footer bg-transparent">{{ $requests->links() }}</div>@endif
</div>

@endsection
