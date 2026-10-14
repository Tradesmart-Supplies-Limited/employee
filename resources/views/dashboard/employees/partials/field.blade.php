{{--
    One employee form field from App\Support\EmployeeFields.
    Vars: $name, $def, $employee, $datalists (optional)
--}}
@php
    $type  = $def['type'] ?? 'text';
    $raw   = $employee->$name;
    $value = old($name, $raw instanceof \DateTimeInterface ? $raw->format('Y-m-d') : ($raw ?? ($employee->exists ? null : ($def['default'] ?? null))));
    $id    = 'f_' . $name;
    $req   = ! empty($def['required']);
    $invalid = $errors->has($name) ? 'is-invalid' : '';
    $portal  = $portal ?? false;                                  // rendering in the self-service portal
    $locked  = $portal && ($def['portal'] ?? 'readonly') === 'readonly';
    $checked = $portal && ($def['portal'] ?? null) === 'approval';
@endphp
<div class="col-md-{{ $def['col'] ?? 6 }} wz-field">
    <label class="form-label" for="{{ $id }}">{{ $def['label'] }}@if($req) <span class="text-danger">*</span>@endif
        @if($checked)<i class="bi bi-shield-lock" style="color:#7c3aed" title="Checked by HR before it applies"></i>@endif
        @if($locked)<i class="bi bi-lock text-muted" title="Only HR can change this"></i>@endif
        @if(! empty($pendingValues) && array_key_exists($name, $pendingValues))<span class="badge bg-warning text-dark ms-1" title="Waiting for HR: {{ $pendingValues[$name] }}">pending</span>@endif
    </label>

    @if($type === 'select')
        <select name="{{ $name }}" id="{{ $id }}" class="form-select {{ $invalid }}" @required($req) @disabled($locked) data-label="{{ $def['label'] }}">
            <option value="">—</option>
            @foreach($def['options'] as $k => $opt)
                @php $optValue = is_int($k) ? $opt : $k; @endphp
                <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $opt }}</option>
            @endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea name="{{ $name }}" id="{{ $id }}" rows="2" class="form-control {{ $invalid }}" @required($req) @disabled($locked) data-label="{{ $def['label'] }}" placeholder="{{ $def['placeholder'] ?? '' }}">{{ $value }}</textarea>
    @else
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}"
               class="form-control {{ $invalid }}" @required($req) @disabled($locked) data-label="{{ $def['label'] }}"
               placeholder="{{ $def['placeholder'] ?? '' }}"
               @if($type === 'number') step="0.01" min="0" @endif
               @if(! empty($def['datalist'])) list="dl_{{ $def['datalist'] }}" autocomplete="off" @endif
               @if($type === 'tel') inputmode="tel" @endif>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @else
        @if(! empty($def['hint']))<div class="form-text">{{ $def['hint'] }}</div>@endif
    @enderror
</div>
