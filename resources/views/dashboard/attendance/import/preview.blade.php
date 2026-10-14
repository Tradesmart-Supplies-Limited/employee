@extends('layouts.app')
@section('title', 'Check timesheet')

@section('content')
@php
    use App\Models\AttendanceRecord as AR;
    $s = $analysis['summary'];
    $rows = collect($analysis['rows'])->filter(fn ($r) => match ($filter) {
        'create' => $r['action'] === 'create', 'update' => $r['action'] === 'update', 'error' => $r['action'] === 'error',
        'warning' => ! empty($r['warnings']), 'unchanged' => $r['action'] === 'unchanged', default => true,
    });
    $meta = [
        'create'    => ['New', '#dcfce7', '#166534'],
        'update'    => ['Update', '#e0f2fe', '#075985'],
        'unchanged' => ['No change', '#f1f5f9', '#475569'],
        'error'     => ['Can\'t import', '#fee2e2', '#991b1b'],
    ];
    $link = fn ($show) => route('attendance.import.show', ['token' => $token, 'mode' => $mode, 'show' => $show]);
    $will = $s['create'] + $s['update'];
@endphp

@include('dashboard.attendance.partials.nav', ['title' => 'Check the timesheet', 'subtitle' => '<i class="bi bi-file-earmark-spreadsheet"></i> ' . e($stored['filename']) . ' · ' . $s['total'] . ' filled row(s)' . ($s['skip'] ? ' · ' . $s['skip'] . ' empty row(s) ignored' : '')])

<style>
.tp-tiles { display: grid; grid-template-columns: repeat(5, 1fr); gap: .6rem; margin-bottom: 1rem; }
.tp-tile { display: block; text-decoration: none; color: inherit; border: 1px solid var(--admin-border, #e2e8f0); border-radius: 12px; padding: .7rem .9rem; background: var(--admin-surface, #fff); }
.tp-tile.active { border-color: var(--c); box-shadow: inset 0 0 0 1px var(--c); }
.tp-tile .v { font-size: 1.5rem; font-weight: 800; color: var(--c); }
.tp-tile .l { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--admin-muted, #64748b); }
.tp-msg { font-size: .74rem; } .tp-msg.err { color: #b91c1c; } .tp-msg.warn { color: #b45309; }
.tp-old { color: #94a3b8; text-decoration: line-through; font-size: .74rem; }
.tp-commit { position: sticky; bottom: 0; background: var(--admin-surface, #fff); border-top: 1px solid var(--admin-border, #e2e8f0); padding: .9rem 1.1rem; border-radius: 0 0 14px 14px; z-index: 4; }
@media (max-width: 768px) { .tp-tiles { grid-template-columns: repeat(2, 1fr); } }
</style>

@if($stored['unknown'])
    <div class="alert alert-light border small py-2"><i class="bi bi-info-circle"></i> Ignored columns: <strong>{{ implode(', ', $stored['unknown']) }}</strong></div>
@endif

<div class="tp-tiles">
    <a class="tp-tile {{ $filter === 'create' ? 'active' : '' }}" style="--c:#16a34a" href="{{ $link('create') }}"><div class="l">New days</div><div class="v">{{ $s['create'] }}</div></a>
    <a class="tp-tile {{ $filter === 'update' ? 'active' : '' }}" style="--c:#0284c7" href="{{ $link('update') }}"><div class="l">Will change</div><div class="v">{{ $s['update'] }}</div></a>
    <a class="tp-tile {{ $filter === 'unchanged' ? 'active' : '' }}" style="--c:#64748b" href="{{ $link('unchanged') }}"><div class="l">Already the same</div><div class="v">{{ $s['unchanged'] }}</div></a>
    <a class="tp-tile {{ $filter === 'warning' ? 'active' : '' }}" style="--c:#d97706" href="{{ $link('warning') }}"><div class="l">With warnings</div><div class="v">{{ $s['warnings'] }}</div></a>
    <a class="tp-tile {{ $filter === 'error' ? 'active' : '' }}" style="--c:#dc2626" href="{{ $link('error') }}"><div class="l">Can't import</div><div class="v">{{ $s['error'] }}</div></a>
</div>

<div class="at-card">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom flex-wrap gap-2">
        <span class="small at-muted">Showing {{ $rows->count() }} of {{ $s['total'] }} · <a href="{{ $link('all') }}">show all</a></span>
        @if($s['error'])<span class="small text-danger"><i class="bi bi-exclamation-triangle"></i> Red rows are skipped. Fix them in the file and upload again, or import the rest now.</span>@endif
    </div>
    <div class="table-responsive" style="max-height:60vh; overflow:auto">
        <table class="table table-hover at-table align-middle mb-0">
            <thead class="table-light" style="position:sticky; top:0; z-index:2"><tr><th>Row</th><th>Result</th><th>Employee</th><th>Date</th><th>In</th><th>Out</th><th>Status</th><th>Notes / problems</th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    @php [$lbl, $bg, $fg] = $meta[$r['action']] ?? $meta['unchanged']; $b = $r['before']; @endphp
                    <tr>
                        <td class="at-muted">{{ $r['line'] }}</td>
                        <td><span class="at-chip" style="--bg:{{ $bg }};--c:{{ $fg }}">{{ $lbl }}</span></td>
                        <td class="text-nowrap"><strong>{{ $r['name'] ?: '—' }}</strong><div class="small at-muted">{{ $r['ein'] }}</div></td>
                        <td class="text-nowrap">{{ $r['date'] ? \Carbon\Carbon::parse($r['date'])->format('D d M Y') : '—' }}</td>
                        <td>{{ $r['in'] ? substr($r['in'], 0, 5) : '' }}@if($b && $b['in'] && $b['in'] !== substr((string) $r['in'], 0, 5))<div class="tp-old">{{ $b['in'] }}</div>@endif</td>
                        <td>{{ $r['out'] ? substr($r['out'], 0, 5) : '' }}@if($b && $b['out'] && $b['out'] !== substr((string) $r['out'], 0, 5))<div class="tp-old">{{ $b['out'] }}</div>@endif</td>
                        <td>@if($r['status'])@include('dashboard.attendance.partials.chip', ['status' => $r['status']])@elseif($r['in'])<span class="small at-muted">Auto</span>@endif</td>
                        <td style="min-width:220px">
                            @if($r['notes'])<div class="small">{{ $r['notes'] }}</div>@endif
                            @foreach($r['errors'] as $m)<div class="tp-msg err"><i class="bi bi-x-circle"></i> {{ $m }}</div>@endforeach
                            @foreach($r['warnings'] as $m)<div class="tp-msg warn"><i class="bi bi-exclamation-triangle"></i> {{ $m }}</div>@endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center at-muted py-4">Nothing in this view.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('attendance.import.commit', $token) }}" class="tp-commit" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
        @csrf
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <label class="form-label small fw-semibold mb-1">Days that already have times</label>
                <select name="mode" class="form-select form-select-sm" onchange="location.href='{{ route('attendance.import.show', ['token' => $token]) }}?mode=' + this.value">
                    <option value="overwrite" @selected($mode === 'overwrite')>Use the file's times (overwrite)</option>
                    <option value="fill" @selected($mode === 'fill')>Keep what's there, only fill blanks</option>
                </select>
            </div>
            <div class="col-md-6 text-md-end">
                <button type="submit" class="btn btn-success px-4" @disabled($will === 0)><i class="bi bi-check2-all"></i> Import {{ $will }} day(s)</button>
                <div class="small at-muted mt-1">{{ $s['create'] }} new · {{ $s['update'] }} changed · {{ $s['error'] }} skipped</div>
            </div>
        </div>
    </form>
</div>
@endsection
