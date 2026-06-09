@extends('layouts.app')

@section('content')

<!-- <div class="admin-main">

  <main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4"> -->

      {{-- HEADER --}}
      <div class="page-heading">
        <div class="page-heading-copy">
          <span class="page-icon"><i class="bi bi-pencil-square"></i></span>
          <div>
            <p class="eyebrow mb-1">HR Management</p>
            <h1 class="h3 mb-1">Edit Employee</h1>
            <p class="text-muted mb-0">
              Update employee information
            </p>
          </div>
        </div>

        <div class="heading-actions">
          <a class="btn btn-outline-secondary btn-sm" href="{{ route('employees.index') }}">
            <i class="bi bi-arrow-left"></i> Back
          </a>
        </div>
      </div>

      {{-- FORM --}}
      <section class="row g-3">

        <div class="col-12 col-xl-8">

          <form class="panel needs-validation"
          method="POST"
          action="{{ route('employees.update', $employee->id) }}"
          enctype="multipart/form-data"
          novalidate>

      @csrf
      @method('PUT')

      {{-- ================= PERSONAL ================= --}}
      <div class="panel-header">
        <h5>Personal Information</h5>
      </div>

      <div class="row g-3">

        <div class="col-md-4">
          <input class="form-control"
                 name="first_name"
                 value="{{ old('first_name', $employee->first_name) }}"
                 placeholder="First Name" required>
        </div>

        <div class="col-md-4">
          <input class="form-control"
                 name="middle_name"
                 value="{{ old('middle_name', $employee->middle_name) }}"
                 placeholder="Middle Name">
        </div>

        <div class="col-md-4">
          <input class="form-control"
                 name="last_name"
                 value="{{ old('last_name', $employee->last_name) }}"
                 placeholder="Last Name" required>
        </div>

        <div class="col-md-4">
          <input type="date"
                 class="form-control"
                 name="date_of_birth"
                 value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
        </div>

        <div class="col-md-4">
          <select class="form-select" name="gender">
            <option value="">Gender</option>
            <option value="Male" {{ $employee->gender == 'Male' ? 'selected' : '' }}>Male</option>
            <option value="Female" {{ $employee->gender == 'Female' ? 'selected' : '' }}>Female</option>
          </select>
        </div>

        <div class="col-md-4">
          <input class="form-control"
                 name="nationality"
                 value="{{ old('nationality', $employee->nationality) }}"
                 placeholder="Nationality">
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="national_id_number"
                 value="{{ old('national_id_number', $employee->national_id_number) }}"
                 placeholder="National ID">
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="passport_number"
                 value="{{ old('passport_number', $employee->passport_number) }}"
                 placeholder="Passport Number">
        </div>

      </div>

      <hr>

      {{-- ================= CONTACT ================= --}}
      <div class="panel-header">
        <h5>Contact Information</h5>
      </div>

      <div class="row g-3">

        <div class="col-md-6">
          <input class="form-control"
                 name="personal_email"
                 value="{{ old('personal_email', $employee->personal_email) }}"
                 placeholder="Personal Email">
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="company_email"
                 value="{{ old('company_email', $employee->company_email) }}"
                 placeholder="Company Email">
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="primary_phone"
                 value="{{ old('primary_phone', $employee->primary_phone) }}"
                 placeholder="Primary Phone">
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="secondary_phone"
                 value="{{ old('secondary_phone', $employee->secondary_phone) }}"
                 placeholder="Secondary Phone">
        </div>

      </div>

      <hr>

      {{-- ================= JOB ================= --}}
      <div class="panel-header">
        <h5>Job Information</h5>
      </div>

      <div class="row g-3">

        <div class="col-md-6">
          <input class="form-control"
                 name="position"
                 value="{{ old('position', $employee->position) }}"
                 required>
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="department"
                 value="{{ old('department', $employee->department) }}">
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="branch"
                 value="{{ old('branch', $employee->branch) }}">
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="supervisor"
                 value="{{ old('supervisor', $employee->supervisor) }}">
        </div>

        <div class="col-md-6">
          <select class="form-select" name="employment_status">
            <option value="Active" {{ $employee->employment_status == 'Active' ? 'selected' : '' }}>Active</option>
            <option value="Exited" {{ $employee->employment_status == 'Exited' ? 'selected' : '' }}>Exited</option>
            <option value="Suspended" {{ $employee->employment_status == 'Suspended' ? 'selected' : '' }}>Suspended</option>
          </select>
        </div>

      </div>

      <hr>

      {{-- ================= DATES ================= --}}
      <div class="panel-header">
        <h5>Employment Dates</h5>
      </div>

      <div class="row g-3">

        <div class="col-md-6">
          <input type="date"
                 class="form-control"
                 name="probation_start"
                 value="{{ old('probation_start', $employee->probation_start) }}">
        </div>

        <div class="col-md-6">
          <input type="date"
                 class="form-control"
                 name="probation_end"
                 value="{{ old('probation_end', $employee->probation_end) }}">
        </div>

        <div class="col-md-6">
          <input type="date"
                 class="form-control"
                 name="contract_start"
                 value="{{ old('contract_start', $employee->contract_start) }}">
        </div>

        <div class="col-md-6">
          <input type="date"
                 class="form-control"
                 name="contract_end"
                 value="{{ old('contract_end', $employee->contract_end) }}">
        </div>

      </div>

      <hr>

      {{-- ================= EMERGENCY ================= --}}
      <div class="panel-header">
        <h5>Emergency Contact</h5>
      </div>

      <div class="row g-3">

        <div class="col-md-4">
          <input class="form-control"
                 name="emergency_name"
                 value="{{ old('emergency_name', $employee->emergency_name) }}"
                 placeholder="Name">
        </div>

        <div class="col-md-4">
          <input class="form-control"
                 name="emergency_relationship"
                 value="{{ old('emergency_relationship', $employee->emergency_relationship) }}"
                 placeholder="Relationship">
        </div>

        <div class="col-md-4">
          <input class="form-control"
                 name="emergency_phone"
                 value="{{ old('emergency_phone', $employee->emergency_phone) }}"
                 placeholder="Phone">
        </div>

      </div>

      <hr>

      {{-- ================= NEXT OF KIN ================= --}}
      <div class="panel-header">
        <h5>Next of Kin</h5>
      </div>

      <div class="row g-3">

        <div class="col-md-4">
          <input class="form-control"
                 name="next_of_kin_name"
                 value="{{ old('next_of_kin_name', $employee->next_of_kin_name) }}"
                 placeholder="Name">
        </div>

        <div class="col-md-4">
          <input class="form-control"
                 name="next_of_kin_phone"
                 value="{{ old('next_of_kin_phone', $employee->next_of_kin_phone) }}"
                 placeholder="Phone">
        </div>

        <div class="col-md-4">
          <input class="form-control"
                 name="next_of_kin_address"
                 value="{{ old('next_of_kin_address', $employee->next_of_kin_address) }}"
                 placeholder="Address">
        </div>

      </div>

      <hr>

      {{-- ================= FINANCE ================= --}}
      <div class="panel-header">
        <h5>Finance</h5>
      </div>

      <div class="row g-3">

        <div class="col-md-4">
          <input class="form-control"
                 name="bank_name"
                 value="{{ old('bank_name', $employee->bank_name) }}"
                 placeholder="Bank">
        </div>

        <div class="col-md-4">
          <input class="form-control"
                 name="bank_account_number"
                 value="{{ old('bank_account_number', $employee->bank_account_number) }}"
                 placeholder="Account">
        </div>

        <div class="col-md-4">
          <input class="form-control"
                 name="salary"
                 value="{{ old('salary', $employee->salary) }}"
                 placeholder="Salary">
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="nssf_number"
                 value="{{ old('nssf_number', $employee->nssf_number) }}"
                 placeholder="NSSF">
        </div>

        <div class="col-md-6">
          <input class="form-control"
                 name="tin_number"
                 value="{{ old('tin_number', $employee->tin_number) }}"
                 placeholder="TIN">
        </div>

      </div>

      <hr>

 {{-- ================= UPLOADS ================= --}}
<div class="panel-header">
  <h5>Uploads</h5>
</div>

<div class="row g-3">

  {{-- PASSPORT PHOTO --}}
  <div class="col-md-6">

    <label class="form-label">Passport Photo</label>

    <div class="d-flex align-items-center gap-3 mb-2">

      <img src="{{ $employee->passport_photo
            ? asset('storage/' . $employee->passport_photo)
            : asset('assets/images/avatar/avatar.jpg') }}"
           width="70"
           height="70"
           class="rounded border"
           style="object-fit: cover;">

      <div class="text-muted small">
        Current photo<br>
        <span class="text-warning">Upload new to replace</span>
      </div>

    </div>

    <input type="file" class="form-control" name="passport_photo">

  </div>

  {{-- DOCUMENTS --}}
  <div class="col-md-6">

    <label class="form-label">Documents</label>

    <input type="file" class="form-control mb-2" name="documents[]" multiple>

    {{-- EXISTING DOCUMENTS --}}
    @if(!empty($employee->uploads) && is_array($employee->uploads))

      <div class="border rounded p-2 bg-light">

        <div class="small text-muted mb-2">Existing Documents</div>

        @foreach($employee->uploads as $doc)
          <div class="d-flex justify-content-between align-items-center mb-1">

            <a href="{{ asset('storage/' . $doc['path']) }}"
               target="_blank"
               class="text-decoration-none small">
              📄 {{ $doc['name'] }}
            </a>

            <span class="text-muted small">view</span>

          </div>
        @endforeach

      </div>

    @else
      <p class="text-muted small">No documents uploaded</p>
    @endif

  </div>

</div>
      {{-- ================= ACTIONS ================= --}}
      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
          Cancel
        </a>

        <button class="btn btn-primary">
          Update Employee
        </button>
      </div>

    </form>

        </div>

        {{-- SIDE PANEL --}}
        <div class="col-12 col-xl-4">

          <div class="panel">

  <h5 class="mb-3">Employee Summary</h5>

  {{-- PROFILE HEADER --}}
  <div class="d-flex align-items-center gap-3 mb-3">

    <img src="{{ $employee->passport_photo
        ? asset('storage/' . $employee->passport_photo)
        : asset('assets/images/avatar/avatar.jpg') }}"
         width="60"
         height="60"
         class="rounded-circle border"
         style="object-fit: cover;">

    <div>
      <div class="fw-semibold">
        {{ $employee->first_name }} {{ $employee->last_name }}
      </div>

      <small class="text-muted">
        {{ $employee->position }}
      </small>

      <div>
        <span class="badge bg-{{ $employee->employment_status == 'Active' ? 'success' : 'secondary' }}">
          {{ $employee->employment_status }}
        </span>
      </div>
    </div>

  </div>

  <hr>

  {{-- QUICK STATS --}}
  <div class="mb-3">

    <div class="d-flex justify-content-between mb-1">
      <span class="text-muted">Employee ID</span>
      <span class="fw-semibold">{{ $employee->employee_id }}</span>
    </div>

    <div class="d-flex justify-content-between mb-1">
      <span class="text-muted">Department</span>
      <span class="fw-semibold">{{ $employee->department ?? '—' }}</span>
    </div>

    <div class="d-flex justify-content-between mb-1">
      <span class="text-muted">Supervisor</span>
      <span class="fw-semibold">{{ $employee->supervisor ?? '—' }}</span>
    </div>

    <div class="d-flex justify-content-between mb-1">
      <span class="text-muted">Age</span>
      <span class="fw-semibold">{{ $employee->age ?? '—' }}</span>
    </div>

  </div>

  <hr>

  {{-- CONTACT QUICK ACCESS --}}
  <div class="mb-3">

    <div class="small text-muted mb-1">Contact</div>

    <div class="d-flex justify-content-between mb-1">
      <span>Email</span>
      <span class="fw-semibold text-truncate" style="max-width: 150px;">
        {{ $employee->personal_email ?? '—' }}
      </span>
    </div>

    <div class="d-flex justify-content-between">
      <span>Phone</span>
      <span class="fw-semibold">
        {{ $employee->primary_phone ?? '—' }}
      </span>
    </div>

  </div>

  <hr>

  {{-- CONTRACT STATUS --}}
  <div class="mb-3">

    <div class="small text-muted mb-1">Contract</div>

    <div class="d-flex justify-content-between mb-1">
      <span>Start</span>
      <span class="fw-semibold">{{ $employee->contract_start ?? '—' }}</span>
    </div>

    <div class="d-flex justify-content-between mb-1">
      <span>End</span>
      <span class="fw-semibold">{{ $employee->contract_end ?? '—' }}</span>
    </div>

    <div class="d-flex justify-content-between">
      <span>Probation</span>
      <span class="fw-semibold">
        {{ $employee->probation_start ?? '—' }} → {{ $employee->probation_end ?? '—' }}
      </span>
    </div>

  </div>

  <hr>

  {{-- FINANCIAL SNAPSHOT --}}
  <div>

    <div class="small text-muted mb-1">Finance</div>

    <div class="d-flex justify-content-between mb-1">
      <span>Salary</span>
      <span class="fw-semibold">
        {{ number_format($employee->salary ?? 0) }}
      </span>
    </div>

    <div class="d-flex justify-content-between mb-1">
      <span>Bank</span>
      <span class="fw-semibold">
        {{ $employee->bank_name ?? '—' }}
      </span>
    </div>

  </div>

</div>

        </div>

      </section>

    <!-- </div>
  </main>

</div> -->

@endsection