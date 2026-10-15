<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Department filter for admin-area pages.
 *
 * Admin / HR / Director see every department and may pick one (or none = all).
 * Managers only ever see their own department(s): the requested one if it is
 * theirs, otherwise their first.
 */
final class DeptScope
{
    /** Matches no department: used for a manager whose department is unknown. */
    public const NONE = "\u{2205} no department";

    /**
     * @param  string[]  $all  every department name (for the drop-down)
     * @return array{0: ?string, 1: string[]}  [selected department or null for all, departments to offer]
     */
    public static function pick(?User $user, ?string $requested, array $all): array
    {
        $requested = filled($requested) ? $requested : null;
        $mine = $user?->managedDepartments();

        if ($mine === null) {
            return [$requested, $all];
        }
        if (! $mine) {
            return [self::NONE, []];
        }

        return [in_array($requested, $mine, true) ? $requested : $mine[0], $mine];
    }

    /** Limit an Employee query to what the user may see. */
    public static function employees(Builder $query, ?User $user, string $column = 'department'): Builder
    {
        $mine = $user?->managedDepartments();
        if ($mine === null) {
            return $query;
        }

        return $query->where(function ($q) use ($mine, $user, $column) {
            $q->whereIn($column, $mine ?: [self::NONE])
                ->orWhere('supervisor_user_id', $user->id);
            if ($user->employee_id) {
                $q->orWhere('supervisor_employee_id', $user->employee_id);
            }
        });
    }

    /** Limit a query on a model with an employee() relation (leave requests, scans…). */
    public static function viaEmployee(Builder $query, ?User $user): Builder
    {
        if ($user?->managedDepartments() === null) {
            return $query;
        }

        return $query->whereHas('employee', fn ($e) => self::employees($e, $user));
    }
}
