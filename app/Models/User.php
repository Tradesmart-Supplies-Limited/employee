<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    /**
     * A portal (self-service) account = role "employee" AND linked to an employee record.
     * Legacy users with the default "Employee" role but no link are still treated as
     * staff, so nobody who can use the admin area today gets locked out.
     */
    public function isPortalUser(): bool
    {
        return strtolower((string) $this->role) === 'employee' && $this->employee_id !== null;
    }

    /** Admin / HR side account (anything that isn't an employee portal login). */
    public function isStaff(): bool
    {
        return ! $this->isPortalUser();
    }

    /** Employees who report directly to this admin / HR user. */
    public function supervisedEmployees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_user_id');
    }
}
