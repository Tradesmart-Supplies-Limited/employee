@extends('layouts.app')

@section('content')

{{-- HEADER CARD --}}
<div class="panel mb-3">
    <div class="d-flex align-items-center gap-3">
        <div class="flex-grow-1">
            <h4 class="mb-0">Employee Information</h4>
            <small class="text-muted">
                Edit, Print, or view details of {{ $employee->first_name }} {{ $employee->last_name }}
            </small>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('employees.edit', $employee) }}">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('employees.index') }}">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

{{-- RESUME CARD --}}
<div class="resume-card">

    {{-- ===== LEFT SIDEBAR ===== --}}
    <div class="resume-left">

        {{-- Photo --}}
        <div class="resume-photo-wrap">
            <div class="resume-photo-circle">
                @if($employee->passport_photo)
                    <img src="{{ asset('storage/' . $employee->passport_photo) }}"
                         alt="{{ $employee->first_name }} {{ $employee->last_name }}">
                @else
                    <span class="resume-photo-initials">
                        {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Badges --}}
        <div class="resume-badges">
            <span class="resume-badge resume-badge-id">{{ $employee->employee_id }}</span>
            <span class="resume-badge resume-badge-{{ $employee->employment_status == 'Active' ? 'active' : 'inactive' }}">
                {{ $employee->employment_status }}
            </span>
        </div>

        {{-- Contacts --}}
        <div class="resume-section-label">Contacts</div>

        @if($employee->personal_email)
        <div class="resume-info-row">
            <i class="bi bi-envelope"></i>
            <span>{{ $employee->personal_email }}</span>
        </div>
        @endif

        @if($employee->company_email)
        <div class="resume-info-row">
            <i class="bi bi-building"></i>
            <span>{{ $employee->company_email }}</span>
        </div>
        @endif

        @if($employee->primary_phone)
        <div class="resume-info-row">
            <i class="bi bi-telephone"></i>
            <span>{{ $employee->primary_phone }}</span>
        </div>
        @endif

        @if($employee->secondary_phone)
        <div class="resume-info-row">
            <i class="bi bi-telephone"></i>
            <span>{{ $employee->secondary_phone }}</span>
        </div>
        @endif

        {{-- Emergency --}}
        @if($employee->emergency_name || $employee->emergency_phone)
        <div class="resume-section-label">Emergency</div>

        @if($employee->emergency_name)
        <div class="resume-info-row">
            <i class="bi bi-person"></i>
            <span>{{ $employee->emergency_name }}</span>
        </div>
        @endif

        @if($employee->emergency_relationship)
        <div class="resume-info-row">
            <i class="bi bi-heart"></i>
            <span>{{ $employee->emergency_relationship }}</span>
        </div>
        @endif

        @if($employee->emergency_phone)
        <div class="resume-info-row">
            <i class="bi bi-telephone"></i>
            <span>{{ $employee->emergency_phone }}</span>
        </div>
        @endif
        @endif

        {{-- Next of Kin --}}
        @if($employee->next_of_kin_name || $employee->next_of_kin_phone)
        <div class="resume-section-label">Next of Kin</div>

        @if($employee->next_of_kin_name)
        <div class="resume-info-row">
            <i class="bi bi-person"></i>
            <span>{{ $employee->next_of_kin_name }}</span>
        </div>
        @endif

        @if($employee->next_of_kin_phone)
        <div class="resume-info-row">
            <i class="bi bi-telephone"></i>
            <span>{{ $employee->next_of_kin_phone }}</span>
        </div>
        @endif

        @if($employee->next_of_kin_address)
        <div class="resume-info-row">
            <i class="bi bi-geo-alt"></i>
            <span>{{ $employee->next_of_kin_address }}</span>
        </div>
        @endif
        @endif

        {{-- Finance --}}
        <div class="resume-section-label">Finance</div>

        @if($employee->bank_name)
        <div class="resume-info-row">
            <i class="bi bi-bank"></i>
            <span>{{ $employee->bank_name }}</span>
        </div>
        @endif

        @if($employee->bank_account_number)
        <div class="resume-info-row">
            <i class="bi bi-credit-card"></i>
            <span>{{ $employee->bank_account_number }}</span>
        </div>
        @endif

        @if($employee->nssf_number)
        <div class="resume-info-row">
            <i class="bi bi-shield-check"></i>
            <span>NSSF: {{ $employee->nssf_number }}</span>
        </div>
        @endif

        @if($employee->tin_number)
        <div class="resume-info-row">
            <i class="bi bi-receipt"></i>
            <span>TIN: {{ $employee->tin_number }}</span>
        </div>
        @endif

        @if($employee->salary)
        <div class="resume-salary">
            K {{ number_format($employee->salary, 2) }} <span>/ month</span>
        </div>
        @endif

    </div>{{-- end resume-left --}}

    {{-- ===== RIGHT MAIN ===== --}}
    <div class="resume-right">

        {{-- Name & Role --}}
        <div class="resume-name">
            {{ strtoupper($employee->first_name) }}
            @if($employee->middle_name)
                {{ strtoupper($employee->middle_name) }}
            @endif
            <span>{{ strtoupper($employee->last_name) }}</span>
        </div>

        <div class="resume-role">
            {{ $employee->position }}
            @if($employee->department)
                &nbsp;•&nbsp; {{ $employee->department }}
            @endif
            @if($employee->branch)
                &nbsp;•&nbsp; {{ $employee->branch }}
            @endif
        </div>

        <hr class="resume-divider">

        {{-- Personal --}}
        <div class="resume-section-label-right">Personal</div>

        <div class="resume-grid-2">

            @if($employee->date_of_birth)
            <div class="resume-entry">
                <div class="resume-entry-label">Date of birth</div>
                <div class="resume-entry-value">{{ $employee->date_of_birth->format('Y-m-d') }}</div>
            </div>
            @endif

            @if($employee->age)
            <div class="resume-entry">
                <div class="resume-entry-label">Age</div>
                <div class="resume-entry-value">{{ $employee->age }}</div>
            </div>
            @endif

            @if($employee->gender)
            <div class="resume-entry">
                <div class="resume-entry-label">Gender</div>
                <div class="resume-entry-value">{{ $employee->gender }}</div>
            </div>
            @endif

            @if($employee->nationality)
            <div class="resume-entry">
                <div class="resume-entry-label">Nationality</div>
                <div class="resume-entry-value">{{ $employee->nationality }}</div>
            </div>
            @endif

            @if($employee->national_id_number)
            <div class="resume-entry">
                <div class="resume-entry-label">National ID</div>
                <div class="resume-entry-value">{{ $employee->national_id_number }}</div>
            </div>
            @endif

            @if($employee->passport_number)
            <div class="resume-entry">
                <div class="resume-entry-label">Passport</div>
                <div class="resume-entry-value">{{ $employee->passport_number }}</div>
            </div>
            @endif

        </div>

        {{-- Job Details --}}
        <div class="resume-section-label-right">Job Details</div>

        <div class="resume-grid-2">

            @if($employee->department)
            <div class="resume-entry">
                <div class="resume-entry-label">Department</div>
                <div class="resume-entry-value">{{ $employee->department }}</div>
            </div>
            @endif

            @if($employee->branch)
            <div class="resume-entry">
                <div class="resume-entry-label">Branch</div>
                <div class="resume-entry-value">{{ $employee->branch }}</div>
            </div>
            @endif

            @if($employee->position)
            <div class="resume-entry">
                <div class="resume-entry-label">Position</div>
                <div class="resume-entry-value">{{ $employee->position }}</div>
            </div>
            @endif

            @if($employee->supervisor)
            <div class="resume-entry">
                <div class="resume-entry-label">Supervisor</div>
                <div class="resume-entry-value">{{ $employee->supervisor }}</div>
            </div>
            @endif

        </div>

        {{-- Employment Dates --}}
        <div class="resume-section-label-right">Employment Dates</div>

        <div class="resume-grid-2">

            @if($employee->probation_start || $employee->probation_end)
            <div class="resume-entry">
                <div class="resume-entry-label">Probation period</div>
                <div class="resume-entry-value">
                    {{ $employee->probation_start?->format('Y-m-d') }}
                    @if($employee->probation_end)
                        &rarr; {{ $employee->probation_end->format('Y-m-d') }}
                    @endif
                </div>
            </div>
            @endif

            @if($employee->contract_start || $employee->contract_end)
            <div class="resume-entry">
                <div class="resume-entry-label">Contract period</div>
                <div class="resume-entry-value">
                    {{ $employee->contract_start?->format('Y-m-d') }}
                    @if($employee->contract_end)
                        &rarr; {{ $employee->contract_end->format('Y-m-d') }}
                    @endif
                </div>
            </div>
            @endif

        </div>

        <hr class="resume-divider">

        {{-- Documents --}}
        <div class="resume-section-label-right">Documents</div>

        <div class="resume-docs">
            @if(!empty($employee->uploads))
                @foreach($employee->uploads as $doc)
                    <a href="{{ asset('storage/' . $doc['path']) }}"
                       target="_blank"
                       class="resume-doc-btn">
                        <i class="bi bi-file-earmark-text"></i>
                        {{ $doc['name'] }}
                    </a>
                @endforeach
            @else
                <span class="resume-no-docs">No documents uploaded</span>
            @endif
        </div>

        <hr class="resume-divider">

        {{-- Actions --}}
        <div class="resume-actions">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>

    </div>{{-- end resume-right --}}

</div>{{-- end resume-card --}}


{{-- ===== STYLES ===== --}}
<style>
/* ---- Card shell ---- */
.resume-card {
    display: flex;
    gap: 0;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    min-height: 680px;
    font-size: 13px;
}

/* ---- Left sidebar ---- */
.resume-left {
    width: 230px;
    flex-shrink: 0;
    background: #f7f7f5;
    padding: 28px 20px;
    border-right: 1px solid #e0e0e0;
}

.resume-photo-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 16px;
}

.resume-photo-circle {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    border: 3px solid #185FA5;
    background: #E6F1FB;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.resume-photo-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.resume-photo-initials {
    font-size: 26px;
    font-weight: 500;
    color: #185FA5;
}

.resume-badges {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 18px;
}

.resume-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 500;
    padding: 3px 10px;
    border-radius: 20px;
}

.resume-badge-id        { background: #E6F1FB; color: #0C447C; }
.resume-badge-active    { background: #EAF3DE; color: #3B6D11; }
.resume-badge-inactive  { background: #f1f1f1; color: #555; }

.resume-section-label {
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: #185FA5;
    border-bottom: 1.5px solid #185FA5;
    padding-bottom: 4px;
    margin: 18px 0 10px;
}

.resume-info-row {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    margin-bottom: 7px;
    color: #333;
    line-height: 1.4;
}

.resume-info-row i {
    font-size: 13px;
    color: #888;
    margin-top: 1px;
    flex-shrink: 0;
}

.resume-salary {
    margin-top: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #185FA5;
}

.resume-salary span {
    font-size: 11px;
    font-weight: 400;
    color: #888;
}

/* ---- Right main ---- */
.resume-right {
    flex: 1;
    padding: 28px 32px;
    min-width: 0;
}

.resume-name {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a1a;
    letter-spacing: 0.03em;
    line-height: 1.2;
    margin-bottom: 3px;
}

.resume-name span {
    color: #185FA5;
}

.resume-role {
    font-size: 13.5px;
    color: #666;
    margin-bottom: 4px;
}

.resume-divider {
    border: none;
    border-top: 1.5px solid #e8e8e8;
    margin: 16px 0;
}

.resume-section-label-right {
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: #185FA5;
    border-bottom: 1.5px solid #185FA5;
    padding-bottom: 4px;
    margin: 16px 0 12px;
}

.resume-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 20px;
    margin-bottom: 6px;
}

.resume-entry-label {
    font-size: 11px;
    color: #888;
    margin-bottom: 2px;
}

.resume-entry-value {
    font-size: 13px;
    font-weight: 500;
    color: #1a1a1a;
}

.resume-docs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}

.resume-doc-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    padding: 4px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    color: #333;
    text-decoration: none;
    background: #fff;
    transition: background 0.15s;
}

.resume-doc-btn:hover {
    background: #f0f0f0;
    color: #185FA5;
    text-decoration: none;
}

.resume-no-docs {
    font-size: 12px;
    color: #aaa;
}

.resume-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

/* ---- Print styles ---- */
@media print {
    .resume-card {
        border: none;
        box-shadow: none;
    }

    .resume-actions,
    .panel.mb-3 {
        display: none !important;
    }

    .resume-left {
        background: #f7f7f5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* ---- Responsive ---- */
@media (max-width: 640px) {
    .resume-card {
        flex-direction: column;
    }

    .resume-left {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e0e0e0;
    }

    .resume-grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

@endsection