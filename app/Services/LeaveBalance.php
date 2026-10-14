<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalanceAdjustment;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

/**
 * The only place that changes employees.leave_balance, so every change
 * lands in the leave_balance_adjustments ledger.
 */
class LeaveBalance
{
    /** Add (+) or remove (−) days. Call inside a transaction when locking matters. */
    public function change(
        Employee $employee,
        float $days,
        string $type,
        ?string $reason = null,
        ?int $userId = null,
        ?LeaveRequest $leave = null,
        ?int $year = null,
    ): LeaveBalanceAdjustment {
        $before = (float) $employee->leave_balance;
        $after  = round($before + $days, 1);

        $employee->forceFill(['leave_balance' => $after])->save();

        return LeaveBalanceAdjustment::create([
            'employee_id'      => $employee->id,
            'leave_request_id' => $leave?->id,
            'user_id'          => $userId,
            'type'             => $type,
            'days'             => $days,
            'balance_before'   => $before,
            'balance_after'    => $after,
            'year'             => $year ?? ($leave?->date_from?->year ?? now()->year),
            'reason'           => $reason,
        ]);
    }

    /** Set the balance to an exact figure (records the difference). */
    public function set(Employee $employee, float $newBalance, string $type, ?string $reason = null, ?int $userId = null, ?int $year = null): ?LeaveBalanceAdjustment
    {
        $diff = round($newBalance - (float) $employee->leave_balance, 1);

        return $diff == 0.0 ? null : $this->change($employee, $diff, $type, $reason, $userId, null, $year);
    }

    /**
     * Start a new leave year for active employees:
     *   new balance = entitlement + carried-over days (capped at $carryMax; negatives carried in full).
     * Skips anyone already done for that year.
     *
     * @return array{done:int, skipped:int, rows:array}
     */
    public function startYear(int $year, ?float $carryMax, ?int $userId = null, ?callable $filter = null): array
    {
        $done = 0;
        $skipped = 0;
        $rows = [];

        $already = LeaveBalanceAdjustment::where('type', 'year_start')->where('year', $year)->pluck('employee_id')->all();

        $employees = Employee::where('employment_status', 'Active')->orderBy('first_name')->get();
        if ($filter) {
            $employees = $employees->filter($filter);
        }

        foreach ($employees as $employee) {
            if (in_array($employee->id, $already, true)) {
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($employee, $year, $carryMax, $userId, &$rows) {
                $e = Employee::whereKey($employee->id)->lockForUpdate()->first();
                $old = (float) $e->leave_balance;
                $carry = $old < 0 ? $old : ($carryMax === null ? $old : min($old, $carryMax));
                $new = round((float) $e->annual_leave_entitlement + $carry, 1);

                $reason = sprintf(
                    '%s entitlement %s + carried %s (balance was %s%s)',
                    $year, $this->n($e->annual_leave_entitlement), $this->n($carry), $this->n($old),
                    $carryMax !== null && $old > $carryMax ? ', cap ' . $this->n($carryMax) : ''
                );

                $this->change($e, round($new - $old, 1), 'year_start', $reason, $userId, null, $year);
                $rows[] = [$e->full_name, $old, $carry, $new];
            });

            $done++;
        }

        return ['done' => $done, 'skipped' => $skipped, 'rows' => $rows];
    }

    private function n($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    }
}
