<?php

use App\Support\Roles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Role-based access: store every role as admin | hr | director | manager | employee.
 *
 * Nobody loses access they have today:
 *   - "Admin" / "HR" / old spellings  → admin / hr
 *   - linked self-service logins       → employee
 *   - unlinked logins with the default "Employee" role (or anything unknown)
 *     could use the whole admin area before, so they become admin.
 *     Review them under Users & Roles afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->orderBy('id')->each(function ($u) {
            $role = Roles::normalize($u->role);

            if ($role === '' || ($role === Roles::EMPLOYEE && $u->employee_id === null)) {
                $role = $u->employee_id === null ? Roles::ADMIN : Roles::EMPLOYEE;
            }

            if ($role !== $u->role) {
                DB::table('users')->where('id', $u->id)->update(['role' => $role]);
            }
        });
    }

    public function down(): void
    {
        // Lower-case roles work with the old code too; nothing to undo.
    }
};
