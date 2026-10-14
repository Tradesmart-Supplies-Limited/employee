@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <a href="{{ route('leave.manage.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Leave reports</a>
        <h1 class="h4 fw-bold mb-0 mt-1"><i class="bi bi-flag"></i> Public Holidays · {{ $year }}</h1>
        <div class="small text-muted">These days are not counted as leave (when "public holidays" is ticked on a request). Changes apply to new requests only.</div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('leave.manage.holidays', ['year' => $year - 1]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i> {{ $year - 1 }}</a>
        <a href="{{ route('leave.manage.holidays', ['year' => $year + 1]) }}" class="btn btn-sm btn-outline-secondary">{{ $year + 1 }} <i class="bi bi-chevron-right"></i></a>
        <form method="POST" action="{{ route('leave.manage.holidays.generate') }}">
            @csrf <input type="hidden" name="year" value="{{ $year }}">
            <button class="btn btn-sm btn-outline-primary" title="Adds any missing official Zambian holidays for this year"><i class="bi bi-magic"></i> Reload Zambian calendar</button>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Holiday</th><th>Source</th><th>Counted as leave?</th><th></th></tr></thead>
                    <tbody>
                        @forelse($holidays as $h)
                            <tr class="{{ $h->is_active ? '' : 'text-muted' }}">
                                <td class="text-nowrap">
                                    <strong>{{ $h->date->format('d M') }}</strong> <small class="text-muted">{{ $h->date->format('l') }}</small>
                                    @if($h->date->isToday())<span class="badge bg-primary ms-1">Today</span>@elseif($h->date->isPast())<small class="text-muted"> · past</small>@endif
                                </td>
                                <td>{!! $h->is_active ? e($h->name) : '<s>' . e($h->name) . '</s>' !!}</td>
                                <td><span class="badge {{ $h->source === 'generated' ? 'bg-light text-dark border' : 'bg-info-subtle text-info-emphasis' }}">{{ $h->source === 'generated' ? 'Zambian calendar' : 'Added by HR' }}</span></td>
                                <td>{!! $h->is_active ? '<span class="text-success"><i class="bi bi-check-circle"></i> No, skipped</span>' : '<span class="text-muted">Yes (switched off)</span>' !!}</td>
                                <td class="text-end text-nowrap">
                                    <form method="POST" action="{{ route('leave.manage.holidays.toggle', $h) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary">{{ $h->is_active ? 'Switch off' : 'Switch on' }}</button>
                                    </form>
                                    @if($h->source === 'manual')
                                        <form method="POST" action="{{ route('leave.manage.holidays.destroy', $h) }}" class="d-inline" onsubmit="return confirm('Delete {{ $h->name }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No holidays for {{ $year }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h6 class="fw-bold"><i class="bi bi-plus-circle"></i> Add a declared holiday</h6>
                <p class="small text-muted">For days the government declares at short notice (elections, national mourning, etc.).</p>
                <form method="POST" action="{{ route('leave.manage.holidays.store') }}">
                    @csrf
                    <input type="date" name="date" class="form-control form-control-sm mb-2" value="{{ old('date') }}" required>
                    <input type="text" name="name" class="form-control form-control-sm mb-2" placeholder="e.g. National Day of Mourning" value="{{ old('name') }}" required maxlength="150">
                    <button class="btn btn-primary btn-sm w-100">Add holiday</button>
                </form>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body small text-muted">
                <h6 class="fw-bold text-body"><i class="bi bi-info-circle"></i> How the calendar works</h6>
                <ul class="ps-3 mb-0">
                    <li>The official Zambian holidays load automatically for each year, including Easter dates.</li>
                    <li>A holiday on a Sunday is observed on the Monday, as the Public Holidays Act requires.</li>
                    <li>From 2026 the National Day of Prayer is on 29 December (previously 18 October).</li>
                    <li>Switching a day off makes it count as a leave day again on new requests.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
