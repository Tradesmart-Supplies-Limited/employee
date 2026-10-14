<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The one place that moves a leave request through its stages.
 *
 *   submit()              → assigns the supervisor, emails employee + supervisor + HR
 *   supervisorDecision()  → supervisor (or any admin on their behalf) recommends approve / reject
 *   approve() / reject()  → final HR / Admin decision; approve() deducts the leave balance
 *
 * Every step sends the workflow emails through LeaveNotifier.
 */
class LeaveWorkflow
{
    /** Copy the employee's current supervisor onto the request and notify everyone. */
    public function submit(LeaveRequest $leave, bool $notify = true): LeaveRequest
    {
        if ($leave->employee_id && ! $leave->hasAssignedSupervisor()) {
            $employee = $leave->employee ?? Employee::find($leave->employee_id);

            if ($employee) {
                $leave->forceFill([
                    'supervisor_employee_id' => $employee->supervisor_employee_id,
                    'supervisor_user_id'     => $employee->supervisor_user_id,
                ])->save();
            }
        }

        if ($notify) {
            LeaveNotifier::send($leave->fresh(), LeaveNotifier::SUBMITTED);
        }

        return $leave;
    }

    /** Stage 2 — supervisor recommendation. Status stays Pending until HR decides. */
    public function supervisorDecision(LeaveRequest $leave, User $actor, string $decision, ?string $comments): LeaveRequest
    {
        if (! in_array($decision, ['Approved', 'Rejected'], true)) {
            throw new RuntimeException('Choose Approve or Reject.');
        }

        if ($leave->isFinal()) {
            throw new RuntimeException('A final decision has already been made on this request.');
        }

        if (filled($leave->supervisor_decision)) {
            throw new RuntimeException('The supervisor decision has already been recorded.');
        }

        $name = $actor->name;
        if ($leave->hasAssignedSupervisor() && ! $leave->isAssignedSupervisor($actor) && $leave->assigned_supervisor_name) {
            $name .= ' (on behalf of ' . $leave->assigned_supervisor_name . ')';
        }

        $leave->update([
            'supervisor_decision'       => $decision,
            'supervisor_comments'       => $comments,
            'supervisor_name'           => $name,
            'supervisor_position'       => $this->positionOf($actor),
            'supervisor_signature_date' => now(),
            'supervisor_acted_by'       => $actor->id,
            'supervisor_acted_at'       => now(),
        ]);

        LeaveNotifier::send($leave->fresh(), LeaveNotifier::SUPERVISOR_DECISION);

        return $leave;
    }

    /** Stage 3 + 4 — final approval and balance deduction, in one transaction. */
    public function approve(LeaveRequest $leave, User $actor, ?string $comments = null, bool $overrideBalance = false): LeaveRequest
    {
        DB::transaction(function () use ($leave, $actor, $comments, $overrideBalance) {
            $locked = LeaveRequest::whereKey($leave->id)->lockForUpdate()->firstOrFail();

            if ($locked->isFinal()) {
                throw new RuntimeException('A final decision has already been made on this request.');
            }

            $employee = $locked->employee_id
                ? Employee::whereKey($locked->employee_id)->lockForUpdate()->first()
                : null;

            $before = $after = null;
            $deducted = 0;

            if ($employee) {
                $before = (float) $employee->leave_balance;
                $after  = $before;

                if ($locked->isDeductible()) {
                    $deducted = (float) $locked->total_days;
                    $after    = $before - $deducted;

                    if ($after < 0 && ! config('leave.allow_negative_balance') && ! $overrideBalance) {
                        throw new RuntimeException(sprintf(
                            '%s has only %s day(s) left but requested %s. Tick "Approve anyway" to override.',
                            $employee->full_name, $this->num($before), $this->num($deducted)
                        ));
                    }

                    $locked->setRelation('employee', $employee);
                    app(LeaveBalance::class)->change(
                        $employee, -$deducted, 'approval',
                        "{$locked->leave_type} leave {$locked->date_from->format('d M')}–{$locked->date_to->format('d M Y')}",
                        $actor->id, $locked
                    );
                }
            }

            $locked->update([
                'status'            => 'Approved',
                'days_accrued'      => $employee?->annual_leave_entitlement,
                'days_available'    => $before,
                'days_requested'    => $locked->total_days,
                'days_balance'      => $after,
                'balance_deducted'  => $deducted,
                'hr_decision'       => 'Approved',
                'hr_comments'       => $comments,
                'hr_name'           => $actor->name,
                'hr_position'       => $this->positionOf($actor),
                'hr_signature_date' => now(),
                'hr_acted_by'       => $actor->id,
                'hr_acted_at'       => now(),
            ]);
        });

        $leave->refresh();
        LeaveNotifier::send($leave, LeaveNotifier::FINAL_DECISION);

        return $leave;
    }

    /** Stage 3 — final rejection. Balance is untouched. */
    public function reject(LeaveRequest $leave, User $actor, string $comments): LeaveRequest
    {
        if ($leave->isFinal()) {
            throw new RuntimeException('A final decision has already been made on this request.');
        }

        $leave->update([
            'status'            => 'Rejected',
            'hr_decision'       => 'Rejected',
            'hr_comments'       => $comments,
            'hr_name'           => $actor->name,
            'hr_position'       => $this->positionOf($actor),
            'hr_signature_date' => now(),
            'hr_acted_by'       => $actor->id,
            'hr_acted_at'       => now(),
        ]);

        LeaveNotifier::send($leave->fresh(), LeaveNotifier::FINAL_DECISION);

        return $leave;
    }

    /** Give the days back if an approved, deducted request is deleted. */
    public function restoreBalance(LeaveRequest $leave): void
    {
        if ($leave->status !== 'Approved' || (float) $leave->balance_deducted <= 0 || ! $leave->employee_id) {
            return;
        }

        DB::transaction(function () use ($leave) {
            $employee = Employee::whereKey($leave->employee_id)->lockForUpdate()->first();

            if ($employee) {
                app(LeaveBalance::class)->change(
                    $employee, (float) $leave->balance_deducted, 'restore',
                    "Approved {$leave->leave_type} leave {$leave->date_from->format('d M')}–{$leave->date_to->format('d M Y')} deleted",
                    auth()->id(), null, $leave->date_from->year
                );
            }
        });
    }

    /** When an employee's supervisor changes, move their unreviewed requests to the new one. */
    public function reassignPending(Employee $employee): int
    {
        return LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'Pending')
            ->whereNull('supervisor_decision')
            ->update([
                'supervisor_employee_id' => $employee->supervisor_employee_id,
                'supervisor_user_id'     => $employee->supervisor_user_id,
            ]);
    }

    private function positionOf(User $user): string
    {
        return $user->employee?->position ?: ucfirst((string) ($user->role ?: 'Staff'));
    }

    private function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1), '0'), '.');
    }
}
