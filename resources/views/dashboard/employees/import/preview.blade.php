@extends('layouts.app')

@section('content')

@php
    $s = $analysis['summary'];
    $rows = collect($analysis['rows'])->filter(fn ($r) => match ($filter) {
        'create' => $r['action'] === 'create', 'update' => $r['action'] === 'update',
        'error' => $r['action'] === 'error', 'warning' => ! empty($r['warnings']),
        'other' => in_array($r['action'], ['unchanged', 'skip'], true), default => true,
    });
    $actionMeta = [
        'create'    => ['New', '#dcfce7', '#166534', 'bi-person-plus'],
        'update'    => ['Update', '#e0f2fe', '#075985', 'bi-arrow-repeat'],
        'unchanged' => ['No change', '#f1f5f9', '#475569', 'bi-dash-circle'],
        'skip'      => ['Skip (exists)', '#f1f5f9', '#475569', 'bi-skip-forward'],
        'error'     => ['Can\'t import', '#fee2e2', '#991b1b', 'bi-x-octagon'],
    ];
    $show = ['position', 'department', 'primary_phone', 'company_email', 'personal_email', 'nrc_no', 'contract_end', 'supervisor'];
@endphp

<style>
.ip-steps { display: flex; gap: .6rem; margin-bottom: 1rem; flex-wrap: wrap; }
.ip-step { flex: 1 1 180px; display: flex; gap: .7rem; align-items: center; padding: .7rem 1rem; border-radius: 12px; border: 1px solid var(--admin-border, #e2e8f0); background: var(--admin-surface, #fff); font-size: .85rem; }
.ip-step .n { width: 28px; height: 28px; border-radius: 50%; background: var(--admin-surface-soft, #f1f5f9); font-weight: 800; display: flex; align-items: center; justify-content: center; }
.ip-step.done .n { background: #16a34a; color: #fff; } .ip-step.active { border-color: var(--brand, #185FA5); } .ip-step.active .n { background: var(--brand, #185FA5); color: #fff; }
.ip-tiles { display: grid; grid-template-columns: repeat(5, 1fr); gap: .6rem; margin-bottom: 1rem; }
.ip-tile { display: block; text-decoration: none; color: inherit; border: 1px solid var(--admin-border, #e2e8f0); border-radius: 12px; padding: .7rem .9rem; background: var(--admin-surface, #fff); }
.ip-tile.active { border-color: var(--c); box-shadow: inset 0 0 0 1px var(--c); }
.ip-tile .v { font-size: 1.5rem; font-weight: 800; color: var(--c); } .ip-tile .l { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--admin-muted, #64748b); }
.ip-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; }
.ip-table { font-size: .8rem; margin: 0; } .ip-table th { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
.ip-pill { font-size: .68rem; font-weight: 700; padding: .15rem .55rem; border-radius: 999px; white-space: nowrap; }
.ip-msg { font-size: .74rem; } .ip-msg.err { color: #b91c1c; } .ip-msg.warn { color: #b45309; }
.ip-changed { background: #fef9c3; border-radius: 3px; padding: 0 2px; }
.ip-commit { position: sticky; bottom: 0; background: var(--admin-surface, #fff); border-top: 1px solid var(--admin-border, #e2e8f0); padding: .9rem 1.1rem; border-radius: 0 0 14px 14px; z-index: 4; }
@media (max-width: 768px) { .ip-tiles { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="mb-3">
    <a href="{{ route('employees.import') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Upload a different file</a>
    <h1 class="h4 fw-bold mb-0 mt-1"><i class="bi bi-clipboard-check"></i> Check before importing</h1>
    <div class="small text-muted"><i class="bi bi-file-earmark-spreadsheet"></i> {{ $stored['filename'] }} · {{ $s['total'] }} row(s) · {{ count($stored['columns']) }} column(s) recognised</div>
</div>

<div class="ip-steps">
    <div class="ip-step done"><span class="n"><i class="bi bi-check"></i></span><strong>Upload</strong></div>
    <div class="ip-step active"><span class="n">2</span><strong>Preview</strong></div>
    <div class="ip-step"><span class="n">3</span><strong>Confirm</strong></div>
</div>

@if($stored['unknown'])
    <div class="alert alert-light border small py-2"><i class="bi bi-info-circle"></i> These columns were not recognised and will be ignored: <strong>{{ implode(', ', $stored['unknown']) }}</strong></div>
@endif

@php $link = fn ($show) => route('employees.import.show', ['token' => $token, 'mode' => $mode, 'show' => $show]); @endphp
<div class="ip-tiles">
    <a class="ip-tile {{ $filter === 'create' ? 'active' : '' }}" style="--c:#16a34a" href="{{ $link('create') }}"><div class="l">New employees</div><div class="v">{{ $s['create'] }}</div></a>
    <a class="ip-tile {{ $filter === 'update' ? 'active' : '' }}" style="--c:#0284c7" href="{{ $link('update') }}"><div class="l">Will be updated</div><div class="v">{{ $s['update'] }}</div></a>
    <a class="ip-tile {{ $filter === 'other' ? 'active' : '' }}" style="--c:#64748b" href="{{ $link('other') }}"><div class="l">No change / skip</div><div class="v">{{ $s['unchanged'] + $s['skip'] }}</div></a>
    <a class="ip-tile {{ $filter === 'warning' ? 'active' : '' }}" style="--c:#d97706" href="{{ $link('warning') }}"><div class="l">With warnings</div><div class="v">{{ $s['warnings'] }}</div></a>
    <a class="ip-tile {{ $filter === 'error' ? 'active' : '' }}" style="--c:#dc2626" href="{{ $link('error') }}"><div class="l">Can't import</div><div class="v">{{ $s['error'] }}</div></a>
</div>

<div class="ip-card">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom flex-wrap gap-2">
        <span class="small text-muted">Showing {{ $rows->count() }} of {{ $s['total'] }} · <a href="{{ $link('all') }}">show all</a></span>
        @if($s['error'])
            <span class="small text-danger"><i class="bi bi-exclamation-triangle"></i> Fix the red rows in your file and upload again, or continue and they'll be skipped.</span>
        @endif
    </div>
    <div class="table-responsive" style="max-height: 60vh; overflow: auto">
        <table class="table table-hover align-middle ip-table">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 2">
                <tr><th>Row</th><th>Result</th><th>Employee</th><th>Notes</th>@foreach($show as $c)<th>{{ $labels[$c] }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    @php [$lbl, $bg, $fg, $ic] = $actionMeta[$r['action']]; $d = $r['data']; @endphp
                    <tr>
                        <td class="text-muted">{{ $r['line'] }}</td>
                        <td><span class="ip-pill" style="background:{{ $bg }};color:{{ $fg }}"><i class="bi {{ $ic }}"></i> {{ $lbl }}</span></td>
                        <td class="text-nowrap">
                            <strong>{{ trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')) ?: ($r['match_name'] ?? '—') }}</strong>
                            <div class="text-muted" style="font-size:.7rem">
                                {{ $d['employee_id'] ?? ($r['action'] === 'create' ? 'EIN auto' : '') }}
                                @if($r['match_name'])· matches <strong>{{ $r['match_name'] }}</strong> by {{ $r['matched_by'] }}@endif
                            </div>
                        </td>
                        <td style="min-width:220px">
                            @foreach($r['errors'] as $m)<div class="ip-msg err"><i class="bi bi-x-circle"></i> {{ $m }}</div>@endforeach
                            @foreach($r['warnings'] as $m)<div class="ip-msg warn"><i class="bi bi-exclamation-triangle"></i> {{ $m }}</div>@endforeach
                            @if($r['action'] === 'update')<div class="ip-msg text-muted">Changes: {{ collect($r['changes'])->map(fn ($c) => $labels[$c] ?? $c)->implode(', ') ?: 'supervisor' }}</div>@endif
                        </td>
                        @foreach($show as $c)
                            <td>
                                @if(filled($d[$c] ?? null))
                                    <span class="{{ in_array($c, $r['changes'], true) ? 'ip-changed' : '' }}">{{ $d[$c] }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @endforeach

                    </tr>
                @empty
                    <tr><td colspan="{{ count($show) + 4 }}" class="text-center text-muted py-4">Nothing in this view.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('employees.import.commit', $token) }}" class="ip-commit" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
        @csrf
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Existing employees</label>
                <select name="mode" class="form-select form-select-sm" onchange="location.href='{{ route('employees.import.show', ['token' => $token]) }}?mode=' + this.value">
                    <option value="create_update" @selected($mode === 'create_update')>Update their details</option>
                    <option value="create_only" @selected($mode === 'create_only')>Leave them alone (new only)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Leave entitlement if blank</label>
                <div class="input-group input-group-sm"><input type="number" name="default_entitlement" step="0.5" min="0" class="form-control" value="{{ config('leave.annual_entitlement') }}"><span class="input-group-text">days / yr</span></div>
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="portal_accounts" value="1" id="pa">
                    <label class="form-check-label small" for="pa">Create portal logins &amp; email them to new employees</label>
                </div>
            </div>
            <div class="col-md-3 text-md-end">
                @php $will = $s['create'] + ($mode === 'create_update' ? $s['update'] : 0); @endphp
                <button type="submit" class="btn btn-success w-100" @disabled($will === 0)>
                    <i class="bi bi-check2-all"></i> Import {{ $will }} employee(s)
                </button>
                <div class="small text-muted mt-1">{{ $s['create'] }} new · {{ $mode === 'create_update' ? $s['update'] : 0 }} updated · {{ $s['error'] }} skipped</div>
            </div>
        </div>
    </form>
</div>

@endsection
