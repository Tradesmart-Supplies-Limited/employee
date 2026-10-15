{{-- Include on dashboard/employees/show.blade.php:  @include('dashboard.employees.partials.portal-account') --}}
<div class="panel mb-3">
    <div class="d-flex align-items-center flex-wrap gap-3">
        <div class="flex-grow-1">
            <h6 class="mb-1"><i class="bi bi-person-lock me-1"></i> Self-Service Portal Access</h6>

            @if($employee->user)
                <small class="text-muted">
                    Login: <strong>{{ $employee->user->email }}</strong>
                    &middot; Role: <strong>{{ $employee->user->roleLabel() }}</strong>
                    <a href="{{ route('users.index', ['q' => $employee->user->email]) }}" class="ms-1">change</a>
                    @if($employee->user->isDirector())
                        &middot; <span class="text-muted">not counted in attendance or leave reports</span>
                    @endif
                    @if($employee->user->must_change_password)
                        &middot; <span class="text-warning">has not set their own password yet</span>
                    @endif
                </small>
            @else
                <small class="text-muted">This employee cannot sign in to the portal yet. Creating an account emails them their login details and a short how-to, and notifies HR.</small>
            @endif
        </div>

        <div class="d-flex gap-2">
            @if($employee->user)
                <form method="POST" action="{{ route('employees.portal.reset', $employee) }}"
                      onsubmit="return confirm('Generate a new temporary password and email it to the employee?')">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-key"></i> Reset password</button>
                </form>
                <form method="POST" action="{{ route('employees.portal.destroy', $employee) }}"
                      onsubmit="return confirm('Remove this employee\'s portal access?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-person-x"></i> Remove access</button>
                </form>
            @else
                <form method="POST" action="{{ route('employees.portal.store', $employee) }}"
                      onsubmit="return confirm('Create a portal login and email the details to {{ $employee->company_email ?: $employee->personal_email ?: 'the employee' }}?')">
                    @csrf
                    <button class="btn btn-primary btn-sm"><i class="bi bi-envelope-plus"></i> Create account &amp; email login</button>
                </form>
            @endif
        </div>
    </div>
</div>
