@extends('layouts.app')
@section('title', 'Attendance summary')

@section('content')
@php
    use App\Models\AttendanceRecord as AR;
    use App\Services\AttendanceService as Att;
    $m = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01');
    $prevM = $m->copy()->subMonth()->format('Y-m');
    $nextM = $m->copy()->addMonth();
    $link = fn ($p) => route('attendance.summary', array_filter(array_merge(['month' => $month, 'department' => $department, 'sort' => $sort !== 'name' ? $sort : null], $p)));
    $rate = $all['expected'] ? round($all['attended'] / $all['expected'] * 100, 1) : null;
    $actions = '<a href="' . route('attendance.export', array_filter(['month' => $month, 'department' => $department])) . '" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Export month (CSV)</a>';
    $cellColor = fn ($s) => $s === null ? 'transparent' : ($s === 'not_in' ? '#e2e8f0' : ($s === 'untracked' ? '#f1f5f9' : Att::meta($s)['color']));
@endphp

@include('dashboard.attendance.partials.nav', ['title' => 'Monthly summary · ' . $m->format('F Y'), 'subtitle' => $from->format('d M') . ' – ' . $to->format('d M Y') . ($to->isSameDay(Att::today()) ? ' (month to date)' : ''), 'actions' => $actions])

<style>
.sm-strip { display: flex; gap: 2px; }
.sm-strip i { width: 11px; height: 18px; border-radius: 3px; display: block; }
.sm-strip i.we { opacity: .35; }
.sm-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 3px; vertical-align: -1px; }
.sm-rate { font-weight: 800; }
th a.sort { color: inherit; text-decoration: none; }
th a.sort.on { color: #185FA5; }
</style>

<form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <div class="btn-group">
        <a href="{{ $link(['month' => $prevM]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
        <input type="month" name="month" value="{{ $month }}" max="{{ Att::today()->format('Y-m') }}" class="form-control form-control-sm" style="width:150px; border-radius:0" onchange="this.form.submit()">
        @if($nextM->lte(Att::today()))<a href="{{ $link(['month' => $nextM->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
        @else<span class="btn btn-sm btn-outline-secondary disabled"><i class="bi bi-chevron-right"></i></span>@endif
    </div>
    <select name="department" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
        <option value="">All departments</option>
        @foreach($departments as $d)<option @selected($department === $d)>{{ $d }}</option>@endforeach
    </select>
    <input type="hidden" name="sort" value="{{ $sort }}">
</form>

<div class="row g-2 mb-3">
    @foreach([
        ['Attendance rate', $rate !== null ? $rate . '%' : '—', '#185FA5'],
        ['Days attended', number_format($all['attended']) . ' / ' . number_format($all['expected']), '#16a34a'],
        ['Late arrivals', $all['late'] . ' · ' . AR::formatMinutes($all['late_minutes']), '#d97706'],
        ['Absences', $all['absent'], '#dc2626'],
        ['Leave days', $all['on_leave'], '#7c3aed'],
        ['Hours worked', number_format($all['worked'] / 60, 0) . ' h', '#0891b2'],
    ] as [$l, $v, $c])
        <div class="col-6 col-md-4 col-xl-2"><div class="at-card at-tile h-100" style="--c:{{ $c }}"><div class="l">{{ $l }}</div><div class="v" style="font-size:1.35rem">{{ $v }}</div></div></div>
    @endforeach
</div>

<div class="at-card">
    <div class="at-card-h">
        <div class="sm-legend small d-flex flex-wrap gap-3">
            @foreach(['present', 'late', 'half_day', 'absent', 'on_leave', 'holiday', 'off'] as $s)<span><i style="background:{{ AR::STATUSES[$s]['color'] }}"></i>{{ AR::STATUSES[$s]['label'] }}</span>@endforeach
        </div>
        <span class="small at-muted">{{ $rows->count() }} employee(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table at-table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><a class="sort {{ $sort === 'name' ? 'on' : '' }}" href="{{ $link(['sort' => null]) }}">Employee</a></th>
                    <th>Day by day</th>
                    <th class="text-end"><a class="sort {{ $sort === 'rate' ? 'on' : '' }}" href="{{ $link(['sort' => 'rate']) }}" title="Lowest first">Rate</a></th>
                    <th class="text-end">In</th>
                    <th class="text-end"><a class="sort {{ $sort === 'late' ? 'on' : '' }}" href="{{ $link(['sort' => 'late']) }}">Late</a></th>
                    <th class="text-end"><a class="sort {{ $sort === 'absent' ? 'on' : '' }}" href="{{ $link(['sort' => 'absent']) }}">Absent</a></th>
                    <th class="text-end">Leave</th>
                    <th class="text-end">Avg arrival</th>
                    <th class="text-end"><a class="sort {{ $sort === 'hours' ? 'on' : '' }}" href="{{ $link(['sort' => 'hours']) }}">Hours</a></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td style="min-width:190px">
                            <a href="{{ route('attendance.employee', ['employee' => $r->employee, 'month' => $month]) }}" class="fw-semibold text-decoration-none">{{ $r->employee->full_name }}</a>
                            <div class="small at-muted">{{ $r->employee->employee_id }}{{ $r->employee->department ? ' · ' . $r->employee->department : '' }}</div>
                        </td>
                        <td>
                            <div class="sm-strip">
                                @foreach($days as $d)
                                    @php $c = $r->cells[$d->toDateString()] ?? null; $st = $c?->status; @endphp
                                    <i class="{{ $st === 'off' ? 'we' : '' }}" style="background:{{ $cellColor($st) }}; {{ $st === null ? 'border:1px dashed #e2e8f0' : '' }}"
                                       title="{{ $d->format('D d M') }}: {{ $st ? Att::meta($st)['label'] : 'n/a' }}{{ $c?->record?->in_time ? ' · in ' . $c->record->in_time : '' }}{{ $c?->record?->out_time ? ' · out ' . $c->record->out_time : '' }}{{ $c?->note ? ' · ' . $c->note : '' }}"></i>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-end sm-rate" style="color:{{ $r->rate === null ? '#94a3b8' : ($r->rate >= 90 ? '#16a34a' : ($r->rate >= 75 ? '#d97706' : '#dc2626')) }}">{{ $r->rate !== null ? $r->rate . '%' : '—' }}</td>
                        <td class="text-end">{{ $r->attended }}<span class="at-muted">/{{ $r->expected }}</span></td>
                        <td class="text-end">{!! $r->late ? '<span style="color:#b45309" title="' . AR::formatMinutes($r->late_minutes) . ' in total">' . $r->late . '</span>' : '<span class="at-muted">0</span>' !!}</td>
                        <td class="text-end">{!! $r->absent ? '<span class="text-danger fw-bold">' . $r->absent . '</span>' : '<span class="at-muted">0</span>' !!}</td>
                        <td class="text-end">{{ $r->on_leave ?: '' }}</td>
                        <td class="text-end">{{ $r->avg_in ?? '—' }}</td>
                        <td class="text-end at-muted">{{ $r->worked ? number_format($r->worked / 60, 1) . ' h' : '—' }}{!! $r->missing_out ? ' <i class="bi bi-exclamation-circle text-danger" title="' . $r->missing_out . ' day(s) without a clock-out"></i>' : '' !!}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center at-muted py-5">No employees to show.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
