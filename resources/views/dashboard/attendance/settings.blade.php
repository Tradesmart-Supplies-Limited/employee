@extends('layouts.app')
@section('title', 'Attendance settings')

@section('content')
@php
    use App\Models\AttendanceSetting as S;
    $s = $settings;
    $methods = [
        ['QR code kiosk', 'bi-qr-code-scan', '#185FA5', 'active', 'A tablet at the entrance scans each employee\'s QR badge. Time-stamped in real time, with a photo at each scan.', route('attendance.qr'), 'Manage kiosks & badges'],
        ['Timesheet import', 'bi-file-earmark-spreadsheet', '#16a34a', 'active', 'Download a CSV / Excel timesheet, fill it in, upload it back. Good for sites without a tablet.', route('attendance.import'), 'Import a timesheet'],
        ['Daily register', 'bi-pencil-square', '#0891b2', 'active', 'HR types or corrects times for a day by hand: missed scans, visitors to other sites, corrections.', route('attendance.register'), 'Open the register'],
        ['Fingerprint scanner', 'bi-fingerprint', '#7c3aed', 'soon', 'Connect a biometric fingerprint terminal (e.g. ZKTeco) so clock-ins flow straight into the register.', null, null],
        ['Facial recognition', 'bi-person-bounding-box', '#db2777', 'soon', 'Recognise employees by face on the entrance tablet: no badge needed, with a liveness check to stop photo spoofing.', null, null],
    ];
@endphp

@include('dashboard.attendance.partials.nav', ['title' => 'Settings & recording methods', 'subtitle' => 'Times are in <strong>' . e($tz) . '</strong> (now ' . $now->format('H:i, D d M') . ').'])

<style>
.st-method { display: flex; gap: .9rem; padding: .95rem 1.1rem; border-bottom: 1px solid var(--admin-border, #eef2f7); }
.st-method:last-child { border-bottom: 0; }
.st-method .ic { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: var(--c); background: color-mix(in srgb, var(--c) 12%, transparent); }
.st-method.soon { background: repeating-linear-gradient(-45deg, transparent 0 12px, rgba(148,163,184,.06) 12px 24px); }
.st-method.soon .ic { filter: grayscale(.3); }
.st-days .btn { min-width: 52px; }
.st-shifts td, .st-shifts th { font-size: .82rem; }
.st-shifts input[type=time] { width: 96px; min-width: 0; padding: .25rem .35rem; font-size: .78rem; }
.st-shifts td, .st-shifts th { padding-left: .4rem; padding-right: .4rem; }
.st-shifts .form-control-color { width: 34px; padding: .15rem; flex-shrink: 0; }
@media (min-width: 992px) { .st-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); } .st-grid .st-method { border-right: 1px solid var(--admin-border, #eef2f7); } }
</style>

<div class="row g-3">
    <div class="col-12">
        <div class="at-card mb-3">
            <div class="at-card-h"><h6><i class="bi bi-ui-checks-grid"></i> How attendance is recorded</h6></div>
            <div class="st-grid">
            @foreach($methods as [$name, $icon, $c, $state, $desc, $url, $cta])
                <div class="st-method {{ $state }}" style="--c:{{ $c }}">
                    <span class="ic"><i class="bi {{ $icon }}"></i></span>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <strong>{{ $name }}</strong>
                            @if($state === 'active')<span class="badge rounded-pill" style="background:#dcfce7;color:#166534">Available</span>
                            @else<span class="at-soon"><i class="bi bi-cone-striped"></i> Under development</span>@endif
                        </div>
                        <div class="small at-muted mt-1">{{ $desc }}</div>
                        @if($url)<a href="{{ $url }}" class="small fw-semibold text-decoration-none">{{ $cta }} <i class="bi bi-arrow-right"></i></a>
                        @else<div class="small mt-1" style="color:#92400e"><i class="bi bi-info-circle"></i> Coming in a future update. Scans from these devices will appear in the same register and reports.</div>@endif
                    </div>
                </div>
            @endforeach
            </div>
        </div>

        <div class="at-card p-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-envelope-paper"></i> Midnight report</h6>
            <p class="small at-muted mb-2">At 00:00 the day is closed: anyone expected who never scanned and has no approved leave is marked <strong>Absent</strong>, missing clock-outs are flagged, and the report goes to {{ count($recipients) }} address(es).
                @if($s->last_closed_date)<br>Last day closed: <strong>{{ $s->last_closed_date->format('D d M Y') }}</strong>{{ $s->last_report_at ? ' · report sent ' . $s->last_report_at->diffForHumans() : '' }}.@endif</p>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('attendance.settings.test') }}">@csrf
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-send"></i> Send me a test (today so far)</button>
                </form>
                <form method="POST" action="{{ route('attendance.settings.send-day') }}" class="d-flex gap-1">@csrf
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $now->copy()->subDay()->toDateString() }}" max="{{ $now->copy()->subDay()->toDateString() }}" style="width:145px">
                    <button class="btn btn-sm btn-outline-secondary" title="Close that day and send its report to everyone on the list"><i class="bi bi-arrow-repeat"></i> Re-send day</button>
                </form>
            </div>
            <div class="small at-muted mt-2"><i class="bi bi-lightning-charge"></i> Runs by itself: the entrance tablet and the admin dashboard trigger it after midnight. For exact timing, also run <code>php artisan schedule:run</code> every minute in Windows Task Scheduler.</div>
        </div>
    </div>

    <div class="col-12 order-first">
        <form method="POST" action="{{ route('attendance.settings.update') }}" class="at-card">
            @csrf @method('PUT')
            <div class="at-card-h"><h6><i class="bi bi-clock"></i> Shifts</h6><span class="small at-muted">24-hour times · blank = shift doesn't run that day</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 st-shifts">
                    <thead class="table-light"><tr><th>Shift</th><th class="text-center" title="Everyone not on a rota works this shift">Default</th>@foreach(\App\Models\AttendanceShift::DAY_TYPES as $dk => $dl)<th>{{ $dl }}</th>@endforeach<th></th></tr></thead>
                    <tbody>
                        @foreach($shifts as $sh)
                            <tr class="{{ $sh->is_active ? '' : 'opacity-50' }}">
                                <td style="min-width:150px">
                                    <div class="d-flex gap-1 align-items-center">
                                        <input type="color" name="shifts[{{ $sh->id }}][color]" value="{{ old("shifts.{$sh->id}.color", $sh->color) }}" class="form-control form-control-color form-control-sm" title="Colour on the rota">
                                        <input type="text" name="shifts[{{ $sh->id }}][name]" value="{{ old("shifts.{$sh->id}.name", $sh->name) }}" class="form-control form-control-sm" required maxlength="60">
                                    </div>
                                </td>
                                <td class="text-center"><input class="form-check-input" type="radio" name="default_shift" value="{{ $sh->id }}" @checked((int) old('default_shift', $shifts->firstWhere('is_default', true)?->id) === $sh->id) aria-label="Default shift"></td>
                                @foreach(array_keys(\App\Models\AttendanceShift::DAY_TYPES) as $dk)
                                    <td style="min-width:150px">
                                        <div class="d-flex gap-1 align-items-center">
                                            <input type="time" name="shifts[{{ $sh->id }}][{{ $dk }}_start]" value="{{ old("shifts.{$sh->id}.{$dk}_start", $sh->{$dk . '_start'}) }}" class="form-control form-control-sm">
                                            <span class="at-muted">–</span>
                                            <input type="time" name="shifts[{{ $sh->id }}][{{ $dk }}_end]" value="{{ old("shifts.{$sh->id}.{$dk}_end", $sh->{$dk . '_end'}) }}" class="form-control form-control-sm @error("shifts.{$sh->id}.{$dk}_end") is-invalid @enderror">
                                        </div>
                                        @error("shifts.{$sh->id}.{$dk}_end")<div class="text-danger small">{{ $message }}</div>@enderror
                                        @error("shifts.{$sh->id}.{$dk}_start")<div class="text-danger small">{{ $message }}</div>@enderror
                                    </td>
                                @endforeach
                                <td>@unless($sh->is_default)<div class="form-check" title="Hide from new rotas"><input class="form-check-input" type="checkbox" name="shifts[{{ $sh->id }}][archived]" value="1" id="ar{{ $sh->id }}" @checked(! $sh->is_active)><label class="form-check-label small" for="ar{{ $sh->id }}">Archive</label></div>@endunless</td>
                            </tr>
                        @endforeach
                        <tr class="table-light">
                            <td style="min-width:150px">
                                <div class="d-flex gap-1 align-items-center">
                                    <input type="color" name="new_shift[color]" value="#0f766e" class="form-control form-control-color form-control-sm">
                                    <input type="text" name="new_shift[name]" value="{{ old('new_shift.name') }}" class="form-control form-control-sm" placeholder="+ Add a shift" maxlength="60">
                                </div>
                            </td>
                            <td></td>
                            @foreach(array_keys(\App\Models\AttendanceShift::DAY_TYPES) as $dk)
                                <td><div class="d-flex gap-1 align-items-center"><input type="time" name="new_shift[{{ $dk }}_start]" class="form-control form-control-sm"><span class="at-muted">–</span><input type="time" name="new_shift[{{ $dk }}_end]" class="form-control form-control-sm"></div></td>
                            @endforeach
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-3 pt-2 small at-muted"><i class="bi bi-info-circle"></i> Sundays and public holidays are days off. Sunday / holiday times are used only for people put on the rota (or asked to come in) on those days. That work is recorded as <strong>overtime</strong>.</div>

            <div class="at-card-h border-top mt-2"><h6><i class="bi bi-sliders"></i> Rules</h6></div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <label class="form-label small fw-semibold">Late after</label>
                        <div class="input-group"><input type="number" name="grace_minutes" min="0" max="240" value="{{ old('grace_minutes', $s->grace_minutes) }}" class="form-control"><span class="input-group-text">min</span></div>
                        <div class="form-text">grace after the shift starts</div>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small fw-semibold">Half day if under</label>
                        <div class="input-group"><input type="number" name="half_day_minutes" min="0" max="720" step="15" value="{{ old('half_day_minutes', $s->half_day_minutes) }}" class="form-control"><span class="input-group-text">min</span></div>
                        <div class="form-text">worked (240 = 4 h)</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Ignore repeat scans within</label>
                        <div class="input-group"><input type="number" name="scan_cooldown_minutes" min="0" max="120" value="{{ old('scan_cooldown_minutes', $s->scan_cooldown_minutes) }}" class="form-control"><span class="input-group-text">min</span></div>
                        <div class="form-text">stops a double scan clocking someone out</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold d-block">Working days <span class="fw-normal at-muted">(for everyone not on a rota)</span></label>
                        <div class="btn-group st-days flex-wrap" role="group">
                            @foreach(S::DAY_NAMES as $n => $d)
                                <input type="checkbox" class="btn-check" name="working_days[]" value="{{ $n }}" id="wd{{ $n }}" @checked(in_array($n, old('working_days', $s->working_days ?? [1, 2, 3, 4, 5])))>
                                <label class="btn btn-sm btn-outline-primary" for="wd{{ $n }}">{{ $d }}</label>
                            @endforeach
                        </div>
                        @error('working_days')<div class="text-danger small">{{ $message }}</div>@enderror
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="count_holidays_off" value="1" id="hol" @checked($s->count_holidays_off)>
                            <label class="form-check-label small" for="hol">Zambian public holidays are days off (nobody is marked absent unless rostered)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="at-card-h border-top"><h6><i class="bi bi-calendar-week"></i> Shift rota</h6>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" name="rota_enabled" value="1" id="rotaOn" @checked(old('rota_enabled', $s->rota_enabled))>
                    <label class="form-check-label small fw-semibold" for="rotaOn">Use weekly rotas</label>
                </div>
            </div>
            <div class="p-3">
                <div class="small at-muted mb-2">Departments that plan the week with a rota. Their staff are expected only when the <strong>published</strong> rota puts them on a shift. Everyone else follows the default shift and working days.</div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @forelse($departments as $dep)
                        <input type="checkbox" class="btn-check" name="rota_departments[]" value="{{ $dep }}" id="rd{{ $loop->index }}" @checked(in_array($dep, old('rota_departments', $s->rota_departments ?? []), true))>
                        <label class="btn btn-sm btn-outline-secondary" for="rd{{ $loop->index }}"><i class="bi bi-diagram-3"></i> {{ $dep }}</label>
                    @empty
                        <span class="small at-muted">No departments on employee records yet.</span>
                    @endforelse
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="overtime_auto" value="1" id="otAuto" @checked(old('overtime_auto', $s->overtime_auto))>
                    <label class="form-check-label small" for="otAuto">Turn Sunday / public-holiday hours into overtime claims automatically (pre-approved when on the published rota, otherwise sent to HR)</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="comp_off_required" value="1" id="compOff" @checked(old('comp_off_required', $s->comp_off_required))>
                    <label class="form-check-label small" for="compOff">Warn on the rota when someone works Sunday without a day off during that week</label>
                </div>
                <a href="{{ route('attendance.rota.index') }}" class="small fw-semibold"><i class="bi bi-calendar-week"></i> Open the rotas</a>
            </div>

            <div class="at-card-h border-top"><h6><i class="bi bi-envelope"></i> Daily report email</h6>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" name="report_enabled" value="1" id="rep" @checked($s->report_enabled)>
                    <label class="form-check-label small fw-semibold" for="rep">Send at midnight</label>
                </div>
            </div>
            <div class="p-3">
                <label class="form-label small fw-semibold">Management emails</label>
                <textarea name="report_recipients" rows="2" class="form-control @error('report_recipients') is-invalid @enderror" placeholder="md@company.zm, operations@company.zm">{{ old('report_recipients', implode(', ', $s->report_recipients ?? [])) }}</textarea>
                @error('report_recipients')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Separate with commas.</div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="report_include_hr" value="1" id="inhr" @checked($s->report_include_hr)>
                    <label class="form-check-label small" for="inhr">Also send to HR (HR_NOTIFICATION_EMAILS and every admin / HR user)</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="report_attach_csv" value="1" id="csv" @checked($s->report_attach_csv)>
                    <label class="form-check-label small" for="csv">Attach the full day's register as a spreadsheet (CSV)</label>
                </div>
                @if($recipients)
                    <div class="small at-muted mt-2"><i class="bi bi-people"></i> Currently going to: {{ implode(', ', $recipients) }}</div>
                @else
                    <div class="small text-danger mt-2"><i class="bi bi-exclamation-triangle"></i> Nobody will get the report yet. Add an email above.</div>
                @endif
            </div>
            <div class="px-3 pb-3"><button class="btn btn-primary"><i class="bi bi-check2"></i> Save settings</button></div>
        </form>
    </div>
</div>
@endsection
