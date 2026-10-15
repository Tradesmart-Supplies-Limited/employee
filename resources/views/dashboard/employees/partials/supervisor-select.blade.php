{{--
    Reports-to dropdown: admin / HR users + active employees.
    Vars: $supervisorEmployees, $supervisorUsers, $selected (e.g. "employee:4"), $legacy (old free-text name, optional)
--}}
@php $selected = old('supervisor_ref', $selected ?? null); @endphp

<label class="form-label" for="supervisorRef">Supervisor / Reports to</label>
<input type="search" class="form-control form-control-sm mb-1" id="supervisorFilter" placeholder="Type to filter names…" autocomplete="off">
<select class="form-select" name="supervisor_ref" id="supervisorRef">
    <option value="">— No supervisor (HR reviews directly) —</option>

    @if($supervisorUsers->isNotEmpty())
        <optgroup label="Admin / HR / Director users (no employee record)">
            @foreach($supervisorUsers as $u)
                <option value="user:{{ $u->id }}" @selected($selected === 'user:' . $u->id)>
                    {{ $u->name }} — {{ $u->roleLabel() }}
                </option>
            @endforeach
        </optgroup>
    @endif

    @if($supervisorEmployees->isNotEmpty())
        <optgroup label="Employees">
            @foreach($supervisorEmployees as $e)
                <option value="employee:{{ $e->id }}" @selected($selected === 'employee:' . $e->id)>
                    {{ $e->full_name }}{{ $e->position ? ' — ' . $e->position : '' }}{{ $e->user ? '' : ' (no portal login)' }}
                </option>
            @endforeach
        </optgroup>
    @endif
</select>
<small class="text-muted d-block mt-1">
    This person reviews the employee's leave first, then HR gives final approval.
    Employees need a portal login to review in the portal; HR can always act for them.
</small>
@if(! empty($legacy) && empty($selected))
    <small class="text-warning d-block"><i class="bi bi-exclamation-circle"></i> Previously recorded as "{{ $legacy }}". Please pick them from the list.</small>
@endif

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filter = document.getElementById('supervisorFilter');
    const select = document.getElementById('supervisorRef');
    if (!filter || !select) return;
    filter.addEventListener('input', () => {
        const q = filter.value.trim().toLowerCase();
        select.querySelectorAll('option').forEach(o => {
            if (!o.value) return;
            o.hidden = q !== '' && !o.textContent.toLowerCase().includes(q);
        });
        const first = [...select.options].find(o => o.value && !o.hidden);
        if (q && first) select.value = first.value;
    });
});
</script>
@endpush
@endonce
