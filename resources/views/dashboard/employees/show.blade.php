@extends('layouts.app')

@section('content')

<!-- <div class="admin-main">

<main class="dashboard-content">
<div class="container-fluid px-3 px-lg-4 py-4"> -->

{{-- HEADER CARD --}}
<div class="panel mb-3">

    <div class="d-flex align-items-center gap-3">

        <img src="{{ $employee->passport_photo
                ? asset('storage/' . $employee->passport_photo)
                : asset('assets/images/avatar/avatar.jpg') }}" class="rounded-circle border"
            alt="{{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->last_name }}" width="80"
            height="80" style="object-fit: cover;">

        <div class="flex-grow-1">
            <h4 class="mb-0">
                {{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->last_name }}
            </h4>

            <small class="text-muted">
                {{ $employee->position }} • {{ $employee->department }}
            </small>
        </div>

        <span class="badge bg-{{ $employee->employment_status == 'Active' ? 'success' : 'secondary' }}">
            {{ $employee->employment_status }}
        </span>

         <a class="btn btn-outline-secondary btn-sm" href="{{ route('employees.index') }}">
            <i class="bi bi-arrow-left"></i> Back
          </a>

    </div>

</div>

{{-- GRID --}}
<div class="row g-3">

    {{-- PERSONAL --}}
    <div class="col-md-6">
        <div class="panel h-100">
            <h5>Personal Information</h5>
            <hr>

            <p><strong>EIN:</strong> {{ $employee->employee_id }}</p>
            <p><strong>DOB:</strong> {{ $employee->date_of_birth }}</p>
            <p><strong>Age:</strong> {{ $employee->age }}</p>
            <p><strong>Gender:</strong> {{ $employee->gender }}</p>
            <p><strong>Nationality:</strong> {{ $employee->nationality }}</p>
            <p><strong>National ID:</strong> {{ $employee->national_id_number }}</p>
            <p><strong>Passport No:</strong> {{ $employee->passport_number }}</p>

        </div>
    </div>

    {{-- CONTACT --}}
    <div class="col-md-6">
        <div class="panel h-100">
            <h5>Contact Information</h5>
            <hr>

            <p><strong>Personal Email:</strong> {{ $employee->personal_email }}</p>
            <p><strong>Company Email:</strong> {{ $employee->company_email }}</p>
            <p><strong>Primary Phone:</strong> {{ $employee->primary_phone }}</p>
            <p><strong>Secondary Phone:</strong> {{ $employee->secondary_phone }}</p>

        </div>
    </div>

    {{-- JOB --}}
    <div class="col-md-6">
        <div class="panel h-100">
            <h5>Job Information</h5>
            <hr>

            <p><strong>Position:</strong> {{ $employee->position }}</p>
            <p><strong>Department:</strong> {{ $employee->department }}</p>
            <p><strong>Supervisor:</strong> {{ $employee->supervisor }}</p>
            <p><strong>Status:</strong> {{ $employee->employment_status }}</p>

        </div>
    </div>

    {{-- EMPLOYMENT DATES --}}
    <div class="col-md-6">
        <div class="panel h-100">
            <h5>Employment Timeline</h5>
            <hr>

            <p><strong>Probation Start:</strong> {{ $employee->probation_start }}</p>
            <p><strong>Probation End:</strong> {{ $employee->probation_end }}</p>
            <p><strong>Contract Start:</strong> {{ $employee->contract_start }}</p>
            <p><strong>Contract End:</strong> {{ $employee->contract_end }}</p>

        </div>
    </div>

    {{-- EMERGENCY --}}
    <div class="col-md-6">
        <div class="panel h-100">
            <h5>Emergency Contact</h5>
            <hr>

            <p><strong>Name:</strong> {{ $employee->emergency_name }}</p>
            <p><strong>Relationship:</strong> {{ $employee->emergency_relationship }}</p>
            <p><strong>Phone:</strong> {{ $employee->emergency_phone }}</p>

        </div>
    </div>

    {{-- NEXT OF KIN --}}
    <div class="col-md-6">
        <div class="panel h-100">
            <h5>Next of Kin</h5>
            <hr>

            <p><strong>Name:</strong> {{ $employee->next_of_kin_name }}</p>
            <p><strong>Phone:</strong> {{ $employee->next_of_kin_phone }}</p>
            <p><strong>Address:</strong> {{ $employee->next_of_kin_address }}</p>

        </div>
    </div>

    {{-- FINANCE --}}
    <div class="col-md-6">
        <div class="panel h-100">
            <h5>Finance</h5>
            <hr>

            <p><strong>Bank:</strong> {{ $employee->bank_name }}</p>
            <p><strong>Account:</strong> {{ $employee->bank_account_number }}</p>
            <p><strong>Salary:</strong> {{ $employee->salary }}</p>
            <p><strong>NSSF:</strong> {{ $employee->nssf_number }}</p>
            <p><strong>TIN:</strong> {{ $employee->tin_number }}</p>

        </div>
    </div>

    {{-- DOCUMENTS --}}
    <div class="col-md-6">
        <div class="panel h-100">
            <h5>Documents</h5>
            <hr>

            @if(!empty($employee->uploads) && is_array($employee->uploads))

            <div class="list-group">

                @foreach($employee->uploads as $doc)
                <a href="{{ asset('storage/' . $doc['path']) }}" target="_blank"
                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">

                    <span>📄 {{ $doc['name'] }}</span>

                    <i class="bi bi-box-arrow-up-right"></i>

                </a>
                @endforeach

            </div>

            @else
            <p class="text-muted">No documents uploaded.</p>
            @endif

        </div>
    </div>

</div>

<!-- </div>
</main>

</div> -->

@endsection