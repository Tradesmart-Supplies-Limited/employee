{{--
    The leave application form, laid out like the official paper "Leave Request Form"
    but filled in step by step with live help.

    Used by: portal (employees), the staff area (managers / supervisors / HR applying
    for their own leave) and the public form.

    Vars:
      $action          form URL
      $employee        Employee|null  (null = public form: name, number, email typed in)
      $types           allowed leave types
      $deductibleTypes types that reduce the balance
      $pendingDays     float  days already in pending requests
      $allowNegative   bool
      $holidays, $dayRules, $canChangeRules   for the day picker
      $approvalRoute   [['key' => 'supervisor', 'title' => 'Supervisor', 'name' => 'Mary Mulenga', 'note' => null], ...]
      $cancelUrl       string|null
      $origin          string|null  posted back as _origin
--}}
@php
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $public = ! $employee;
    $balance = $employee ? (float) $employee->leave_balance - (float) $pendingDays : null;

    // Same order and numbering as the paper form.
    $catalogue = [
        'Annual'        => ['bi-sun',              'Your yearly leave'],
        'Sick'          => ['bi-thermometer-half', 'Attach a medical certificate'],
        'Maternity'     => ['bi-balloon-heart',    'Before and after the birth'],
        'Paternal'      => ['bi-people',           'For new fathers'],
        'Compassionate' => ['bi-flower1',          'Bereavement or a family emergency'],
        'Unpaid'        => ['bi-wallet2',          'Time off without pay'],
        'Other'         => ['bi-three-dots',       'Tell us what it is for'],
        'Study'         => ['bi-mortarboard',      'Exams or a course'],
    ];
    $catalogue = array_intersect_key($catalogue, array_flip($types));
    $reasonNeeded = ['Compassionate', 'Unpaid', 'Other', 'Study'];
    $pickedType = old('leave_type');
@endphp

<style>
.af { --af-line: var(--admin-border, #dde3ea); --af-muted: var(--admin-muted, #64748b); --af-surface: var(--admin-surface, #fff); --af-soft: var(--admin-surface-soft, #f6f8fb); --af-text: var(--admin-text, #0f172a); color: var(--af-text); }
.af-paper { background: var(--af-surface); border: 1px solid var(--af-line); border-radius: 14px; overflow: hidden; box-shadow: 0 10px 30px rgba(15,23,42,.06); }
.af-head { display: flex; align-items: center; gap: 16px; padding: 18px 24px; border-bottom: 4px solid var(--brand, #185FA5); }
.af-logo { width: 54px; height: 54px; border-radius: 10px; display: grid; place-items: center; background: #fff; border: 1px solid var(--af-line); overflow: hidden; flex-shrink: 0; }
.af-logo img { width: 100%; height: 100%; object-fit: contain; }
.af-logo span { font-weight: 800; color: var(--brand-text, #185FA5); }
.af-company { font-weight: 800; font-size: 1.1rem; color: var(--brand-text, #185FA5); line-height: 1.2; }
.af-sub { font-size: .78rem; color: var(--af-muted); }
.af-title { margin-left: auto; text-align: right; }
.af-title b { display: block; font-size: .78rem; letter-spacing: .12em; text-transform: uppercase; text-decoration: underline; }
.af-title small { font-size: .75rem; color: var(--af-muted); }
.af-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; }
.af-main { min-width: 0; border-right: 1px solid var(--af-line); }
.af-sec { padding: 0 24px 22px; }
.af-sec + .af-sec { border-top: 1px solid var(--af-line); }
.af-bar { display: flex; align-items: center; gap: 10px; margin: 0 -24px 16px; padding: 7px 24px; background: #33383f; color: #fff; font-size: .74rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
.af-num { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; background: var(--brand, #185FA5); font-size: .72rem; letter-spacing: 0; flex-shrink: 0; }
.af-bar .af-done { background: #16a34a; }
.af-bar small { margin-left: auto; font-weight: 500; letter-spacing: 0; text-transform: none; opacity: .75; }
.af-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--af-muted); margin-bottom: 3px; }
.af-paperfield { border-bottom: 1px dotted var(--af-line); padding: 2px 0 6px; font-weight: 600; min-height: 1.9em; overflow-wrap: anywhere; }
.af-input { width: 100%; border: 0; border-bottom: 1.5px solid var(--af-line); background: transparent; padding: 4px 2px 6px; font-weight: 600; color: var(--af-text); border-radius: 0; }
.af-input:focus { outline: none; border-color: var(--brand, #185FA5); box-shadow: 0 1px 0 var(--brand, #185FA5); }
.af-types { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.af-type { position: relative; display: flex; flex-direction: column; gap: 4px; padding: 12px 12px 10px; border: 1.5px solid var(--af-line); border-radius: 12px; cursor: pointer; background: var(--af-surface); transition: border-color .15s, box-shadow .15s, transform .15s; margin: 0; }
.af-type:hover { border-color: rgba(var(--brand-rgb, 24,95,165), .5); transform: translateY(-1px); }
.af-type input { position: absolute; opacity: 0; pointer-events: none; }
.af-type .no { position: absolute; top: 8px; right: 10px; font-size: .7rem; color: var(--af-muted); font-weight: 700; }
.af-type i { font-size: 1.3rem; color: var(--brand-text, #185FA5); }
.af-type b { font-size: .92rem; }
.af-type span { font-size: .72rem; color: var(--af-muted); line-height: 1.3; }
.af-type em { font-style: normal; font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #b45309; }
.af-type:has(input:checked) { border-color: var(--brand, #185FA5); box-shadow: 0 0 0 3px rgba(var(--brand-rgb, 24,95,165), .15); background: rgba(var(--brand-rgb, 24,95,165), .05); }
.af-type:has(input:focus-visible) { outline: 2px solid var(--brand, #185FA5); outline-offset: 2px; }
.af-req { color: #dc2626; }
.af-opt { font-weight: 400; text-transform: none; letter-spacing: 0; }
.af-drop { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1.5px dashed var(--af-line); border-radius: 12px; cursor: pointer; background: var(--af-soft); margin: 0; }
.af-drop:hover, .af-drop.is-over { border-color: var(--brand, #185FA5); }
.af-drop i { font-size: 1.5rem; color: var(--brand-text, #185FA5); }
.af-drop input { position: absolute; width: 1px; height: 1px; opacity: 0; }
.af-route { display: flex; flex-wrap: wrap; align-items: stretch; gap: 6px; }
.af-route > div { flex: 1 1 120px; padding: 10px 12px; border-radius: 10px; background: var(--af-soft); border: 1px solid var(--af-line); }
.af-route b { display: block; font-size: .85rem; }
.af-route small { color: var(--af-muted); font-size: .72rem; }
.af-route .is-you { background: rgba(var(--brand-rgb, 24,95,165), .08); border-color: rgba(var(--brand-rgb, 24,95,165), .3); }
.af-route .is-final { border-color: #16a34a; }
.af-route .af-arrow { flex: 0 0 auto; padding: 0; background: none; border: 0; display: flex; align-items: center; color: var(--af-muted); }
.af-declare { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 10px; background: var(--af-soft); border: 1px solid var(--af-line); font-size: .86rem; margin: 0; }
.af-side { padding: 18px; background: var(--af-soft); }
.af-sticky { position: sticky; top: 84px; }
.af-sum h6 { font-size: .74rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--af-muted); margin-bottom: 10px; }
.af-sum dl { margin: 0; display: grid; grid-template-columns: auto 1fr; gap: 8px 12px; font-size: .86rem; }
.af-sum dt { color: var(--af-muted); font-weight: 500; }
.af-sum dd { margin: 0; text-align: right; font-weight: 700; overflow-wrap: anywhere; }
.af-big { margin: 14px 0; padding: 14px; border-radius: 12px; background: var(--af-surface); border: 1px solid var(--af-line); text-align: center; }
.af-big strong { display: block; font-size: 2.2rem; line-height: 1; color: var(--brand-text, #185FA5); }
.af-big span { font-size: .78rem; color: var(--af-muted); }
.af-meter { height: 8px; border-radius: 999px; background: var(--af-line); overflow: hidden; margin-top: 6px; }
.af-meter i { display: block; height: 100%; width: 0; background: #16a34a; transition: width .3s; }
.af-meter i.is-low { background: #dc2626; }
.af-tip { font-size: .76rem; color: var(--af-muted); margin-top: 12px; }
.af-submit { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; padding: 16px 24px; border-top: 1px solid var(--af-line); background: var(--af-soft); }
.af-foot { padding: 10px 24px; font-size: .76rem; color: var(--af-muted); border-top: 1px solid var(--af-line); font-style: italic; }
.af .dp-mode .btn-group { flex-wrap: wrap; }
@media (max-width: 991.98px) {
    .af-grid { grid-template-columns: 1fr; }
    .af-main { border-right: 0; }
    .af-side { border-top: 1px solid var(--af-line); }
    .af-sticky { position: static; }
    .af-types { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 575.98px) {
    .af-head { flex-wrap: wrap; padding: 14px 16px; }
    .af-title { margin-left: 0; text-align: left; width: 100%; }
    .af-sec { padding: 0 16px 18px; }
    .af-bar { margin: 0 -16px 14px; padding: 7px 16px; }
    .af-submit { padding: 14px 16px; }
    .af-submit .btn { flex: 1 1 auto; }
    .af-route .af-arrow { display: none; }
}
html[data-theme=dark] .af-logo { background: #fff; }
html[data-theme=dark] .af-type em { color: #fbbf24; }
</style>

<div class="af">
@if($errors->any())
    <div class="alert alert-danger small">
        <strong><i class="bi bi-exclamation-triangle me-1"></i> Please check the form:</strong>
        <ul class="mb-0 mt-1 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="applyForm" novalidate>
    @csrf
    @if(! empty($origin))<input type="hidden" name="_origin" value="{{ $origin }}">@endif

    <div class="af-paper">
        {{-- Letterhead, as on the paper form --}}
        <div class="af-head">
            <div class="af-logo">
                <img src="{{ config('company.logo_url') }}" alt="" onerror="this.parentElement.innerHTML='<span>TS</span>'">
            </div>
            <div>
                <div class="af-company">{{ config('company.name', 'Tradesmart Supplies Limited') }}</div>
                <div class="af-sub">Human Resources · Leave application</div>
            </div>
            <div class="af-title">
                <b>Leave Request Form</b>
                <small>Date submitted: {{ now()->format('d M Y') }}</small>
            </div>
        </div>

        <div class="af-grid">
        <div class="af-main">

            {{-- 1. Requestor --}}
            <div class="af-sec">
                <div class="af-bar"><span class="af-num {{ $public ? '' : 'af-done' }}">{!! $public ? '1' : '<i class="bi bi-check"></i>' !!}</span> To be completed by requestor <small>{{ $public ? 'your details' : 'filled in from your employee record' }}</small></div>
                @if($employee)
                    <div class="row g-3">
                        <div class="col-sm-6"><div class="af-label">Name</div><div class="af-paperfield">{{ $employee->full_name }}</div></div>
                        <div class="col-sm-6"><div class="af-label">Employee No.</div><div class="af-paperfield">{{ $employee->employee_id ?: '—' }}</div></div>
                        <div class="col-sm-6"><div class="af-label">Position</div><div class="af-paperfield">{{ $employee->position ?: '—' }}</div></div>
                        <div class="col-sm-6"><div class="af-label">Department</div><div class="af-paperfield">{{ $employee->department ?: '—' }}</div></div>
                    </div>
                @else
                    <div class="row g-3">
                        <div class="col-sm-7">
                            <label class="af-label" for="afName">Name <span class="af-req">*</span></label>
                            <input class="af-input @error('employee_name') is-invalid @enderror" id="afName" name="employee_name" value="{{ old('employee_name') }}" required autocomplete="name">
                        </div>
                        <div class="col-sm-5">
                            <label class="af-label" for="afNo">Employee No.</label>
                            <input class="af-input" id="afNo" name="employee_number" value="{{ old('employee_number') }}" placeholder="e.g. OPS-12">
                        </div>
                        <div class="col-sm-6">
                            <label class="af-label" for="afPos">Position</label>
                            <input class="af-input" id="afPos" name="position" value="{{ old('position') }}">
                        </div>
                        <div class="col-sm-6">
                            <label class="af-label" for="afEmail">Email <span class="af-req">*</span></label>
                            <input class="af-input @error('email') is-invalid @enderror" id="afEmail" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                        </div>
                    </div>
                    <div class="small text-muted mt-2"><i class="bi bi-info-circle"></i> Use your employee number or work email so we can find your record and leave balance.</div>
                @endif
            </div>

            {{-- 2. Leave type --}}
            <div class="af-sec">
                <div class="af-bar"><span class="af-num">{{ $public ? 2 : 1 }}</span> Leave applied for <small>pick one</small></div>
                <div class="af-types" role="radiogroup" aria-label="Leave type">
                    @foreach($catalogue as $type => [$icon, $hint])
                        <label class="af-type">
                            <input type="radio" name="leave_type" value="{{ $type }}" @checked($pickedType === $type) required>
                            <span class="no">{{ $loop->iteration }}.</span>
                            <i class="bi {{ $icon }}"></i>
                            <b>{{ $type }}</b>
                            <span>{{ $hint }}</span>
                            @if(in_array($type, $deductibleTypes, true))<em>From your balance</em>@endif
                        </label>
                    @endforeach
                </div>
                <input type="hidden" id="leaveTypeValue" value="{{ $pickedType }}">
                @error('leave_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div class="mt-3" id="otherWrap" @if($pickedType !== 'Other') hidden @endif>
                    <label class="af-label" for="otherInput">If other, specify <span class="af-req">*</span></label>
                    <input class="af-input" id="otherInput" name="other_leave_type" value="{{ old('other_leave_type') }}" style="max-width:360px" placeholder="e.g. Jury service">
                </div>
            </div>

            {{-- 3. Dates --}}
            <div class="af-sec">
                <div class="af-bar"><span class="af-num">{{ $public ? 3 : 2 }}</span> When <small>days are counted for you</small></div>
                @include('leave.partials.day-picker', [
                    'balance'    => $balance,
                    'typeSelect' => 'leaveTypeValue',
                ])
                @error('date_from')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                @error('date_to')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            {{-- 4. Reason + documents --}}
            <div class="af-sec">
                <div class="af-bar"><span class="af-num">{{ $public ? 4 : 3 }}</span> Reason &amp; supporting documents</div>
                <div class="mb-3">
                    <label class="af-label" for="afReason">Reason <span class="af-req" id="reasonStar" hidden>*</span> <span class="af-opt" id="reasonHint">(optional for this type)</span></label>
                    <textarea class="form-control" id="afReason" name="reason" rows="3" maxlength="5000" placeholder="A short note for your approvers">{{ old('reason') }}</textarea>
                    @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3" id="lateWrap" hidden>
                    <label class="af-label" for="afLate">Why is this application late? <span class="af-req" id="lateStar" hidden>*</span></label>
                    <div class="small text-muted mb-1" id="lateHint">Leave should be requested at least five working days before it starts.</div>
                    <textarea class="form-control" id="afLate" name="late_application_reason" rows="2" maxlength="5000">{{ old('late_application_reason') }}</textarea>
                    @error('late_application_reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <label class="af-drop" id="afDrop">
                    <input type="file" name="supporting_document" id="afFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>
                        <b class="d-block" id="afFileName">Add a supporting document</b>
                        <small class="text-muted" id="afFileHint">Optional (e.g. a medical certificate). PDF, photo or Word, up to 5 MB. Click or drop a file here.</small>
                    </span>
                </label>
                @error('supporting_document')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- 5. Approval + declaration --}}
            <div class="af-sec">
                <div class="af-bar"><span class="af-num">{{ $public ? 5 : 4 }}</span> Who approves it</div>
                <div class="af-route mb-3">
                    <div class="is-you"><small>You</small><b>Submit</b><small>{{ $employee?->first_name ?? 'Applicant' }}</small></div>
                    @foreach($approvalRoute as $step)
                        <div class="af-arrow" aria-hidden="true"><i class="bi bi-chevron-right"></i></div>
                        <div class="{{ $loop->last ? 'is-final' : '' }}">
                            <small>{{ $loop->iteration }}. {{ $step['title'] }}{{ $loop->last ? ' · final' : '' }}</small>
                            <b>{{ $step['name'] }}</b>
                            @if($step['note'])<small>{{ $step['note'] }}</small>@endif
                        </div>
                    @endforeach
                </div>
                <label class="af-declare">
                    <input class="form-check-input mt-1" type="checkbox" id="afConfirm" @checked(old('_confirm')) name="_confirm" value="1">
                    <span>I confirm the details above are correct. I understand the leave is only granted once it has been approved{{ count($approvalRoute) > 1 ? ' at every step' : '' }}, and that the balance is updated on the final approval.</span>
                </label>
            </div>

        </div>

        {{-- Live summary --}}
        <aside class="af-side">
            <div class="af-sticky af-sum" aria-live="polite">
                <h6>Your request</h6>
                <dl>
                    <dt>Type</dt><dd id="sumType">—</dd>
                    <dt>From</dt><dd id="sumFrom">—</dd>
                    <dt>To</dt><dd id="sumTo">—</dd>
                    <dt>Back at work</dt><dd id="sumBack">—</dd>
                </dl>
                <div class="af-big"><strong id="sumDays">0</strong><span>leave day(s)</span></div>
                @if($employee)
                    <dl>
                        <dt>Available now</dt><dd>{{ $n($balance) }} d</dd>
                        <dt>After this</dt><dd id="sumAfter">{{ $n($balance) }} d</dd>
                    </dl>
                    <div class="af-meter" title="Balance left after this request"><i id="sumMeter"></i></div>
                    @if($pendingDays > 0)
                        <div class="af-tip"><i class="bi bi-hourglass-split"></i> {{ $n($pendingDays) }} day(s) already in pending requests are set aside.</div>
                    @endif
                @endif
                <div class="af-tip"><i class="bi bi-envelope"></i> You'll get an email when it's submitted and at every approval.</div>
            </div>
        </aside>
        </div>

        <div class="af-submit">
            <button class="btn btn-primary px-4" id="afSubmit"><i class="bi bi-send me-1"></i> Submit leave request</button>
            @if(! empty($cancelUrl))<a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Cancel</a>@endif
            <small class="text-muted" id="afMissing"></small>
        </div>
        <div class="af-foot">
            <i class="bi bi-info-circle"></i> Unless in emergency situations, employees are required to submit leave applications not less than five working days prior to the proposed date of commencement of leave.
        </div>
    </div>
</form>
</div>

<script>
(function () {
    var form = document.getElementById('applyForm'), $ = function (id) { return document.getElementById(id); };
    var NEEDS_REASON = @json($reasonNeeded);
    var BAL = @json($balance);
    var DEDUCT = @json(array_values($deductibleTypes));
    var typeVal = $('leaveTypeValue');
    var fmt = function (v) { if (!v) return '—'; var p = v.split('-'); return new Date(Date.UTC(+p[0], p[1] - 1, +p[2])).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }); };

    function type() { var r = form.querySelector('input[name="leave_type"]:checked'); return r ? r.value : ''; }

    // Working days ahead of today (Mon–Sat), for the "five working days" rule.
    function workingDaysUntil(v) {
        if (!v) return null;
        var p = v.split('-'), d = new Date(+p[0], p[1] - 1, +p[2]), t = new Date(); t.setHours(0, 0, 0, 0);
        if (d < t) return -1;
        var n = 0; for (var x = new Date(t); x < d; x.setDate(x.getDate() + 1)) { if (x.getDay() !== 0) n++; }
        return n;
    }

    function refresh() {
        var t = type();
        if (typeVal.value !== t) { typeVal.value = t; typeVal.dispatchEvent(new Event('change')); }
        $('otherWrap').hidden = t !== 'Other';
        $('otherInput').required = t === 'Other';
        var need = NEEDS_REASON.indexOf(t) !== -1;
        $('afReason').required = need; $('reasonStar').hidden = !need;
        $('reasonHint').textContent = t ? (need ? '(required for ' + t + ' leave)' : '(optional for this type)') : '';

        var ahead = workingDaysUntil($('dateFrom').value);
        var late = ahead !== null && ahead < 5, past = ahead === -1;
        $('lateWrap').hidden = !late; $('afLate').required = past; $('lateStar').hidden = !past;
        $('lateHint').textContent = past ? 'Your leave starts before today, so please explain why it is being applied for now.' : 'Leave should be requested at least five working days before it starts. Add a short explanation (optional).';

        $('sumType').textContent = t ? (t === 'Other' && $('otherInput').value ? $('otherInput').value : t) : '—';
        $('sumFrom').textContent = fmt($('dateFrom').value);
        $('sumTo').textContent = fmt($('dateTo').value);
        var w = parseFloat($('dpWorking').textContent) || 0;
        $('sumDays').textContent = $('dateFrom').value && $('dateTo').value ? w : 0;
        var back = $('dpReturnBig').textContent; $('sumBack').textContent = back && back !== '–' ? back : '—';
        if (BAL !== null && $('sumAfter')) {
            var counts = !t || DEDUCT.indexOf(t) !== -1, left = counts ? BAL - w : BAL;
            $('sumAfter').textContent = (Math.round(left * 10) / 10) + ' d';
            $('sumAfter').className = left < 0 ? 'text-danger' : '';
            var m = $('sumMeter'); m.style.width = Math.max(0, Math.min(100, BAL > 0 ? left / BAL * 100 : 0)) + '%'; m.className = left < 0 || (BAL > 0 && left / BAL < .2) ? 'is-low' : '';
        }
        $('afMissing').textContent = '';
    }

    form.addEventListener('change', refresh);
    form.addEventListener('input', function (e) { if (e.target.id === 'dpDays' || e.target.id === 'otherInput') setTimeout(refresh, 0); });
    new MutationObserver(refresh).observe($('dpWorking'), { childList: true, characterData: true, subtree: true });

    // File picker: show the name, accept drops.
    var drop = $('afDrop'), file = $('afFile');
    file.addEventListener('change', function () {
        var f = file.files[0];
        $('afFileName').textContent = f ? f.name : 'Add a supporting document';
        $('afFileHint').textContent = f ? (Math.round(f.size / 102.4) / 10) + ' KB · click to change' : 'Optional (e.g. a medical certificate). PDF, photo or Word, up to 5 MB. Click or drop a file here.';
    });
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); }); });
    drop.addEventListener('drop', function (e) { if (e.dataTransfer.files.length) { file.files = e.dataTransfer.files; file.dispatchEvent(new Event('change')); } });

    // Friendly check before sending.
    form.addEventListener('submit', function (e) {
        refresh();
        var missing = [];
        if (!type()) missing.push('the type of leave');
        if (!$('dateFrom').value || !$('dateTo').value) missing.push('your dates');
        form.querySelectorAll('[required]').forEach(function (el) {
            if (el.type !== 'radio' && !el.value.trim() && ['dateFrom', 'dateTo'].indexOf(el.id) === -1) missing.push(((el.labels && el.labels[0]) ? el.labels[0].textContent : el.name).replace(/\*|\(.*\)/g, '').trim().toLowerCase());
        });
        if (!$('afConfirm').checked) missing.push('the confirmation tick');
        if (missing.length) {
            e.preventDefault();
            $('afMissing').innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> Still needed: ' + missing.join(', ') + '.</span>';
            return;
        }
        var b = $('afSubmit'); b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting…';
    });

    refresh();
})();
</script>
