@extends('layouts.app')
@section('title', 'Attendance')

@section('content')
@php
    use App\Models\AttendanceRecord as AR;
    use App\Services\AttendanceService as Att;
    $t = $day['totals'];
    $prev = $date->copy()->subDay()->toDateString();
    $next = $date->copy()->addDay();
    $q = fn ($d) => route('attendance.index', array_filter(['date' => $d, 'department' => $department]));
    $actions = '<a href="' . route('attendance.export', ['date' => $date->toDateString()]) . '" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Export day</a>'
             . '<a href="' . route('attendance.register', array_filter(['date' => $date->toDateString(), 'department' => $department])) . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i> Edit this day</a>';
    $rate = $t['rate'];
    $circ = 2 * M_PI * 52;
    $maxBar = max(1, collect($trend)->max('expected'));
@endphp

@include('dashboard.attendance.partials.nav', ['title' => $isToday ? 'Today · ' . $date->format('l d M') : $date->format('l, d F Y'), 'subtitle' => 'Who is in, who is late and who is missing' . ($isToday ? ' — updates live as people scan.' : '.'), 'actions' => $actions])

<style>
.ov-ring { display: flex; align-items: center; gap: 1.1rem; padding: 1rem 1.2rem; height: 100%; }
.ov-ring svg { flex-shrink: 0; }
.ov-trend { display: flex; align-items: flex-end; gap: 4px; height: 120px; padding: 0 .2rem; }
.ov-bar { flex: 1; display: flex; flex-direction: column-reverse; min-width: 6px; border-radius: 4px 4px 0 0; overflow: hidden; position: relative; text-decoration: none; }
.ov-bar span { display: block; }
.ov-bar.sel { outline: 2px solid #185FA5; outline-offset: 2px; }
.ov-bar.off { opacity: .35; }
.ov-days { display: flex; gap: 4px; padding: 0 .2rem; font-size: .6rem; color: var(--admin-muted, #94a3b8); }
.ov-days span { flex: 1; text-align: center; min-width: 6px; overflow: hidden; }
.ov-feed { max-height: 420px; overflow: auto; }
.ov-feed .it { display: flex; gap: .6rem; align-items: center; padding: .55rem 1rem; border-bottom: 1px solid var(--admin-border, #f1f5f9); font-size: .84rem; }
.ov-feed .it.new { animation: ovflash 2s; }
@keyframes ovflash { from { background: #dbeafe; } to { background: transparent; } }
.ov-act { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ov-filter .btn { font-size: .76rem; padding: .2rem .65rem; border-radius: 999px; }
.ov-dept { display: flex; align-items: center; gap: .6rem; padding: .45rem 0; font-size: .82rem; }
.ov-dept .bar { flex: 1; height: 8px; border-radius: 4px; background: var(--admin-surface-soft, #f1f5f9); overflow: hidden; display: flex; }
.ov-live { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 700; color: #16a34a; }
.ov-live i { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: ovpulse 1.6s infinite; }
@keyframes ovpulse { 0% { box-shadow: 0 0 0 0 rgba(34,197,94,.5); } 100% { box-shadow: 0 0 0 9px rgba(34,197,94,0); } }
</style>

{{-- date + department bar --}}
<form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <div class="btn-group">
        <a href="{{ $q($prev) }}" class="btn btn-sm btn-outline-secondary" title="Previous day"><i class="bi bi-chevron-left"></i></a>
        <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ Att::today()->toDateString() }}" class="form-control form-control-sm" style="width:150px; border-radius:0" onchange="this.form.submit()">
        @if($next->lte(Att::today()))
            <a href="{{ $q($next->toDateString()) }}" class="btn btn-sm btn-outline-secondary" title="Next day"><i class="bi bi-chevron-right"></i></a>
        @else
            <span class="btn btn-sm btn-outline-secondary disabled"><i class="bi bi-chevron-right"></i></span>
        @endif
    </div>
    @unless($isToday)<a href="{{ route('attendance.index', array_filter(['department' => $department])) }}" class="btn btn-sm btn-light border">Today</a>@endunless
    <select name="department" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
        <option value="">All departments</option>
        @foreach($departments as $d)<option @selected($department === $d)>{{ $d }}</option>@endforeach
    </select>
    @if($isToday)<span class="ov-live ms-1"><i></i> LIVE · <span id="liveNow">{{ Att::now()->format('H:i') }}</span></span>@endif
    @if($day['holiday'])<span class="badge rounded-pill" style="background:#e0f2fe;color:#075985"><i class="bi bi-flag"></i> Public holiday: {{ $day['holiday'] }}</span>
    @elseif(! $day['working'])<span class="badge rounded-pill bg-light text-secondary border"><i class="bi bi-moon"></i> Not a working day</span>@endif
</form>

{{-- KPIs --}}
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="at-card ov-ring">
            <svg width="124" height="124" viewBox="0 0 124 124" aria-label="Attendance rate">
                <circle cx="62" cy="62" r="52" fill="none" stroke="#eef2f7" stroke-width="14"/>
                <circle id="ringArc" cx="62" cy="62" r="52" fill="none" stroke="{{ $rate === null ? '#cbd5e1' : ($rate >= 90 ? '#16a34a' : ($rate >= 75 ? '#d97706' : '#dc2626')) }}" stroke-width="14" stroke-linecap="round"
                        stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $circ * (1 - ($rate ?? 0) / 100) }}" transform="rotate(-90 62 62)" style="transition: stroke-dashoffset .6s"/>
                <text x="62" y="60" text-anchor="middle" font-size="26" font-weight="800" fill="currentColor" id="ringTxt">{{ $rate !== null ? round($rate) . '%' : '—' }}</text>
                <text x="62" y="80" text-anchor="middle" font-size="11" fill="#64748b">attendance</text>
            </svg>
            <div>
                <div class="fw-bold" style="font-size:1.05rem"><span data-k="attended">{{ $t['attended'] }}</span> of <span data-k="expected">{{ $t['expected'] }}</span> in</div>
                <div class="small at-muted mb-2">expected at work {{ $isToday ? 'today' : 'that day' }}</div>
                <div class="small"><i class="bi bi-stopwatch text-success"></i> <span data-k="punctual">{{ $t['punctual'] !== null ? $t['punctual'] . '%' : '—' }}</span> on time</div>
                <div class="small at-muted">{{ \App\Models\AttendanceShift::where('is_active', true)->orderBy('sort')->get()->map(fn ($sh) => $sh->name . ' ' . $sh->weekday_start)->implode(' · ') }} · {{ $settings->grace_minutes }} min grace</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="row g-2 h-100">
            @foreach([
                ['present', 'On time', '#16a34a', $t['present'] + $t['half_day'], 'bi-check-circle'],
                ['late', 'Late', '#d97706', $t['late'], 'bi-alarm'],
                [$isToday ? 'not_in' : 'absent', $isToday ? 'Not in yet' : 'Absent', $isToday ? '#64748b' : '#dc2626', $isToday ? $t['not_in'] + $t['absent'] : $t['absent'], $isToday ? 'bi-hourglass-split' : 'bi-x-circle'],
                ['on_leave', 'On leave', '#7c3aed', $t['on_leave'], 'bi-airplane'],
            ] as [$k, $label, $c, $v, $ic])
                <div class="col-6 col-md-3">
                    <a href="#everyone" data-filter="{{ $k }}" class="at-card at-tile d-block text-decoration-none h-100" style="--c:{{ $c }}">
                        <div class="l"><i class="bi {{ $ic }}"></i> {{ $label }}</div>
                        <div class="v" data-k="{{ $k === 'present' ? 'ontime' : ($k === 'not_in' ? 'notin' : $k) }}">{{ $v }}</div>
                        <div class="small at-muted mt-1">{{ $t['expected'] + $t['on_leave'] ? round($v / ($t['expected'] + $t['on_leave']) * 100) : 0 }}% of staff · <span class="text-primary">show</span></div>
                    </a>
                </div>
            @endforeach
            @if(! $isToday && $t['missing_out'])
                <div class="col-12"><div class="alert alert-info py-2 small mb-0"><i class="bi bi-exclamation-circle"></i> {{ $t['missing_out'] }} {{ $t['missing_out'] === 1 ? 'person' : 'people' }} clocked in but never scanned out, so their hours are missing. <a href="{{ route('attendance.register', ['date' => $date->toDateString()]) }}">Fix in the register</a>.</div></div>
            @endif
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- trend --}}
    <div class="col-lg-8">
        <div class="at-card h-100">
            <div class="at-card-h">
                <h6><i class="bi bi-bar-chart"></i> Last 3 weeks</h6>
                <div class="small d-flex gap-3"><span><i class="bi bi-square-fill text-success"></i> On time</span><span><i class="bi bi-square-fill text-warning"></i> Late</span><span><i class="bi bi-square-fill text-danger"></i> Absent</span><span><i class="bi bi-square-fill" style="color:#c4b5fd"></i> Leave</span></div>
            </div>
            <div class="p-3">
                <div class="ov-trend">
                    @foreach($trend as $d)
                        @php $h = fn ($n) => round($n / $maxBar * 110); @endphp
                        <a href="{{ $q($d['date']->toDateString()) }}" class="ov-bar {{ $d['date']->isSameDay($date) ? 'sel' : '' }} {{ $d['working'] ? '' : 'off' }}"
                           title="{{ $d['date']->format('D d M') }}: {{ $d['working'] ? $d['attended'] . '/' . $d['expected'] . ' in, ' . $d['late'] . ' late, ' . $d['absent'] . ' absent' : 'not a working day' }}">
                            <span style="height:{{ $h($d['present'] + $d['half_day']) }}px;background:#22c55e"></span>
                            <span style="height:{{ $h($d['late']) }}px;background:#f59e0b"></span>
                            <span style="height:{{ $h($d['absent'] + $d['not_in']) }}px;background:{{ $d['not_in'] ? '#cbd5e1' : '#ef4444' }}"></span>
                            <span style="height:{{ $h($d['on_leave']) }}px;background:#c4b5fd"></span>
                            @if(! $d['working'])<span style="height:4px;background:#e2e8f0"></span>@endif
                        </a>
                    @endforeach
                </div>
                <div class="ov-days mt-1">
                    @foreach($trend as $d)<span>{{ $d['date']->format('j') }}</span>@endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- departments --}}
    <div class="col-lg-4">
        <div class="at-card h-100">
            <div class="at-card-h"><h6><i class="bi bi-diagram-3"></i> By department</h6></div>
            <div class="px-3 py-2" style="max-height:200px; overflow:auto">
                @forelse($byDept as $d)
                    <div class="ov-dept">
                        <div style="width:38%" class="text-truncate fw-semibold" title="{{ $d['name'] }}">{{ $d['name'] }}</div>
                        <div class="bar">
                            @if($d['expected'])
                                <span style="width:{{ ($d['present'] + $d['half_day']) / $d['expected'] * 100 }}%;background:#22c55e"></span>
                                <span style="width:{{ $d['late'] / $d['expected'] * 100 }}%;background:#f59e0b"></span>
                            @endif
                        </div>
                        <div class="small fw-bold text-end" style="width:58px">{{ $d['attended'] }}/{{ $d['expected'] }}</div>
                    </div>
                @empty
                    <div class="at-muted small py-3 text-center">No employees.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- everyone --}}
    <div class="col-xl-8" id="everyone">
        <div class="at-card">
            <div class="at-card-h">
                <div class="ov-filter d-flex flex-wrap gap-1" id="filters">
                    <button type="button" class="btn btn-primary" data-f="all">Everyone ({{ $day['rows']->count() }})</button>
                    @foreach(['present' => 'On time', 'late' => 'Late', 'not_in' => 'Not in yet', 'absent' => 'Absent', 'on_leave' => 'On leave', 'half_day' => 'Half day', 'off' => 'Day off', 'holiday' => 'Holiday'] as $k => $l)
                        @php $n = $day['rows']->where('status', $k)->count(); @endphp
                        @if($n)<button type="button" class="btn btn-light border" data-f="{{ $k }}">{{ $l }} ({{ $n }})</button>@endif
                    @endforeach
                </div>
                <input type="search" id="find" class="form-control form-control-sm" style="max-width:200px" placeholder="Find a name…">
            </div>
            <div id="refreshBar" class="alert alert-primary rounded-0 border-0 py-2 small mb-0 d-none"><i class="bi bi-arrow-repeat"></i> New scans came in. <a href="javascript:location.reload()">Refresh the list</a></div>
            <div class="table-responsive">
                <table class="table at-table align-middle mb-0" id="tbl">
                    <thead class="table-light"><tr><th>Employee</th><th>Status</th><th>In</th><th>Out</th><th>Hours</th><th></th></tr></thead>
                    <tbody>
                        @forelse($day['rows']->sortBy(fn ($r) => [array_search($r->status, ['late', 'absent', 'not_in', 'present', 'half_day', 'on_leave', 'holiday', 'off']), $r->employee->first_name]) as $r)
                            @php $rec = $r->record; @endphp
                            <tr data-s="{{ $r->status }}" data-n="{{ strtolower($r->employee->full_name . ' ' . $r->employee->employee_id . ' ' . $r->employee->department) }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="at-av">@if($r->employee->passport_photo)<img src="{{ asset('storage/' . $r->employee->passport_photo) }}" alt="">@else{{ $r->employee->initials }}@endif</span>
                                        <div style="min-width:0">
                                            <a href="{{ route('attendance.employee', $r->employee) }}" class="fw-semibold text-decoration-none">{{ $r->employee->full_name }}</a>
                                            <div class="small at-muted text-truncate">{{ $r->employee->employee_id }}{{ $r->employee->department ? ' · ' . $r->employee->department : '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @include('dashboard.attendance.partials.chip', ['status' => $r->status])
                                    @if($rec && $rec->minutes_late)<div class="small" style="color:#b45309">+{{ $rec->minutes_late }} min</div>@endif
                                    @if($rec && $rec->shift && ! $rec->shift->is_default)<div class="small" style="color:{{ $rec->shift->color }}">{{ $rec->shift->name }}{{ $rec->scheduled_start ? ' ' . $rec->scheduled_start : '' }}</div>@endif
                                    @if($rec && $rec->overtime_minutes)<div class="small" style="color:#b45309"><i class="bi bi-clock-history"></i> overtime</div>@endif
                                    @if($r->note && ! $rec)<div class="small at-muted text-truncate" style="max-width:160px">{{ $r->note }}</div>@endif
                                </td>
                                <td class="fw-semibold">
                                    {{ $rec?->in_time ?? '—' }}
                                    @if($rec?->source_in && $rec->clock_in)<i class="bi {{ AR::SOURCES[$rec->source_in]['icon'] ?? '' }} at-muted small" title="{{ AR::SOURCES[$rec->source_in]['label'] ?? '' }}"></i>@endif
                                </td>
                                <td>
                                    @if($rec?->clock_out){{ $rec->out_time }} @if($rec->left_early)<i class="bi bi-box-arrow-left text-warning small" title="Left before {{ $rec->scheduled_end ?? $settings->work_end }}"></i>@endif
                                    @elseif($rec?->missing_out)<span class="small text-danger">no scan out</span>
                                    @elseif($rec?->clock_in)<span class="small text-success">at work</span>
                                    @else — @endif
                                </td>
                                <td class="at-muted">{{ $rec ? $rec->hours : '—' }}</td>
                                <td class="text-end"><a href="{{ route('attendance.employee', $r->employee) }}" class="btn btn-sm btn-light border" title="Month view"><i class="bi bi-calendar3"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center at-muted py-5"><i class="bi bi-people fs-2 d-block"></i>No active employees to show.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- live feed + kiosks --}}
    <div class="col-xl-4">
        <div class="at-card mb-3">
            <div class="at-card-h"><h6><i class="bi bi-broadcast"></i> {{ $isToday ? 'Live scans' : 'Scans that day' }}</h6><a href="{{ route('attendance.qr') }}" class="small text-decoration-none">Kiosks</a></div>
            <div class="ov-feed" id="feed">
                @forelse($scans as $s)
                    @php $ac = ['in' => ['bi-box-arrow-in-right', '#dcfce7', '#16a34a'], 'out' => ['bi-box-arrow-right', '#dbeafe', '#2563eb'], 'duplicate' => ['bi-arrow-repeat', '#f1f5f9', '#64748b'], 'rejected' => ['bi-x-octagon', '#fee2e2', '#dc2626']][$s->action] ?? ['bi-dot', '#f1f5f9', '#64748b']; @endphp
                    <div class="it">
                        <span class="ov-act" style="background:{{ $ac[1] }};color:{{ $ac[2] }}"><i class="bi {{ $ac[0] }}"></i></span>
                        <div style="min-width:0" class="flex-grow-1">
                            <div class="fw-semibold text-truncate">{{ $s->employee?->full_name ?? 'Unrecognised code' }}</div>
                            <div class="small at-muted text-truncate" title="{{ $s->message }}">{{ $s->message }}</div>
                        </div>
                        @if($s->photo)<a href="{{ route('attendance.photo', ['path' => $s->photo]) }}" target="_blank" class="small" title="Photo at scan"><i class="bi bi-camera"></i></a>@endif
                        <span class="small fw-semibold">{{ $s->scanned_at->format('H:i') }}</span>
                    </div>
                @empty
                    <div class="text-center at-muted small py-4" id="feedEmpty"><i class="bi bi-qr-code-scan fs-3 d-block"></i>No kiosk scans {{ $isToday ? 'yet today' : 'that day' }}.</div>
                @endforelse
            </div>
        </div>

        <div class="at-card">
            <div class="at-card-h"><h6><i class="bi bi-tablet"></i> Entrance kiosks</h6></div>
            <div class="px-3 py-2">
                @forelse($kiosks as $k)
                    <div class="d-flex align-items-center gap-2 py-1 small">
                        <i class="bi bi-circle-fill" style="font-size:.55rem;color:{{ ! $k->is_active ? '#cbd5e1' : ($k->is_online ? '#22c55e' : '#ef4444') }}"></i>
                        <span class="fw-semibold">{{ $k->name }}</span>
                        <span class="at-muted ms-auto">{{ ! $k->is_active ? 'switched off' : ($k->is_online ? 'online' : ($k->last_seen_at ? 'last seen ' . $k->last_seen_at->diffForHumans() : 'never opened')) }}</span>
                    </div>
                @empty
                    <div class="small at-muted py-2">No kiosk yet. <a href="{{ route('attendance.qr') }}">Set up the entrance tablet</a> to start recording by QR code.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // filter + search
    const rows = [...document.querySelectorAll('#tbl tbody tr[data-s]')];
    let f = 'all';
    const apply = () => {
        const q = (document.getElementById('find').value || '').toLowerCase();
        rows.forEach(r => { r.style.display = (f === 'all' || r.dataset.s === f || (f === 'present' && r.dataset.s === 'half_day')) && (!q || r.dataset.n.includes(q)) ? '' : 'none'; });
        document.querySelectorAll('#filters button').forEach(b => { b.className = 'btn ' + (b.dataset.f === f ? 'btn-primary' : 'btn-light border'); });
    };
    document.getElementById('filters').addEventListener('click', e => { const b = e.target.closest('button'); if (b) { f = b.dataset.f; apply(); } });
    document.querySelectorAll('[data-filter]').forEach(a => a.addEventListener('click', () => { f = a.dataset.filter; if (f === 'not_in' && !document.querySelector('[data-f=not_in]')) f = 'absent'; apply(); }));
    document.getElementById('find').addEventListener('input', apply);

    @if($isToday)
    // live updates
    let last = {{ $lastScanId }};
    const url = @json(route('attendance.live', array_filter(['department' => $department])));
    const C = 2 * Math.PI * 52;
    const icon = { in: ['bi-box-arrow-in-right', '#dcfce7', '#16a34a'], out: ['bi-box-arrow-right', '#dbeafe', '#2563eb'], duplicate: ['bi-arrow-repeat', '#f1f5f9', '#64748b'], rejected: ['bi-x-octagon', '#fee2e2', '#dc2626'] };
    const esc = s => String(s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    async function poll() {
        if (document.hidden) return;
        try {
            const r = await (await fetch(url + (url.includes('?') ? '&' : '?') + 'since=' + last, { headers: { Accept: 'application/json' } })).json();
            const t = r.totals, set = (k, v) => document.querySelectorAll('[data-k="' + k + '"]').forEach(el => el.textContent = v);
            set('attended', t.attended); set('expected', t.expected); set('ontime', t.present + t.half_day); set('late', t.late);
            set('notin', t.not_in + t.absent); set('on_leave', t.on_leave); set('punctual', t.punctual === null ? '—' : t.punctual + '%');
            document.getElementById('ringTxt').textContent = t.rate === null ? '—' : Math.round(t.rate) + '%';
            document.getElementById('ringArc').setAttribute('stroke-dashoffset', C * (1 - (t.rate || 0) / 100));
            document.getElementById('liveNow').textContent = r.now;
            if (r.scans.length) {
                const feed = document.getElementById('feed'); const empty = document.getElementById('feedEmpty'); if (empty) empty.remove();
                r.scans.forEach(s => {
                    const a = icon[s.action] || icon.duplicate;
                    feed.insertAdjacentHTML('afterbegin', '<div class="it new"><span class="ov-act" style="background:' + a[1] + ';color:' + a[2] + '"><i class="bi ' + a[0] + '"></i></span><div style="min-width:0" class="flex-grow-1"><div class="fw-semibold text-truncate">' + esc(s.name) + '</div><div class="small at-muted text-truncate">' + esc(s.msg) + '</div></div><span class="small fw-semibold">' + s.time + '</span></div>');
                });
                last = r.last;
                if (r.scans.some(s => s.action === 'in' || s.action === 'out')) document.getElementById('refreshBar').classList.remove('d-none');
            }
        } catch (e) {}
    }
    setInterval(poll, 15000);
    @endif
})();
</script>
@endpush
