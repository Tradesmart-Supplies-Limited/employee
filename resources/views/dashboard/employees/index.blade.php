@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">

  <h3 class="mb-0">Employees</h3>

  <div class="d-flex gap-2">

    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
      <i class="bi bi-plus"></i> Add
    </a>

    <button class="btn btn-outline-secondary btn-sm" onclick="toggleView()">
      <i class="bi bi-grid"></i> Toggle View
    </button>

  </div>

</div>


  <div class="panel">

<div id="cardView" style="display:none">

@foreach($employees->groupBy('department') as $department => $group)

  <div class="mb-4">

    <h6 class="text-muted mb-2">
      {{ $department ?? 'Unassigned' }}
    </h6>

    <div class="row g-3">

      @foreach($group as $employee)

        <div class="col-md-4">

          <div class="panel p-3">

            <div class="d-flex align-items-center gap-3 mb-2">

              <img src="{{ $employee->passport_photo
                    ? asset('storage/' . $employee->passport_photo)
                    : asset('assets/images/avatar/avatar.jpg') }}"
                   class="rounded-circle border"
                   width="45"
                   height="45"
                   style="object-fit: cover;">

              <div>
                <div class="fw-semibold">
                  {{ $employee->first_name }} {{ $employee->last_name }}
                </div>

                <small class="text-muted">
                  {{ $employee->position }}
                </small>
              </div>

            </div>

            <div class="d-flex justify-content-between small text-muted mb-2">
              <span>{{ $employee->primary_phone }}</span>

              <span class="badge bg-{{ $employee->employment_status == 'Active' ? 'success' : 'secondary' }}">
                {{ $employee->employment_status }}
              </span>
            </div>

            <div class="d-flex gap-2">

              <a href="{{ route('employees.show', $employee->id) }}"
                 class="btn btn-sm btn-outline-info w-100">
                View
              </a>

              <a href="{{ route('employees.edit', $employee->id) }}"
                 class="btn btn-sm btn-outline-warning w-100">
                Edit
              </a>

            </div>

          </div>

        </div>

      @endforeach

    </div>

  </div>

@endforeach

</div>

<div id="tableView">

  <div class="panel">

    <table class="table table-hover align-middle">

      <thead>
        <tr>
          <th>EIN</th>
          <th>Name</th>
          <th>Position</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        @foreach($employees as $employee)
        <tr>
          <td>{{ $employee->employee_id }}</td>
          <td>
            {{ $employee->first_name }} {{ $employee->last_name }}
          </td>
          <td>{{ $employee->position }}</td>
          <td>{{ $employee->primary_phone }}</td>
          <td>
            <span class="badge bg-success">
              {{ $employee->employment_status }}
            </span>
          </td>
          <td>
            <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-sm btn-info">View</a>
            <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-sm btn-warning">Edit</a>
          </td>
        </tr>
        @endforeach
      </tbody>

    </table>

  </div>

</div>

<script>
function toggleView() {
  const card = document.getElementById('cardView');
  const table = document.getElementById('tableView');

  if (card.style.display === 'none') {
    card.style.display = 'block';
    table.style.display = 'none';
  } else {
    card.style.display = 'none';
    table.style.display = 'block';
  }
}
</script>

  </div>

@endsection