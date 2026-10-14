{{--
    Leave dates with live working-day count, holiday skipping and auto return date.
    Vars:
      $holidays        ['Y-m-d' => name]
      $dayRules        ['exclude_sundays'=>bool, 'exclude_saturdays'=>bool, 'exclude_holidays'=>bool]
      $canChangeRules  bool
      $balance         float|null   available days (null = don't show balance maths)
      $deductibleTypes array
      $allowNegative   bool
      $typeSelect      id of the leave-type <select> (optional)
--}}
@php
    $rule = fn ($k) => (bool) old($k, old('date_from') ? false : $dayRules[$k]);
    $balance = $balance ?? null;
@endphp

<style>
.dp-mode .btn { font-size: .8rem; }
.dp-summary { border: 1px solid var(--admin-border, #e2e8f0); border-radius: 12px; overflow: hidden; background: var(--admin-surface, #fff); }
.dp-stats { display: grid; grid-template-columns: repeat(4, 1fr); }
.dp-stats > div { padding: .8rem 1rem; border-right: 1px solid var(--admin-border, #e2e8f0); }
.dp-stats > div:last-child { border-right: 0; }
.dp-stats small { display: block; font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--admin-muted, #64748b); }
.dp-stats strong { font-size: 1.35rem; line-height: 1.2; }
.dp-stats .dp-sub { font-size: .74rem; color: var(--admin-muted, #64748b); }
.dp-skipped { padding: .6rem 1rem; border-top: 1px dashed var(--admin-border, #e2e8f0); font-size: .78rem; display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; }
.dp-chip { padding: .15rem .55rem; border-radius: 999px; font-weight: 600; font-size: .72rem; }
.dp-chip.hol { background: #fef3c7; color: #92400e; }
.dp-chip.wk  { background: #e2e8f0; color: #334155; }
.dp-msg { padding: .55rem 1rem; font-size: .8rem; border-top: 1px solid var(--admin-border, #e2e8f0); }
.dp-rules .form-check { margin-right: 1rem; }
@media (max-width: 640px) { .dp-stats { grid-template-columns: 1fr 1fr; } .dp-stats > div:nth-child(2) { border-right: 0; } }
</style>

<div class="row g-3" id="dayPicker">
    <div class="col-12 d-flex align-items-center gap-2 flex-wrap dp-mode">
        <span class="small text-muted me-1">Choose by:</span>
        <div class="btn-group btn-group-sm" role="group">
            <input type="radio" class="btn-check" name="dp_mode" id="dpModeDates" value="dates" checked>
            <label class="btn btn-outline-primary" for="dpModeDates"><i class="bi bi-calendar-range"></i> Start &amp; end date</label>
            <input type="radio" class="btn-check" name="dp_mode" id="dpModeDays" value="days">
            <label class="btn btn-outline-primary" for="dpModeDays"><i class="bi bi-123"></i> Start date &amp; number of days</label>
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label">First day of leave <span class="text-danger">*</span></label>
        <input type="date" name="date_from" id="dateFrom" class="form-control" value="{{ old('date_from') }}" required>
    </div>
    <div class="col-md-4" id="dpToWrap">
        <label class="form-label">Last day of leave <span class="text-danger">*</span></label>
        <input type="date" name="date_to" id="dateTo" class="form-control" value="{{ old('date_to') }}" required>
    </div>
    <div class="col-md-4 d-none" id="dpDaysWrap">
        <label class="form-label">Number of leave days</label>
        <input type="number" min="1" max="120" id="dpDays" class="form-control" placeholder="e.g. 5">
        <small class="text-muted">The last day is worked out for you.</small>
    </div>
    <div class="col-md-4">
        <label class="form-label">Back at work on</label>
        <input type="text" id="dpReturn" class="form-control" readonly placeholder="Worked out automatically" tabindex="-1">
        <small class="text-muted">Next working day after your leave.</small>
    </div>

    <div class="col-12 dp-rules">
        <span class="small text-muted d-block mb-1">Don't count these days as leave:</span>
        @foreach(['exclude_sundays' => 'Sundays', 'exclude_holidays' => 'Zambian public holidays', 'exclude_saturdays' => 'Saturdays'] as $key => $label)
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="{{ $key }}" id="{{ $key }}" value="1"
                       @checked($rule($key)) @disabled(! $canChangeRules)>
                <label class="form-check-label small" for="{{ $key }}">{{ $label }}</label>
                @if(! $canChangeRules && $rule($key))<input type="hidden" name="{{ $key }}" value="1">@endif
            </div>
        @endforeach
    </div>

    <div class="col-12">
        <div class="dp-summary" aria-live="polite">
            <div class="dp-stats">
                <div><small>Leave days</small><strong id="dpWorking">–</strong><div class="dp-sub" id="dpCal">pick your dates</div></div>
                <div><small>Skipped</small><strong id="dpSkippedN">–</strong><div class="dp-sub">Sundays / holidays</div></div>
                <div><small>Back at work</small><strong id="dpReturnBig" style="font-size:1rem">–</strong><div class="dp-sub" id="dpReturnDay"></div></div>
                <div><small>{{ $balance === null ? 'Calendar' : 'Balance after' }}</small><strong id="dpBalance">{{ $balance === null ? '–' : rtrim(rtrim(number_format($balance, 1), '0'), '.') }}</strong><div class="dp-sub" id="dpBalanceSub">{{ $balance === null ? '' : 'days available now' }}</div></div>
            </div>
            <div class="dp-skipped d-none" id="dpSkipped"></div>
            <div class="dp-msg d-none" id="dpMsg"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const HOLIDAYS = @json((object) $holidays);
    const BALANCE = @json($balance);
    const DEDUCTIBLE = @json(array_values($deductibleTypes ?? []));
    const ALLOW_NEG = @json((bool) ($allowNegative ?? false));
    const typeSel = document.getElementById(@json($typeSelect ?? 'leaveType'));

    const $ = id => document.getElementById(id);
    const from = $('dateFrom'), to = $('dateTo'), daysIn = $('dpDays');
    const fmtKey = d => d.toISOString().slice(0, 10);
    const parse = v => { if (!v) return null; const [y, m, d] = v.split('-').map(Number); return new Date(Date.UTC(y, m - 1, d)); };
    const addDays = (d, n) => new Date(d.getTime() + n * 86400000);
    const nice = d => d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
    const short = d => d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'UTC' });
    const num = n => (Math.round(n * 10) / 10).toString();

    function rules() {
        return { sun: $('exclude_sundays').checked, sat: $('exclude_saturdays').checked, hol: $('exclude_holidays').checked };
    }
    function reason(d, r) {
        const k = fmtKey(d);
        if (r.hol && HOLIDAYS[k]) return { text: HOLIDAYS[k], cls: 'hol' };
        if (r.sun && d.getUTCDay() === 0) return { text: 'Sunday', cls: 'wk' };
        if (r.sat && d.getUTCDay() === 6) return { text: 'Saturday', cls: 'wk' };
        return null;
    }
    function nextWorking(d, r) { let x = addDays(d, 1), i = 0; while (reason(x, r) && i++ < 30) x = addDays(x, 1); return x; }

    function endFromDays() {
        const s = parse(from.value), n = parseInt(daysIn.value, 10);
        if (!s || !(n > 0)) return;
        const r = rules(); let d = s, counted = 0, guard = 0;
        while (guard++ < 400) { if (!reason(d, r)) { counted++; if (counted === n) break; } d = addDays(d, 1); }
        to.value = fmtKey(d);
    }

    function render() {
        const s = parse(from.value), e = parse(to.value), msg = $('dpMsg'), skipped = $('dpSkipped');
        msg.className = 'dp-msg d-none'; skipped.classList.add('d-none');
        if (!s || !e) return;
        if (e < s) { msg.className = 'dp-msg text-danger'; msg.textContent = 'The last day must be on or after the first day.'; return; }

        const r = rules(); let working = 0, cal = 0; const skip = [];
        for (let d = s; d <= e; d = addDays(d, 1)) { cal++; const why = reason(d, r); if (why) skip.push([d, why]); else working++; }
        const back = nextWorking(e, r);

        $('dpWorking').textContent = working;
        $('dpCal').textContent = `${cal} calendar day(s)`;
        $('dpSkippedN').textContent = skip.length;
        $('dpReturn').value = nice(back);
        $('dpReturnBig').textContent = back.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
        $('dpReturnDay').textContent = back.toLocaleDateString('en-GB', { weekday: 'long', timeZone: 'UTC' });
        if (!daysIn.matches(':focus')) daysIn.value = working || '';

        if (skip.length) {
            skipped.classList.remove('d-none');
            skipped.innerHTML = '<span class="text-muted me-1">Not counted:</span>' +
                skip.map(([d, w]) => `<span class="dp-chip ${w.cls}">${short(d)} · ${w.text}</span>`).join('');
        }

        const BAL = (typeof window.__dpBalance === 'number' && !isNaN(window.__dpBalance)) ? window.__dpBalance : BALANCE;
        if (BAL !== null) {
            const counts = !typeSel || !typeSel.value || DEDUCTIBLE.includes(typeSel.value);
            const left = counts ? BAL - working : BAL;
            $('dpBalance').textContent = num(left);
            $('dpBalance').className = left < 0 ? 'text-danger' : 'text-success';
            $('dpBalanceSub').textContent = counts ? `of ${num(BAL)} available` : `${typeSel.value} leave isn't deducted`;
            if (counts && left < 0) {
                msg.className = 'dp-msg ' + (ALLOW_NEG ? 'text-warning' : 'text-danger');
                msg.textContent = `This is ${num(-left)} day(s) more than you have available.`;
            }
        }
        if (working === 0) { msg.className = 'dp-msg text-danger'; msg.textContent = 'Every selected day is a Sunday or public holiday, so no leave days would be used.'; }
    }

    function setMode(m) {
        const days = m === 'days';
        $('dpDaysWrap').classList.toggle('d-none', !days);
        to.readOnly = days;
        if (days) { daysIn.focus(); endFromDays(); }
        render();
    }

    document.querySelectorAll('input[name="dp_mode"]').forEach(r => r.addEventListener('change', () => setMode(r.value)));
    from.addEventListener('change', () => { if ($('dpModeDays').checked) endFromDays(); render(); });
    to.addEventListener('change', render);
    daysIn.addEventListener('input', () => { endFromDays(); render(); });
    ['exclude_sundays', 'exclude_saturdays', 'exclude_holidays'].forEach(id => $(id).addEventListener('change', () => { if ($('dpModeDays').checked) endFromDays(); render(); }));
    if (typeSel) typeSel.addEventListener('change', render);
    render();
})();
</script>
