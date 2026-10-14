@extends('layouts.portal')
@section('title', 'Update my details')

@section('content')

@php
    use App\Support\EmployeeFields as F;
    $sections = [
        'personal' => ['Personal', 'bi-person'],
        'contact'  => ['Contact & family', 'bi-telephone'],
        'pay'      => ['Bank & statutory', 'bi-bank'],
    ];
@endphp

<style>
.pe-nav { display: flex; gap: .4rem; flex-wrap: wrap; margin-bottom: 1rem; position: sticky; top: .5rem; z-index: 5; background: var(--admin-bg, #f4f6fb); padding: .3rem 0; }
.pe-nav a { font-size: .82rem; padding: .35rem .8rem; border-radius: 999px; border: 1px solid var(--admin-border, #e2e8f0); text-decoration: none; color: var(--admin-text, #0f172a); background: var(--admin-surface, #fff); }
.pe-card { background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: 1.1rem 1.25rem; margin-bottom: 1rem; scroll-margin-top: 4rem; }
.pe-card h2 { font-size: 1rem; font-weight: 800; margin: 0 0 1rem; display: flex; align-items: center; gap: .5rem; }
.pe-group { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--admin-muted, #64748b); margin: 1.1rem 0 .2rem; padding-top: .8rem; border-top: 1px dashed var(--admin-border, #e2e8f0); }
.pe-photo { display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; }
.pe-photo .pv { width: 80px; height: 80px; border-radius: 50%; overflow: hidden; background: var(--admin-surface-soft, #f1f5f9); display: flex; align-items: center; justify-content: center; font-size: 1.7rem; color: #94a3b8; flex-shrink: 0; }
.pe-photo .pv img { width: 100%; height: 100%; object-fit: cover; }
.pe-save { position: sticky; bottom: 0; background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e2e8f0); border-radius: 14px; padding: .9rem 1.2rem; display: flex; gap: .8rem; align-items: center; flex-wrap: wrap; box-shadow: 0 -6px 20px rgba(15, 23, 42, .06); z-index: 6; }
.pe-legend { font-size: .78rem; color: var(--admin-muted, #64748b); }
</style>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-2">
    <div>
        <a href="{{ route('portal.profile.show') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> My information</a>
        <h4 class="mb-0 mt-1 fw-bold"><i class="bi bi-pencil-square"></i> Update my details</h4>
    </div>
    <div class="pe-legend">
        <i class="bi bi-shield-lock" style="color:#7c3aed"></i> checked by HR{{ $needsApproval ? ' before it applies' : '' }}
        &nbsp; <i class="bi bi-lock"></i> only HR can change
    </div>
</div>

<div class="pe-nav">
    @foreach($sections as $k => [$t, $i])<a href="#{{ $k }}"><i class="bi {{ $i }}"></i> {{ $t }}</a>@endforeach
    <a href="{{ route('portal.documents.index') }}"><i class="bi bi-folder2-open"></i> Documents</a>
</div>

<datalist id="dl_bank_name">@foreach(['Zanaco', 'Stanbic Bank', 'Absa Bank', 'FNB Zambia', 'Standard Chartered', 'Indo Zambia Bank', 'Access Bank', 'Atlas Mara', 'First Capital Bank', 'Ecobank', 'United Bank for Africa', 'ZICB', 'AB Bank', 'Natsave', 'Investrust', 'Citibank'] as $b)<option value="{{ $b }}">@endforeach</datalist>

<form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" id="profileForm">
    @csrf
    @method('PUT')

    @foreach($sections as $step => [$title, $icon])
        <section class="pe-card" id="{{ $step }}">
            <h2><i class="bi {{ $icon }}"></i> {{ $title }}</h2>

            @if($step === 'personal')
                <div class="pe-photo">
                    <div class="pv" id="pv">@if($employee->passport_photo)<img src="{{ asset('storage/' . $employee->passport_photo) }}" alt="">@else<i class="bi bi-person"></i>@endif</div>
                    <div>
                        <label class="form-label mb-1" for="photo">Profile photo</label>
                        <input type="file" name="passport_photo" id="photo" accept="image/*" class="form-control form-control-sm @error('passport_photo') is-invalid @enderror">
                        @error('passport_photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-text">A clear passport-style photo, JPG or PNG up to 4 MB.</div>
                    </div>
                </div>
            @endif

            @php $last = null; @endphp
            <div class="row g-3">
                @foreach(F::forStep($step) as $name => $def)
                    @continue($def['portal'] === 'hidden' || ($step === 'pay' && $name === 'salary'))
                    @if(($def['group'] ?? null) && ($def['group'] ?? null) !== $last)
                        </div><div class="pe-group">{{ $def['group'] }}</div><div class="row g-3">
                    @endif
                    @php $last = $def['group'] ?? null; @endphp
                    @include('dashboard.employees.partials.field', ['name' => $name, 'def' => $def, 'portal' => true, 'pendingValues' => $pendingFields])
                @endforeach
            </div>

            @if($step === 'pay')
                <div class="alert alert-light border small mt-3 mb-0"><i class="bi bi-info-circle"></i> Changing your bank account? HR may ask for a bank letter or statement. Upload it under <a href="{{ route('portal.documents.index') }}">My documents</a> as "Bank details".</div>
            @endif
        </section>
    @endforeach

    <div class="pe-card d-none" id="noteBox" style="border-color:#c4b5fd">
        <label class="form-label fw-semibold" for="change_note"><i class="bi bi-shield-lock" style="color:#7c3aed"></i> Note for HR <span class="text-muted fw-normal small">(optional)</span></label>
        <textarea name="change_note" id="change_note" rows="2" class="form-control" maxlength="1000" placeholder="e.g. I changed banks in September. Bank letter uploaded.">{{ old('change_note') }}</textarea>
        <div class="form-text">You've changed something HR needs to check. Saying why helps them approve it faster.</div>
    </div>

    <div class="pe-save">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Save my details</button>
        <a href="{{ route('portal.profile.show') }}" class="btn btn-light">Cancel</a>
        <span class="small text-muted ms-auto" id="changeCount"></span>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('profileForm');
    const initial = {};
    const inputs = [...form.querySelectorAll('input[name], select[name], textarea[name]')].filter(el => el.type !== 'file' && el.name !== '_token' && el.name !== '_method' && el.name !== 'change_note');
    inputs.forEach(el => initial[el.name] = el.value);
    function check() {
        const changed = inputs.filter(el => el.value !== initial[el.name]);
        const sensitive = changed.some(el => el.closest('.wz-field')?.querySelector('.bi-shield-lock'));
        document.getElementById('noteBox').classList.toggle('d-none', !sensitive);
        document.getElementById('changeCount').textContent = changed.length ? `${changed.length} change(s)${sensitive && {{ $needsApproval ? 'true' : 'false' }} ? ', some will go to HR for approval' : ''}` : '';
    }
    form.addEventListener('input', check); form.addEventListener('change', check);
    document.getElementById('photo').addEventListener('change', e => {
        const f = e.target.files[0]; if (!f) return;
        const r = new FileReader(); r.onload = () => document.getElementById('pv').innerHTML = `<img src="${r.result}" alt="">`; r.readAsDataURL(f);
    });
    window.addEventListener('beforeunload', e => { if (inputs.some(el => el.value !== initial[el.name]) && !form.dataset.submitting) { e.preventDefault(); e.returnValue = ''; } });
    form.addEventListener('submit', () => form.dataset.submitting = '1');
    check();
})();
</script>
@endpush

@endsection
