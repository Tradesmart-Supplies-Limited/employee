@extends('layouts.app')

@section('content')

<!-- <div class="admin-main"> -->

<!-- <main class="dashboard-content">
<div class="container-fluid px-3 px-lg-4 py-4"> -->

  {{-- HEADER --}}
  <div class="page-heading">
    <div class="page-heading-copy">
      <span class="page-icon"><i class="bi bi-person-plus"></i></span>
      <div>
        <p class="eyebrow mb-1">HR Management</p>
        <h1 class="h3 mb-1">Add Employee</h1>
        <p class="text-muted mb-0">Create complete employee profile</p>
      </div>
    </div>

    <div class="heading-actions">
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('employees.index') }}">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>
  </div>

  <section class="row">

    <div class="col-12 col-xl-12">

      <form class="panel"
            method="POST"
            action="{{ route('employees.store') }}"
            enctype="multipart/form-data">

        @csrf

        {{-- ================= PERSONAL INFO ================= --}}
        <div class="panel-header">
          <h5>Personal Information</h5>
        </div>

        <div class="row g-3">

          <div class="col-md-4">
            <label class="form-label">First Name</label>
            <input class="form-control" name="first_name" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Middle Name</label>
            <input class="form-control" name="middle_name">
          </div>

          <div class="col-md-4">
            <label class="form-label">Last Name</label>
            <input class="form-control" name="last_name" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control" name="date_of_birth" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Gender</label>
            <select class="form-select" name="gender" required>
              <option value="">Select</option>
              <option>Male</option>
              <option>Female</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Nationality</label>
            <input class="form-control" name="nationality">
          </div>

          <div class="col-md-6">
            <label class="form-label">National ID</label>
            <input class="form-control" name="national_id_number">
          </div>

          <div class="col-md-6">
            <label class="form-label">Passport Number</label>
            <input class="form-control" name="passport_number">
          </div>

        </div>

        <hr>

        {{-- ================= CONTACT INFO ================= --}}
        <div class="panel-header">
          <h5>Contact Information</h5>
        </div>

        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label">Personal Email</label>
            <input class="form-control" name="personal_email">
          </div>

          <div class="col-md-6">
            <label class="form-label">Company Email</label>
            <input class="form-control" name="company_email">
          </div>

          <div class="col-md-6">
            <label class="form-label">Primary Phone</label>
            <input class="form-control" name="primary_phone" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Secondary Phone</label>
            <input class="form-control" name="secondary_phone">
          </div>

        </div>

        <hr>

        {{-- ================= JOB INFO ================= --}}
        <div class="panel-header">
          <h5>Job Information</h5>
        </div>

        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label">Position</label>
            <input class="form-control" name="position" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Department</label>
            <input class="form-control" name="department">
          </div>

          <div class="col-md-6">
            <label class="form-label">Branch</label>
            <input class="form-control" name="branch">
          </div>

          <div class="col-md-6">
            <label class="form-label">Supervisor</label>
            <input class="form-control" name="supervisor">
          </div>

          <div class="col-md-6">
            <label class="form-label">Employment Status</label>
            <select class="form-select" name="employment_status">
              <option value="Active">Active</option>
              <option value="Exited">Exited</option>
              <option value="Suspended">Suspended</option>
            </select>
          </div>

        </div>

        <hr>

        {{-- ================= EMPLOYMENT DATES ================= --}}
        <div class="panel-header">
          <h5>Employment Dates</h5>
        </div>

        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label">Probation Start</label>
            <input type="date" class="form-control" name="probation_start">
          </div>

          <div class="col-md-6">
            <label class="form-label">Probation End</label>
            <input type="date" class="form-control" name="probation_end">
          </div>

          <div class="col-md-6">
            <label class="form-label">Contract Start</label>
            <input type="date" class="form-control" name="contract_start">
          </div>

          <div class="col-md-6">
            <label class="form-label">Contract End</label>
            <input type="date" class="form-control" name="contract_end">
          </div>

        </div>

        <hr>

        {{-- ================= EMERGENCY ================= --}}
        <div class="panel-header">
          <h5>Emergency Contact</h5>
        </div>

        <div class="row g-3">

          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input class="form-control" name="emergency_name">
          </div>

          <div class="col-md-4">
            <label class="form-label">Relationship</label>
            <input class="form-control" name="emergency_relationship">
          </div>

          <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input class="form-control" name="emergency_phone">
          </div>

        </div>

        <hr>

        {{-- ================= NEXT OF KIN ================= --}}
        <div class="panel-header">
          <h5>Next of Kin</h5>
        </div>

        <div class="row g-3">

          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input class="form-control" name="next_of_kin_name">
          </div>

          <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input class="form-control" name="next_of_kin_phone">
          </div>

          <div class="col-md-4">
            <label class="form-label">Address</label>
            <input class="form-control" name="next_of_kin_address">
          </div>

        </div>

        <hr>

        {{-- ================= FINANCE ================= --}}
        <div class="panel-header">
          <h5>Finance Information</h5>
        </div>

        <div class="row g-3">

          <div class="col-md-4">
            <label class="form-label">Bank Name</label>
            <input class="form-control" name="bank_name">
          </div>

          <div class="col-md-4">
            <label class="form-label">Account Number</label>
            <input class="form-control" name="bank_account_number">
          </div>

          <div class="col-md-4">
            <label class="form-label">Salary</label>
            <input type="number" class="form-control" name="salary">
          </div>

          <div class="col-md-6">
            <label class="form-label">NSSF Number</label>
            <input class="form-control" name="nssf_number">
          </div>

          <div class="col-md-6">
            <label class="form-label">TIN Number</label>
            <input class="form-control" name="tin_number">
          </div>

        </div>

        <hr>

        {{-- ================= UPLOADS ================= --}}
        <div class="panel-header">
          <h5>Uploads</h5>
        </div>

        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label">Passport Photo</label>
            <input type="file" class="form-control" name="passport_photo">
          </div>

          <div class="col-md-6">
            <label class="form-label">Documents</label>
            <input type="file" class="form-control" name="documents[]" multiple>
          </div>

        </div>

        {{-- ================= ACTIONS ================= --}}
        <div class="d-flex justify-content-end gap-2 mt-4">
          <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button class="btn btn-primary">
            <i class="bi bi-save"></i> Save Employee
          </button>
        </div>

      </form>

    </div>

  </section>

<!-- </div>
</main> -->

<!-- </div> -->

@endsection