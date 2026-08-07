@extends('layouts.app')

@section('content')

{{-- ===== TOP BAR ===== --}}
<div class="emp-top-bar">
    <h3 class="emp-title">Contract Reminders</h3>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3">

    {{-- ===== SETTINGS FORM ===== --}}
    <div class="col-lg-7">
        <div class="panel p-4">

            <h5 class="mb-3"><i class="bi bi-bell"></i> Reminder Settings</h5>

            <form method="POST" action="{{ route('settings.contract-reminders.update', $setting->id) }}">
                @csrf
                @method('PUT')

                {{-- ACTIVE TOGGLE --}}
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="is_active" name="is_active" value="1"
                           {{ $setting->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Reminders are <strong>{{ $setting->is_active ? 'active' : 'paused' }}</strong>
                    </label>
                </div>

                {{-- RECIPIENTS --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">Notify these email addresses</label>
                    <textarea name="recipients" rows="3" class="form-control"
                              placeholder="hr@company.com, manager@company.com">{{ old('recipients', $setting->recipients_text) }}</textarea>
                    <div class="form-text">Separate multiple addresses with commas or new lines.</div>
                </div>

                <hr class="my-4">

                {{-- DAYS BEFORE --}}
                <div class="mb-4">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="use_days_before" name="use_days_before" value="1"
                               {{ $setting->use_days_before ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="use_days_before">
                            Remind X day(s) before the contract ends
                        </label>
                    </div>
                    <input type="text" name="days_before" class="form-control"
                           placeholder="e.g. 14, 7"
                           value="{{ old('days_before', $setting->days_before_text) }}">
                    <div class="form-text">
                        One email per matching day. E.g. "14, 7" sends a reminder exactly 14 days
                        before a contract ends, and again 7 days before.
                    </div>
                </div>

                {{-- MONTHLY FIXED DAYS --}}
                <div class="mb-4">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="use_monthly_fixed" name="use_monthly_fixed" value="1"
                               {{ $setting->use_monthly_fixed ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="use_monthly_fixed">
                            Standard monthly digest on fixed day(s) of the month
                        </label>
                    </div>
                    <input type="text" name="monthly_fixed_days" class="form-control"
                           placeholder="e.g. 1, 14"
                           value="{{ old('monthly_fixed_days', $setting->monthly_fixed_days_text) }}">
                    <div class="form-text">
                        E.g. "1, 14" sends a digest every 1st and 14th of the month listing every
                        contract ending within the lookahead window below. Day values 1–28.
                    </div>
                </div>

                <div class="mb-4" style="max-width:260px;">
                    <label class="form-label fw-semibold">Monthly digest lookahead (days)</label>
                    <input type="number" name="monthly_lookahead_days" class="form-control"
                           min="1" max="365"
                           value="{{ old('monthly_lookahead_days', $setting->monthly_lookahead_days) }}">
                    <div class="form-text">How far ahead the monthly digest looks for expiring contracts.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Settings
                </button>

            </form>

            <hr class="my-4">

            <form method="POST" action="{{ route('settings.contract-reminders.test', $setting->id) }}"
                  onsubmit="return confirm('Send a test digest now to the configured recipients?');">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-send"></i> Send Test Reminder Now
                </button>
                <span class="text-muted small ms-2">Ignores the schedule — sends immediately using the up-to-date settings above.</span>
            </form>

        </div>
    </div>

    {{-- ===== UPCOMING CONTRACT ENDINGS PREVIEW ===== --}}
    <div class="col-lg-5">
        <div class="panel p-4">
            <h5 class="mb-3"><i class="bi bi-calendar-event"></i> Upcoming Contract Endings (90 days)</h5>

            @if($upcoming->isEmpty())
                <div class="text-muted small">No contracts are ending in the next 90 days.</div>
            @else
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Ends</th>
                            <th class="text-end">Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcoming as $employee)
                        @php
                            $daysLeft = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($employee->contract_end)->startOfDay(), false);
                        @endphp
                        <tr>
                            <td>
                                {{ $employee->first_name }} {{ $employee->last_name }}
                                <div class="text-muted small">{{ $employee->department ?? '—' }}</div>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($employee->contract_end)->format('M d, Y') }}</td>
                            <td class="text-end">
                                <span class="{{ $daysLeft <= 7 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    {{ $daysLeft }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>

@endsection
