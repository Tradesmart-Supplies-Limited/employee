@extends('layouts.portal')
@section('title', 'My Attendance')

@section('content')
@php
    use App\Models\AttendanceRecord as AR;
    use App\Services\AttendanceService as Att;
    $cells = $row->cells ?? [];
    $days = collect($cells)->filter(fn ($c) => ! in_array($c->status, [null, 'off', 'untracked'], true))->sortKeysDesc();
    $tm = $todayCell?->status ? Att::meta($todayCell->status) : null;
    $tr = $todayCell?->record;
@endphp

<style>
.ma-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 16px; }
.ma-qr { background: linear-gradient(160deg, var(--brand, #185FA5), #0b3b6f); color: #fff; border-radius: 18px; padding: 1.3rem; text-align: center; }
.ma-qr .code { background: #fff; border-radius: 14px; padding: 12px; width: min(250px, 72vw); margin: .8rem auto; }
.ma-qr .code svg { display: block; width: 100%; height: auto; }
.ma-stat { padding: .8rem 1rem; border-left: 4px solid var(--c); }
.ma-stat .v { font-size: 1.5rem; font-weight: 800; color: var(--c); line-height: 1.1; }
.ma-stat .l { font-size: .7rem; font-weight: 700; text-transform: uppercase; color: var(--admin-muted, #64748b); letter-spacing: .05em; }
.ma-day { display: flex; align-items: center; gap: .8rem; padding: .6rem 1rem; border-bottom: 1px solid var(--admin-border, #f1f5f9); font-size: .88rem; }
.ma-day .d { width: 48px; text-align: center; line-height: 1.1; }
.ma-day .d b { font-size: 1.15rem; display: block; }
.ma-day .d small { font-size: .66rem; text-transform: uppercase; color: var(--admin-muted, #94a3b8); font-weight: 700; }
.ma-full { position: fixed; inset: 0; background: #fff; z-index: 2000; display: none; flex-direction: column; align-items: center; justify-content: center; gap: 14px; padding: 20px; color: #0f172a; }
.ma-full.show { display: flex; }
.ma-full .code { width: min(84vw, 70vh); }
.ma-full .code svg { width: 100%; height: auto; display: block; }
</style>

<div class="mb-3">
    <h4 class="mb-1 fw-bold"><i class="bi bi-qr-code"></i> My Attendance</h4>
    <small class="text-muted">Show your QR code to the tablet at the entrance when you arrive and when you leave.</small>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="ma-qr mb-3">
            <div class="small text-uppercase fw-bold" style="letter-spacing:.08em;opacity:.8">My attendance QR</div>
            <div class="code" id="qrSmall"></div>
            <div class="fw-bold fs-5">{{ $employee->full_name }}</div>
            <div class="small" style="opacity:.8">{{ $employee->employee_id }}</div>
            <button type="button" class="btn btn-light btn-sm mt-3 fw-semibold" id="btnFull"><i class="bi bi-arrows-fullscreen"></i> Show full screen</button>
            <div class="small mt-2" style="opacity:.75"><i class="bi bi-brightness-high"></i> Turn your screen brightness up for a quick scan.</div>
        </div>

        <div class="ma-card p-3">
            <div class="small text-uppercase fw-bold text-muted mb-2" style="letter-spacing:.06em">Today · {{ $today->format('D d M') }}</div>
            @if($tm)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill" style="background:{{ $tm['bg'] }};color:{{ $tm['color'] }};font-size:.8rem"><i class="bi {{ $tm['icon'] }}"></i> {{ $tm['label'] }}</span>
                    @if($tr?->minutes_late)<span class="small" style="color:#b45309">{{ $tr->minutes_late }} min late</span>@endif
                </div>
                <div class="d-flex gap-4">
                    <div><div class="small text-muted">In</div><div class="fw-bold fs-5">{{ $tr?->in_time ?? '—' }}</div></div>
                    <div><div class="small text-muted">Out</div><div class="fw-bold fs-5">{{ $tr?->out_time ?? '—' }}</div></div>
                    <div><div class="small text-muted">Hours</div><div class="fw-bold fs-5">{{ $tr ? $tr->hours : '—' }}</div></div>
                </div>
                @if($todayCell->status === 'not_in')<div class="small text-muted mt-2">{{ $todayCell->note ? $todayCell->note . '.' : 'Work starts at ' . $settings->work_start . '.' }} Scan your code at the entrance.</div>@endif
                @if(! $tr && $todayCell->note)<div class="small text-muted mt-2">{{ $todayCell->note }}</div>@endif
            @else
                <div class="text-muted small">Not a working day for you.</div>
            @endif
        </div>
    </div>

    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <h6 class="fw-bold mb-0">{{ $from->format('F Y') }}</h6>
            <form method="GET" class="d-flex gap-1">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('portal.attendance.index', ['month' => $from->copy()->subMonth()->format('Y-m')]) }}"><i class="bi bi-chevron-left"></i></a>
                <input type="month" name="month" value="{{ $from->format('Y-m') }}" max="{{ $today->format('Y-m') }}" class="form-control form-control-sm" style="width:150px" onchange="this.form.submit()">
                @if($from->copy()->addMonth()->lte($today))<a class="btn btn-sm btn-outline-secondary" href="{{ route('portal.attendance.index', ['month' => $from->copy()->addMonth()->format('Y-m')]) }}"><i class="bi bi-chevron-right"></i></a>@endif
            </form>
        </div>

        @if($row)
        <div class="row g-2 mb-3">
            @foreach([
                ['Attendance', $row->rate !== null ? $row->rate . '%' : '—', 'var(--brand-text, #185FA5)'],
                ['Days in', $row->attended . '/' . $row->expected, '#16a34a'],
                ['Late', $row->late, '#d97706'],
                ['Absent', $row->absent, '#dc2626'],
                ['Avg arrival', $row->avg_in ?? '—', '#0891b2'],
                ['Hours', number_format($row->worked / 60, 1), '#7c3aed'],
            ] as [$l, $v, $c])
                <div class="col-4 col-md-2"><div class="ma-card ma-stat h-100" style="--c:{{ $c }}"><div class="l">{{ $l }}</div><div class="v">{{ $v }}</div></div></div>
            @endforeach
        </div>
        @endif

        <div class="ma-card">
            @forelse($days as $date => $c)
                @php $d = \Carbon\Carbon::parse($date); $m = Att::meta($c->status); $r = $c->record; @endphp
                <div class="ma-day">
                    <div class="d"><b>{{ $d->format('d') }}</b><small>{{ $d->format('D') }}</small></div>
                    <span class="badge rounded-pill" style="background:{{ $m['bg'] }};color:{{ $m['color'] }}"><i class="bi {{ $m['icon'] }}"></i> {{ $m['label'] }}</span>
                    <div class="flex-grow-1 small text-muted text-truncate">
                        @if($r?->clock_in) In <strong class="text-body">{{ $r->in_time }}</strong>@if($r->clock_out) · Out <strong class="text-body">{{ $r->out_time }}</strong>@elseif($r->missing_out) · <span class="text-danger">no clock-out</span>@endif @endif
                        @if($r?->minutes_late) · <span style="color:#b45309">{{ $r->minutes_late }} min late</span>@endif
                        @if($c->note) · {{ $c->note }}@endif
                    </div>
                    <div class="small fw-semibold">{{ $r && $r->worked_minutes ? $r->hours : '' }}</div>
                </div>
            @empty
                <div class="text-center text-muted py-5"><i class="bi bi-calendar3 fs-2 d-block"></i>Nothing recorded this month yet.</div>
            @endforelse
        </div>
        <div class="small text-muted mt-2"><i class="bi bi-info-circle"></i> Something wrong (for example you forgot to scan)? Tell HR and they can correct it.</div>
    </div>
</div>

<div class="ma-full" id="full">
    <div class="fw-bold fs-4">{{ $employee->full_name }}</div>
    <div class="code" id="qrBig"></div>
    <div class="text-muted">{{ $employee->employee_id }} · tap to close</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/attendance/qrcode.js') }}"></script>
<script>
(function () {
    const payload = @json($payload);
    const svg = () => { const q = qrcode(0, 'M'); q.addData(payload); q.make(); return q.createSvgTag({ cellSize: 6, margin: 2, scalable: true }); };
    document.getElementById('qrSmall').innerHTML = svg();
    document.getElementById('qrBig').innerHTML = svg();
    const full = document.getElementById('full');
    let wake = null;
    document.getElementById('btnFull').addEventListener('click', async () => {
        full.classList.add('show');
        try { await (full.requestFullscreen ? full.requestFullscreen() : Promise.resolve()); } catch (e) {}
        try { if ('wakeLock' in navigator) wake = await navigator.wakeLock.request('screen'); } catch (e) {}
    });
    full.addEventListener('click', () => { full.classList.remove('show'); if (document.fullscreenElement) document.exitFullscreen(); if (wake) { wake.release(); wake = null; } });
})();
</script>
@endpush
