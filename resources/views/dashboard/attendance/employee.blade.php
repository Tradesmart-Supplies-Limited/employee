@extends('layouts.app')
@section('title', 'Attendance · ' . $employee->full_name)

@section('content')
@php
    use App\Models\AttendanceRecord as AR;
    use App\Services\AttendanceService as Att;
    $m = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01');
    $start = $m->copy()->startOfMonth();
    $end = $m->copy()->endOfMonth();
    $gridStart = $start->copy()->startOfWeek();
    $gridEnd = $end->copy()->endOfWeek();
    $cells = $row->cells ?? [];
    $actions = auth()->user()?->hasFullAccess()
        ? '<a href="' . route('attendance.badges', ['ids' => [$employee->id]]) . '" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print QR badge</a>'
             . '<a href="' . route('employees.show', $employee) . '" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person"></i> Profile</a>'
        : null;
@endphp

@include('dashboard.attendance.partials.nav', ['title' => $employee->full_name, 'subtitle' => $employee->employee_id . ($employee->position ? ' · ' . e($employee->position) : '') . ($employee->department ? ' · ' . e($employee->department) : ''), 'actions' => $actions])

<style>
.ec-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
.ec-dow { font-size: .68rem; font-weight: 700; text-transform: uppercase; color: var(--admin-muted, #94a3b8); text-align: center; }
.ec-day { min-height: 74px; border-radius: 10px; border: 1px solid var(--admin-border, #e2e8f0); padding: 6px 7px; font-size: .72rem; position: relative; background: var(--admin-surface, #fff); text-decoration: none; color: inherit; display: block; }
.ec-day .n { font-weight: 800; font-size: .82rem; }
.ec-day.out { opacity: .3; }
.ec-day.today { box-shadow: 0 0 0 2px var(--brand, #185FA5); }
.ec-day .st { position: absolute; left: 0; right: 0; bottom: 0; height: 4px; border-radius: 0 0 10px 10px; }
.ec-photo { width: 54px; height: 54px; border-radius: 10px; object-fit: cover; }
</style>

<form method="GET" class="d-flex gap-2 align-items-center mb-3">
    <div class="btn-group">
        <a href="{{ route('attendance.employee', ['employee' => $employee, 'month' => $m->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
        <input type="month" name="month" value="{{ $month }}" max="{{ Att::today()->format('Y-m') }}" class="form-control form-control-sm" style="width:150px;border-radius:0" onchange="this.form.submit()">
        @if($m->copy()->addMonth()->lte(Att::today()))<a href="{{ route('attendance.employee', ['employee' => $employee, 'month' => $m->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
        @else<span class="btn btn-sm btn-outline-secondary disabled"><i class="bi bi-chevron-right"></i></span>@endif
    </div>
    @if(strtolower((string) $employee->employment_status) !== 'active')<span class="badge bg-secondary">{{ $employee->employment_status }}</span>@endif
</form>

@if($row)
<div class="row g-2 mb-3">
    @foreach([
        ['Attendance', $row->rate !== null ? $row->rate . '%' : '—', 'var(--brand-text, #185FA5)', $row->attended . ' of ' . $row->expected . ' days'],
        ['Late', $row->late, '#d97706', AR::formatMinutes($row->late_minutes) . ' in total'],
        ['Absent', $row->absent, '#dc2626', 'no scan, no leave'],
        ['On leave', $row->on_leave, '#7c3aed', 'approved leave days'],
        ['Avg arrival', $row->avg_in ?? '—', '#16a34a', 'work starts ' . \App\Models\AttendanceSetting::current()->work_start],
        ['Hours', number_format($row->worked / 60, 1), '#0891b2', $row->missing_out ? $row->missing_out . ' day(s) no clock-out' : 'worked this month'],
    ] as [$l, $v, $c, $sub])
        <div class="col-6 col-md-4 col-xl-2"><div class="at-card at-tile h-100" style="--c:{{ $c }}"><div class="l">{{ $l }}</div><div class="v" style="font-size:1.4rem">{{ $v }}</div><div class="small at-muted">{{ $sub }}</div></div></div>
    @endforeach
</div>
@endif

<div class="row g-3">
    <div class="col-xl-7">
        <div class="at-card p-3">
            <div class="ec-grid mb-2">@foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d)<div class="ec-dow">{{ $d }}</div>@endforeach</div>
            <div class="ec-grid">
                @for($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay())
                    @php $c = $cells[$d->toDateString()] ?? null; $meta = $c && $c->status ? Att::meta($c->status) : null; $inMonth = $d->month === $m->month; @endphp
                    <a class="ec-day {{ $inMonth ? '' : 'out' }} {{ $d->isSameDay($today) ? 'today' : '' }}"
                       @if($inMonth && $d->lte($today)) href="{{ route('attendance.register', ['date' => $d->toDateString(), 'department' => $employee->department]) }}" title="Edit this day in the register" @endif
                       style="{{ $meta ? 'background:' . $meta['bg'] : '' }}">
                        <div class="n">{{ $d->day }}</div>
                        @if($inMonth && $meta)
                            <div style="color:{{ $meta['color'] }};font-weight:700">{{ $meta['label'] }}</div>
                            @if($c->record?->clock_in)<div>{{ $c->record->in_time }}–{{ $c->record->out_time ?? '?' }}</div>@endif
                            @if($c->record?->minutes_late)<div style="color:#b45309">+{{ $c->record->minutes_late }}m</div>@endif
                            @if(! $c->record && $c->note)<div class="text-truncate at-muted">{{ $c->note }}</div>@endif
                            <span class="st" style="background:{{ $meta['color'] }}"></span>
                        @endif
                    </a>
                @endfor
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="at-card">
            <div class="at-card-h"><h6><i class="bi bi-qr-code-scan"></i> Kiosk scans this month</h6><span class="small at-muted">{{ $scans->count() }}</span></div>
            <div style="max-height:560px; overflow:auto">
                @forelse($scans as $s)
                    <div class="d-flex gap-2 align-items-center px-3 py-2 border-bottom">
                        @if($s->photo)
                            <a href="{{ route('attendance.photo', ['path' => $s->photo]) }}" target="_blank"><img class="ec-photo" src="{{ route('attendance.photo', ['path' => $s->photo]) }}" alt="Photo at scan" loading="lazy"></a>
                        @else
                            <span class="ec-photo d-flex align-items-center justify-content-center" style="background:var(--admin-surface-soft,#f1f5f9)"><i class="bi bi-person at-muted"></i></span>
                        @endif
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="fw-semibold small">{{ $s->scanned_at->format('D d M · H:i') }} <span class="badge {{ ['in' => 'bg-success', 'out' => 'bg-primary', 'rejected' => 'bg-danger'][$s->action] ?? 'bg-secondary' }}">{{ strtoupper($s->action) }}</span></div>
                            <div class="small at-muted text-truncate" title="{{ $s->message }}">{{ $s->message }}</div>
                            <div class="small at-muted">{{ $s->kiosk?->name ?? ($s->method === 'qr' ? 'Kiosk' : ucfirst($s->method)) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center at-muted small py-5"><i class="bi bi-qr-code fs-2 d-block"></i>No kiosk scans this month.</div>
                @endforelse
            </div>
        </div>
        @if(auth()->user()?->hasFullAccess())
        <form method="POST" action="{{ route('attendance.qr.reissue', $employee) }}" class="mt-2 small" onsubmit="return confirm('Cancel {{ addslashes($employee->full_name) }}\'s current QR code and issue a new one? Their old badge will stop working.')">
            @csrf
            <i class="bi bi-shield-exclamation at-muted"></i> Badge lost? <button class="btn btn-link btn-sm p-0 align-baseline">Cancel the old QR code and issue a new one</button>
        </form>
        @endif
    </div>
</div>
@endsection
