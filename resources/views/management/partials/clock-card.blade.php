{{-- Clock in / out card. Vars: $todayRec (ManagementAttendance|null) --}}
@php
    $state = ! $todayRec?->clock_in ? 'out' : (! $todayRec->clock_out ? 'in' : 'done');
@endphp
<div class="mc-clock is-{{ $state }}">
    <div class="mc-clock-top">
        <div>
            <div class="mc-label">{{ \App\Services\AttendanceService::today()->format('l, d F Y') }}</div>
            <div class="mc-time" id="mcNow" data-tz="{{ \App\Services\AttendanceService::tz() }}">{{ \App\Services\AttendanceService::now()->format('H:i') }}</div>
        </div>
        <span class="mc-pill">
            @if($state === 'out')<i class="bi bi-moon-stars"></i> Not clocked in yet
            @elseif($state === 'in')<i class="bi bi-circle-fill"></i> At work since {{ $todayRec->in_time }}
            @else<i class="bi bi-check2-circle"></i> Done for today
            @endif
        </span>
    </div>

    <div class="mc-times">
        <div><small>Clocked in</small><b>{{ $todayRec?->in_time ?? '—' }}</b>@if($todayRec?->in_source === 'manual')<em>entered</em>@endif</div>
        <div><small>Clocked out</small><b>{{ $todayRec?->out_time ?? '—' }}</b></div>
        <div><small>Today</small><b id="mcWorked" data-in="{{ $state === 'in' ? $todayRec->date->format('Y-m-d') . 'T' . $todayRec->clock_in : '' }}">{{ $state === 'done' ? \App\Models\ManagementAttendance::hm($todayRec->minutes) : ($state === 'in' ? '…' : '—') }}</b></div>
    </div>

    @if($state !== 'done')
        <form method="POST" action="{{ route('mgmt.clock') }}" class="mc-form" onsubmit="this.querySelector('button').disabled = true;">
            @csrf
            @if($state === 'out')
                <input type="text" name="note" class="form-control form-control-sm mc-note" maxlength="255" placeholder="Optional note, e.g. working from the Lusaka branch today">
            @endif
            <details class="mc-manual" @if($errors->has('time')) open @endif>
                <summary>{{ $state === 'out' ? 'Forgot to clock in? Set the time you started' : 'Finished earlier? Set the time you stopped' }}</summary>
                <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                    <input type="time" name="time" class="form-control form-control-sm" style="width:130px" value="{{ old('time') }}" max="{{ \App\Services\AttendanceService::now()->format('H:i') }}" aria-label="{{ $state === 'out' ? 'Start time' : 'Finish time' }}">
                    <small>Leave empty to use the time now.</small>
                </div>
                @error('time')<div class="mc-err mt-1">{{ $message }}</div>@enderror
            </details>
            <button class="btn btn-lg w-100 {{ $state === 'out' ? 'btn-success' : 'btn-primary' }}">
                <i class="bi {{ $state === 'out' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right' }} me-1"></i>
                {{ $state === 'out' ? 'Clock in' : 'Clock out' }}
            </button>
        </form>
    @else
        <div class="mc-done">Thank you. You worked {{ \App\Models\ManagementAttendance::hm($todayRec->minutes) }} today. See you tomorrow!</div>
    @endif
</div>

@once
<style>
.mc-clock { border-radius: 18px; padding: 20px; color: #fff; background: linear-gradient(135deg, #0f2a4a, var(--brand, #185FA5)); box-shadow: 0 14px 36px rgba(15, 42, 74, .25); }
.mc-clock.is-in { background: linear-gradient(135deg, #064e3b, #059669); }
.mc-clock.is-done { background: linear-gradient(135deg, #1e293b, #475569); }
.mc-clock-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.mc-label { font-size: .82rem; opacity: .85; }
.mc-time { font-size: clamp(2.4rem, 7vw, 3.4rem); font-weight: 800; line-height: 1.05; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
.mc-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; background: rgba(255,255,255,.16); font-size: .8rem; font-weight: 600; }
.mc-clock.is-in .mc-pill .bi-circle-fill { font-size: .55rem; color: #6ee7b7; animation: mcPulse 1.6s ease-in-out infinite; }
@keyframes mcPulse { 50% { opacity: .35; } }
.mc-times { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin: 16px 0; }
.mc-times > div { background: rgba(255,255,255,.12); border-radius: 12px; padding: 10px 12px; }
.mc-times small { display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; opacity: .8; }
.mc-times b { font-size: 1.15rem; font-variant-numeric: tabular-nums; }
.mc-note { background: rgba(255,255,255,.95); border: 0; margin-bottom: 8px; }
.mc-form .btn { font-weight: 700; border: 0; box-shadow: 0 6px 16px rgba(0,0,0,.2); }
.mc-times em { font-style: normal; font-size: .66rem; margin-left: 6px; padding: 1px 6px; border-radius: 999px; background: rgba(255,255,255,.2); vertical-align: middle; }
.mc-manual { margin-bottom: 10px; font-size: .84rem; }
.mc-manual summary { cursor: pointer; opacity: .9; text-decoration: underline; text-underline-offset: 3px; }
.mc-manual small { opacity: .85; }
.mc-manual input { background: rgba(255,255,255,.95); border: 0; }
.mc-err { background: rgba(255,255,255,.95); color: #b91c1c; border-radius: 8px; padding: 4px 8px; font-size: .8rem; }
.mc-done { background: rgba(255,255,255,.12); border-radius: 12px; padding: 12px; font-size: .92rem; }
</style>
<script>
(function () {
    var el = document.getElementById('mcNow'), w = document.getElementById('mcWorked');
    if (!el) return;
    var tz = el.dataset.tz;
    function tick() {
        try { el.textContent = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', timeZone: tz }); } catch (e) {}
        if (w && w.dataset.in) {
            // minutes since clock-in, using the server's local time zone
            var nowLocal = new Date(new Date().toLocaleString('en-US', { timeZone: tz }));
            var p = w.dataset.in.split(/[-T:]/), start = new Date(+p[0], p[1] - 1, +p[2], +p[3], +p[4], +(p[5] || 0));
            var m = Math.max(0, Math.floor((nowLocal - start) / 60000));
            w.textContent = Math.floor(m / 60) + 'h ' + String(m % 60).padStart(2, '0') + 'm';
        }
    }
    tick(); setInterval(tick, 15000);
})();
</script>
@endonce
