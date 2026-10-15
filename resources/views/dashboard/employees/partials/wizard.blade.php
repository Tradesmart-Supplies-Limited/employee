{{--
    Step-by-step employee form, used for both Add (guided, with review) and Edit (jump to any step).
    Vars: $employee, $mode ('create'|'edit'), $supervisorEmployees, $supervisorUsers, $datalists
--}}
@php
    use App\Support\EmployeeFields as F;
    $steps = F::STEPS;
    $isCreate = $mode === 'create';
    if ($isCreate) { $steps['review'] = ['Review & save', 'bi-check2-circle']; }
    $fields = F::all();
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
@endphp

<style>
.wz { --wz-accent: var(--brand-text, #185FA5); }
.wz-shell { display: grid; grid-template-columns: 240px 1fr; gap: 1.25rem; align-items: start; }
.wz-nav { position: sticky; top: 1rem; background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: .6rem; }
.wz-nav-item { display: flex; align-items: center; gap: .7rem; width: 100%; border: 0; background: transparent; text-align: left; padding: .65rem .7rem; border-radius: 10px; color: var(--admin-text, #0f172a); font-size: .88rem; cursor: pointer; }
.wz-nav-item:hover { background: var(--admin-surface-soft, #f1f5f9); }
.wz-nav-item .num { width: 30px; height: 30px; border-radius: 50%; border: 2px solid var(--admin-border, #cbd5e1); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; color: var(--admin-muted, #64748b); flex-shrink: 0; background: var(--admin-surface, #fff); }
.wz-nav-item .t { line-height: 1.2; } .wz-nav-item .t small { display: block; font-size: .72rem; color: var(--admin-muted, #64748b); }
.wz-nav-item.active { background: rgba(var(--brand-rgb, 24, 95, 165), .08); font-weight: 700; }
.wz-nav-item.active .num { border-color: var(--wz-accent); color: var(--wz-accent); }
.wz-nav-item.done .num { background: #16a34a; border-color: #16a34a; color: #fff; }
.wz-nav-item.has-error .num { background: #dc2626; border-color: #dc2626; color: #fff; }
.wz-nav-item:disabled { cursor: not-allowed; opacity: .55; }
.wz-progress { height: 4px; background: var(--admin-border, #e2e8f0); border-radius: 4px; margin: .4rem .7rem .8rem; overflow: hidden; }
.wz-progress span { display: block; height: 100%; background: var(--wz-accent); transition: width .25s; }

.wz-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; }
.wz-step { display: none; padding: 1.4rem 1.5rem 1rem; }
.wz-step.active { display: block; animation: wzIn .2s ease; }
@keyframes wzIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
.wz-step-head { display: flex; align-items: center; gap: .8rem; margin-bottom: 1.1rem; }
.wz-step-head i { font-size: 1.4rem; color: var(--wz-accent); }
.wz-step-head h2 { font-size: 1.15rem; font-weight: 800; margin: 0; }
.wz-step-head p { margin: 0; font-size: .82rem; color: var(--admin-muted, #64748b); }
.wz-group { font-size: .74rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--admin-muted, #64748b); margin: 1.2rem 0 .3rem; padding-top: .9rem; border-top: 1px dashed var(--admin-border, #e2e8f0); }
.wz-foot { display: flex; justify-content: space-between; align-items: center; gap: .6rem; padding: .9rem 1.5rem; border-top: 1px solid var(--admin-border, #e2e8f0); background: var(--admin-surface-soft, #f8fafc); border-radius: 0 0 14px 14px; position: sticky; bottom: 0; z-index: 5; }
.wz-photo { display: flex; align-items: center; gap: 1rem; padding: .8rem; border: 1px dashed var(--admin-border, #cbd5e1); border-radius: 12px; margin-bottom: 1rem; }
.wz-photo .pv { width: 76px; height: 76px; border-radius: 50%; background: var(--admin-surface-soft, #f1f5f9); display: flex; align-items: center; justify-content: center; overflow: hidden; font-size: 1.6rem; color: var(--admin-muted, #94a3b8); flex-shrink: 0; }
.wz-photo .pv img { width: 100%; height: 100%; object-fit: cover; }
.wz-review-sec { border: 1px solid var(--admin-border, #e2e8f0); border-radius: 12px; margin-bottom: .8rem; }
.wz-review-sec header { display: flex; justify-content: space-between; align-items: center; padding: .55rem .9rem; background: var(--admin-surface-soft, #f8fafc); border-radius: 12px 12px 0 0; font-weight: 700; font-size: .86rem; }
.wz-review-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .5rem 1rem; padding: .7rem .9rem; font-size: .84rem; }
.wz-review-grid small { display: block; color: var(--admin-muted, #64748b); font-size: .7rem; }
.wz-review-grid .empty { color: var(--admin-muted, #94a3b8); }
.wz-files .row-file { display: flex; gap: .5rem; align-items: center; margin-bottom: .4rem; font-size: .84rem; }
.wz-draft { font-size: .76rem; color: var(--admin-muted, #64748b); }
@media (max-width: 992px) {
    .wz-shell { grid-template-columns: 1fr; }
    .wz-nav { position: static; display: flex; overflow-x: auto; gap: .3rem; padding: .4rem; }
    .wz-nav-item { flex: 0 0 auto; width: auto; } .wz-nav-item .t small, .wz-progress { display: none; }
}
</style>

@foreach($datalists ?? [] as $key => $values)
    <datalist id="dl_{{ $key }}">@foreach($values as $v)<option value="{{ $v }}">@endforeach</datalist>
@endforeach

<form method="POST" action="{{ $isCreate ? route('employees.store') : route('employees.update', $employee) }}"
      enctype="multipart/form-data" class="wz" id="wizard" data-mode="{{ $mode }}" novalidate>
    @csrf
    @unless($isCreate) @method('PUT') @endunless

    <div class="wz-shell">

        {{-- ===== STEP NAV ===== --}}
        <nav class="wz-nav" aria-label="Form steps">
            <div class="wz-progress d-none d-lg-block"><span id="wzBar" style="width: 0%"></span></div>
            @foreach($steps as $key => [$title, $icon])
                <button type="button" class="wz-nav-item" data-goto="{{ $loop->index }}" @disabled($isCreate && ! $loop->first)>
                    <span class="num">{{ $loop->iteration }}</span>
                    <span class="t">{{ $title }}<small>
                        {{ ['personal' => 'Name, ID, photo', 'contact' => 'Phone, address, next of kin', 'job' => 'Role, supervisor, dates', 'pay' => 'Salary, bank, NAPSA, TPIN', 'extras' => 'Leave, files, portal', 'review' => 'Check everything'][$key] }}
                    </small></span>
                </button>
            @endforeach
        </nav>

        <div class="wz-card">

            {{-- ===== 1. PERSONAL ===== --}}
            <section class="wz-step" data-step="personal">
                <div class="wz-step-head"><i class="bi bi-person"></i><div><h2>Personal details</h2><p>Who is this person? Only first and last name are required to get started.</p></div></div>

                <div class="wz-photo">
                    <div class="pv" id="photoPreview">
                        @if($employee->passport_photo)<img src="{{ asset('storage/' . $employee->passport_photo) }}" alt="">@else<i class="bi bi-person"></i>@endif
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label mb-1" for="f_photo">Photo <span class="text-muted small">(optional)</span></label>
                        <input type="file" name="passport_photo" id="f_photo" accept="image/*" class="form-control form-control-sm @error('passport_photo') is-invalid @enderror">
                        @error('passport_photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @if(! $isCreate && $employee->passport_photo)
                            <div class="form-check small mt-1"><input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="rmPhoto"><label class="form-check-label" for="rmPhoto">Remove current photo</label></div>
                        @endif
                    </div>
                </div>

                <div class="row g-3">
                    @foreach(F::forStep('personal') as $name => $def)
                        @include('dashboard.employees.partials.field', ['name' => $name, 'def' => $def])
                    @endforeach
                </div>
            </section>

            {{-- ===== 2. CONTACT ===== --}}
            <section class="wz-step" data-step="contact">
                <div class="wz-step-head"><i class="bi bi-telephone"></i><div><h2>Contact &amp; family</h2><p>How to reach them, and who to call in an emergency.</p></div></div>
                @php $lastGroup = null; @endphp
                <div class="row g-3">
                    @foreach(F::forStep('contact') as $name => $def)
                        @if(($def['group'] ?? null) !== $lastGroup && ($def['group'] ?? null))
                            </div><div class="wz-group">{{ $def['group'] }}</div><div class="row g-3">
                        @endif
                        @php $lastGroup = $def['group'] ?? null; @endphp
                        @include('dashboard.employees.partials.field', ['name' => $name, 'def' => $def])
                    @endforeach
                </div>
            </section>

            {{-- ===== 3. JOB ===== --}}
            <section class="wz-step" data-step="job">
                <div class="wz-step-head"><i class="bi bi-briefcase"></i><div><h2>Job &amp; contract</h2><p>Role, who they report to, and the dates that drive contract and probation reminders.</p></div></div>
                @php $lastGroup = null; @endphp
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="f_ein">Employee number (EIN)</label>
                        <input type="text" name="employee_id" id="f_ein" class="form-control @error('employee_id') is-invalid @enderror" value="{{ old('employee_id', $employee->employee_id) }}" placeholder="{{ $isCreate ? 'Leave blank to generate one' : '' }}" data-label="Employee number">
                        @error('employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        @include('dashboard.employees.partials.supervisor-select', ['selected' => $employee->supervisor_ref, 'legacy' => $employee->supervisor])
                    </div>
                    @foreach(F::forStep('job') as $name => $def)
                        @if(($def['group'] ?? null) !== $lastGroup && ($def['group'] ?? null))
                            </div><div class="wz-group">{{ $def['group'] }} <span class="text-lowercase fw-normal">· used for contract &amp; probation reminders</span></div><div class="row g-3">
                        @endif
                        @php $lastGroup = $def['group'] ?? null; @endphp
                        @include('dashboard.employees.partials.field', ['name' => $name, 'def' => $def])
                    @endforeach
                </div>
                <div class="small mt-2" id="datesHint" style="color:#b45309"></div>
            </section>

            {{-- ===== 4. PAY ===== --}}
            <section class="wz-step" data-step="pay">
                <div class="wz-step-head"><i class="bi bi-bank"></i><div><h2>Pay &amp; statutory</h2><p>Needed for payroll, NAPSA, ZRA PAYE, NHIMA and bank payment files. You can fill these in later.</p></div></div>
                @php $lastGroup = null; @endphp
                <div class="row g-3">
                    @foreach(F::forStep('pay') as $name => $def)
                        @if(($def['group'] ?? null) !== $lastGroup && ($def['group'] ?? null))
                            </div><div class="wz-group">{{ $def['group'] }}</div><div class="row g-3">
                        @endif
                        @php $lastGroup = $def['group'] ?? null; @endphp
                        @include('dashboard.employees.partials.field', ['name' => $name, 'def' => $def])
                    @endforeach
                </div>
            </section>

            {{-- ===== 5. EXTRAS ===== --}}
            <section class="wz-step" data-step="extras">
                <div class="wz-step-head"><i class="bi bi-folder2-open"></i><div><h2>Leave, documents &amp; portal</h2><p>Opening leave balance, any files to keep on record, and self-service access.</p></div></div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="f_ent">Annual leave entitlement (days / year)</label>
                        <input type="number" step="0.5" min="0" name="annual_leave_entitlement" id="f_ent" class="form-control" value="{{ old('annual_leave_entitlement', $n($employee->annual_leave_entitlement ?? config('leave.annual_entitlement'))) }}" data-label="Leave entitlement">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="f_bal">{{ $isCreate ? 'Opening leave balance' : 'Current leave balance' }} (days)</label>
                        <input type="number" step="0.5" name="leave_balance" id="f_bal" class="form-control" value="{{ old('leave_balance', $n($employee->leave_balance ?? config('leave.annual_entitlement'))) }}" data-label="Leave balance">
                        <div class="form-text">{{ $isCreate ? 'Days available today, e.g. pro-rated for a mid-year start.' : 'Changing it is recorded in the balance history.' }}</div>
                    </div>
                </div>

                <div class="wz-group">Documents</div>
                <div class="wz-files" id="docRows">
                    <div class="row-file">
                        <input type="file" name="documents[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                        <select name="document_categories[]" class="form-select form-select-sm" style="max-width:170px">
                            @foreach(F::DOC_CATEGORIES as $c)<option>{{ $c }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-link px-0" id="addDoc"><i class="bi bi-plus-circle"></i> Add another file</button>
                <div class="form-text">PDF, images, Word or Excel, up to 10 MB each. e.g. signed contract, NRC copy, CV.</div>
                @if(! $isCreate && $employee->documents->isNotEmpty())
                    <div class="small text-muted mt-2"><i class="bi bi-paperclip"></i> {{ $employee->documents->count() }} document(s) already on file. Manage them on the profile's Documents tab.</div>
                @endif

                @if($isCreate)
                    <div class="wz-group">Self-service portal</div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="create_portal_account" value="1" id="mkPortal" @checked(old('create_portal_account', true))>
                        <label class="form-check-label" for="mkPortal">Create a portal login and email the details to the employee</label>
                    </div>
                    <div class="form-text">Needs a company or personal email (Contact step). HR is notified too.</div>
                @endif
            </section>

            {{-- ===== 6. REVIEW (create only) ===== --}}
            @if($isCreate)
                <section class="wz-step" data-step="review">
                    <div class="wz-step-head"><i class="bi bi-check2-circle"></i><div><h2>Review &amp; save</h2><p>Check the details below. Click <em>Edit</em> on any section to change it.</p></div></div>
                    <div id="reviewBody"></div>
                </section>
            @endif

            <div class="wz-foot">
                <button type="button" class="btn btn-outline-secondary" id="wzPrev"><i class="bi bi-arrow-left"></i> Back</button>
                <span class="wz-draft" id="wzDraft"></span>
                <div class="d-flex gap-2">
                    @unless($isCreate)
                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-success" id="wzSaveAny"><i class="bi bi-check2"></i> Save changes</button>
                    @endunless
                    <button type="button" class="btn btn-primary" id="wzNext">Next <i class="bi bi-arrow-right"></i></button>
                    @if($isCreate)
                        <button type="submit" class="btn btn-success d-none" id="wzSave"><i class="bi bi-person-check"></i> Save employee</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('wizard');
    const isCreate = form.dataset.mode === 'create';
    const steps = [...form.querySelectorAll('.wz-step')];
    const navs = [...form.querySelectorAll('.wz-nav-item')];
    const prev = document.getElementById('wzPrev'), next = document.getElementById('wzNext'), save = document.getElementById('wzSave');
    const DRAFT_KEY = 'tsl-employee-draft';
    let current = 0, furthest = 0;

    function fieldsOf(step) { return [...step.querySelectorAll('input, select, textarea')].filter(el => el.name && el.type !== 'file' && el.type !== 'hidden' && !el.name.startsWith('dp_')); }

    function validStep(i) {
        const bad = fieldsOf(steps[i]).find(el => !el.checkValidity());
        if (bad) { bad.reportValidity(); bad.focus(); return false; }
        return true;
    }

    function show(i) {
        current = Math.max(0, Math.min(i, steps.length - 1));
        furthest = Math.max(furthest, current);
        steps.forEach((s, k) => s.classList.toggle('active', k === current));
        navs.forEach((n, k) => {
            n.classList.toggle('active', k === current);
            n.classList.toggle('done', isCreate && k < furthest && k !== current);
            if (isCreate) n.disabled = k > furthest;
        });
        prev.style.visibility = current === 0 ? 'hidden' : 'visible';
        const last = current === steps.length - 1;
        next.classList.toggle('d-none', last);
        if (save) save.classList.toggle('d-none', !last);
        document.getElementById('wzBar').style.width = ((current) / (steps.length - 1) * 100) + '%';
        if (steps[current].dataset.step === 'review') buildReview();
        const first = fieldsOf(steps[current])[0];
        window.scrollTo({ top: form.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
    }

    next.addEventListener('click', () => { if (!isCreate || validStep(current)) show(current + 1); });
    prev.addEventListener('click', () => show(current - 1));
    navs.forEach((n, k) => n.addEventListener('click', () => {
        if (isCreate && k > current) { for (let j = current; j < k; j++) if (!validStep(j)) { show(j); return; } }
        show(k);
    }));
    // Enter key moves forward instead of submitting half a form
    form.addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.tagName === 'INPUT' && isCreate && current < steps.length - 1) { e.preventDefault(); next.click(); }
    });

    // ----- Review -----
    function buildReview() {
        const body = document.getElementById('reviewBody');
        body.innerHTML = '';
        steps.slice(0, -1).forEach((s, i) => {
            const title = navs[i].querySelector('.t').firstChild.textContent.trim();
            const items = fieldsOf(s).filter(el => el.dataset.label || el.name === 'supervisor_ref').map(el => {
                let v = el.tagName === 'SELECT' ? (el.value ? el.selectedOptions[0].textContent.trim() : '') : el.value.trim();
                if (el.type === 'date' && v) v = new Date(v + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                return [el.dataset.label || 'Reports to', v];
            });
            const files = [...s.querySelectorAll('input[type=file]')].flatMap(f => [...f.files].map(x => x.name));
            const sec = document.createElement('div');
            sec.className = 'wz-review-sec';
            sec.innerHTML = `<header><span>${i + 1}. ${title}</span><button type="button" class="btn btn-sm btn-link p-0" data-edit="${i}"><i class="bi bi-pencil"></i> Edit</button></header>
                <div class="wz-review-grid">${items.map(([l, v]) => `<div><small>${l}</small>${v ? escapeHtml(v) : '<span class="empty">—</span>'}</div>`).join('')}
                ${files.length ? `<div><small>Files</small>${files.map(escapeHtml).join('<br>')}</div>` : ''}</div>`;
            body.appendChild(sec);
        });
        body.querySelectorAll('[data-edit]').forEach(b => b.addEventListener('click', () => show(+b.dataset.edit)));
    }
    function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

    // ----- Photo preview -----
    document.getElementById('f_photo').addEventListener('change', e => {
        const f = e.target.files[0]; if (!f) return;
        const r = new FileReader(); r.onload = () => document.getElementById('photoPreview').innerHTML = `<img src="${r.result}" alt="">`; r.readAsDataURL(f);
    });

    // ----- More document rows -----
    document.getElementById('addDoc').addEventListener('click', () => {
        const row = document.querySelector('#docRows .row-file').cloneNode(true);
        row.querySelector('input').value = '';
        document.getElementById('docRows').appendChild(row);
    });

    // ----- Friendly date hints -----
    function dateHints() {
        const v = id => { const el = document.getElementById(id); return el && el.value ? new Date(el.value + 'T00:00:00') : null; };
        const nature = document.getElementById('f_employment_nature'), end = v('f_contract_end'), pe = v('f_probation_end');
        const msgs = [];
        if (nature && nature.value === 'CONTRACT' && !end) msgs.push('Fixed-term contract: add a contract end date so HR is reminded before it expires.');
        const days = d => Math.round((d - new Date(new Date().toDateString())) / 86400000);
        if (end) { const d = days(end); msgs.push(d < 0 ? `⚠ Contract end date is ${-d} day(s) in the past.` : `Contract ends in ${d} day(s).`); }
        if (pe) { const d = days(pe); msgs.push(d < 0 ? `Probation ended ${-d} day(s) ago.` : `Probation ends in ${d} day(s).`); }
        document.getElementById('datesHint').textContent = msgs.join(' ');
    }
    ['f_employment_nature', 'f_contract_end', 'f_probation_end'].forEach(id => { const el = document.getElementById(id); if (el) el.addEventListener('change', dateHints); });
    dateHints();

    // ----- Draft autosave (Add only; files can't be saved) -----
    if (isCreate) {
        const hasServerErrors = !!form.querySelector('.is-invalid');
        const store = { get() { try { return JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) { return null; } },
                        set(v) { try { localStorage.setItem(DRAFT_KEY, JSON.stringify(v)); } catch (e) {} },
                        clear() { try { localStorage.removeItem(DRAFT_KEY); } catch (e) {} } };
        const draft = store.get();
        if (draft && !hasServerErrors && Object.values(draft.data || {}).some(Boolean)) {
            const bar = document.getElementById('wzDraft');
            bar.innerHTML = `Unsaved draft from ${new Date(draft.at).toLocaleString()} · <a href="#" id="dRestore">restore</a> · <a href="#" id="dDiscard">discard</a>`;
            document.getElementById('dRestore').onclick = e => { e.preventDefault(); Object.entries(draft.data).forEach(([k, v]) => { const el = form.elements[k]; if (el && !el.length && el.type !== 'file' && el.type !== 'checkbox') el.value = v; }); bar.textContent = 'Draft restored.'; dateHints(); };
            document.getElementById('dDiscard').onclick = e => { e.preventDefault(); store.clear(); bar.textContent = ''; };
        }
        let t;
        form.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => {
            const data = {}; [...form.elements].forEach(el => { if (el.name && el.type !== 'file' && el.type !== 'hidden' && el.type !== 'checkbox' && !el.name.endsWith('[]')) data[el.name] = el.value; });
            store.set({ at: Date.now(), data }); document.getElementById('wzDraft').textContent = 'Draft saved on this computer';
        }, 600); });
        form.addEventListener('submit', () => store.clear());
    }

    // ----- Open the first step with a server-side error -----
    const errStep = steps.findIndex(s => s.querySelector('.is-invalid'));
    steps.forEach((s, i) => { if (s.querySelector('.is-invalid')) navs[i].classList.add('has-error'); });
    if (errStep >= 0) { furthest = steps.length - 1; navs.forEach(n => n.disabled = false); }
    const hashStep = isCreate ? -1 : steps.findIndex(s => '#' + s.dataset.step === location.hash);
    show(errStep >= 0 ? errStep : (hashStep >= 0 ? hashStep : 0));

    // Final client check before submit (create): every step valid
    form.addEventListener('submit', e => {
        for (let i = 0; i < steps.length; i++) { if (!validStep(i)) { e.preventDefault(); show(i); setTimeout(() => validStep(i), 50); return; } }
    });
})();
</script>
@endpush
