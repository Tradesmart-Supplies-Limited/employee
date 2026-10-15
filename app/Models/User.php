<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\Roles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_id',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /** The employee record this login belongs to (portal accounts only). */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ------------------------------------------------------------ roles

    /** One of App\Support\Roles::ALL, or '' when the stored role is unknown (no access). */
    public function roleKey(): string
    {
        return Roles::normalize($this->role);
    }

    public function roleLabel(): string
    {
        return Roles::label($this->role);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->roleKey(), $roles, true);
    }

    /** Admin, HR and Director: every admin-area permission. */
    public function hasFullAccess(): bool
    {
        return in_array($this->roleKey(), Roles::FULL, true);
    }

    public function isDirector(): bool
    {
        return $this->roleKey() === Roles::DIRECTOR;
    }

    public function isManager(): bool
    {
        return $this->roleKey() === Roles::MANAGER;
    }

    /** May open the admin / HR area. */
    public function canUseAdmin(): bool
    {
        return in_array($this->roleKey(), Roles::ADMIN_AREA, true);
    }

    /** May open a self-service portal: right role AND linked to an employee record. */
    public function canUsePortal(): bool
    {
        return in_array($this->roleKey(), Roles::PORTAL, true) && $this->employee_id !== null;
    }

    /**
     * A portal-ONLY account (role "employee"). Kept under its old name because
     * the login redirect, middleware and portal code all ask this question.
     */
    public function isPortalUser(): bool
    {
        return $this->roleKey() === Roles::EMPLOYEE;
    }

    /** Anyone who can use the admin / HR area. */
    public function isStaff(): bool
    {
        return $this->canUseAdmin();
    }

    /** Admin-area permission check (see Roles::MANAGER_PERMISSIONS). */
    public function hasPermission(string $permission): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        return $this->isManager() && in_array($permission, Roles::MANAGER_PERMISSIONS, true);
    }

    private ?array $managedDepartmentsCache = null;

    /**
     * Departments this user may see in the admin area.
     * null = every department (full access). Managers: their own department,
     * plus the departments of anyone who reports to them.
     */
    public function managedDepartments(): ?array
    {
        if ($this->hasFullAccess()) {
            return null;
        }
        if (! $this->isManager()) {
            return [];
        }
        if ($this->managedDepartmentsCache !== null) {
            return $this->managedDepartmentsCache;
        }

        $own = $this->employee?->department;
        $reports = Employee::where(function ($q) {
            $q->where('supervisor_user_id', $this->id);
            if ($this->employee_id) {
                $q->orWhere('supervisor_employee_id', $this->employee_id);
            }
        })->pluck('department');

        return $this->managedDepartmentsCache = collect([$own])->merge($reports)
            ->map(fn ($d) => trim((string) $d))->filter()->unique()->sort()->values()->all();
    }

    /** May this user see / act on this employee in the admin area? */
    public function canSeeEmployee(?Employee $e): bool
    {
        if (! $e) {
            return $this->hasFullAccess();
        }
        $depts = $this->managedDepartments();

        return $depts === null
            || in_array(trim((string) $e->department), $depts, true)
            || $e->supervisor_user_id === $this->id
            || ($this->employee_id && $e->supervisor_employee_id === $this->employee_id);
    }

    /** Employees who report directly to this admin / HR user. */
    public function supervisedEmployees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_user_id');
    }
}
