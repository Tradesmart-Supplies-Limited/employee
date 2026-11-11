<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApprovalSetting;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The one place that moves a leave request through its approval chain.
 *
 *   Employee submits → Supervisor → HR → Director
 *
 * The Admin chooses which steps are on (Leave Management → Approval chain).
 * The chain is fixed on the request when it is submitted, so changing the
 * settings later never strands requests that are already on their way.
 *
 *   submit()   → assigns the supervisor, works out the chain, emails the first approver
 *   decide()   → one step approves or reverts. Approve moves to the next step;
 *                approval at the LAST step is final and deducts the balance.
 *                A revert at any step ends the request…
 *   override() → …unless a higher approver overrides that revert and approves.
 *   comment()  → anyone in the chain (and the applicant) can add a comment at any time.
 *
 * (Stored decision values stay Approved / Rejected / Skipped; screens and
 *  emails say "Reverted".)
 *
 * Every change sends the workflow emails through LeaveNotifier.
 */
class LeaveWorkflow
{
    /** Copy the employee's current supervisor onto the request, set the chain and notify. */
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

        if (! $leave->approval_chain && $leave->status === 'Pending') {
            $this->buildChain($leave->fresh());
        }

        if ($notify) {
            LeaveNotifier::send($leave->fresh(), LeaveNotifier::SUBMITTED);
        }

        return $leave->refresh();
    }

    /**
     * Fix the chain on the request: the switched-on steps, minus any that
     * cannot happen (no supervisor at all, no Management login, or a short
     * leave below Management's minimum days).
     */
    public function buildChain(LeaveRequest $leave): LeaveRequest
    {
        $settings = LeaveApprovalSetting::current();
        $chain = $settings->steps() ?: ['hr'];
        $skip = [];

        if (in_array('director', $chain, true)) {
            $min = (float) $settings->director_min_days;
            if ($min > 0 && (float) $leave->total_days < $min) {
                $chain = array_values(array_diff($chain, ['director']));
            } elseif (! self::directorUsers()->count()) {
                $skip['director'] = 'Skipped — no Management login is set up';
            }
        }

        if (in_array('supervisor', $chain, true)
            && ! $leave->hasAssignedSupervisor()
            && ! LeaveNotifier::supervisorEmails($leave)) {
            $skip['supervisor'] = 'Skipped — no supervisor or department manager assigned';
        }

        // Never skip everything: whoever is left last still decides.
        if (count($skip) >= count($chain)) {
            unset($skip[last($chain)]);
        }

        if (! $chain) {
            $chain = ['hr'];
        }

        $fill = ['approval_chain' => $chain];
        foreach ($skip as $step => $why) {
            $fill[$step . '_decision'] = 'Skipped';
            $fill[$step . '_comments'] = $why;
        }
        $fill['current_step'] = collect($chain)->first(fn ($s) => ! isset($skip[$s]));

        $leave->forceFill($fill)->save();

        return $leave;
    }

    // ------------------------------------------------------------ who may act

    /** May $user record the decision for $step on this request? */
    public function canAct(LeaveRequest $leave, ?User $user, string $step): bool
    {
        if (! $user || $leave->isFinal() || ! $leave->inChain($step) || filled($leave->{$step . '_decision'})) {
            return false;
        }

        // Nobody approves their own leave.
        if ($this->isApplicant($leave, $user)) {
            return false;
        }

        return $this->entitled($leave, $user, $step);
    }

    /** Does $user hold the $step role for this request (whatever its state)? */
    public function entitled(LeaveRequest $leave, ?User $user, string $step): bool
    {
        if (! $user) {
            return false;
        }

        return match ($step) {
            'supervisor' => $leave->isAssignedSupervisor($user)
                || ($user->isManager() && $leave->employee && $user->canSeeEmployee($leave->employee))
                || $user->hasFullAccess(),
            'hr'       => $user->hasRole('hr', 'admin'),
            'director' => $user->hasRole('director', 'admin'),
            default    => false,
        };
    }

    public function isApplicant(LeaveRequest $leave, ?User $user): bool
    {
        return $user && $user->employee_id && $leave->employee_id && (int) $user->employee_id === (int) $leave->employee_id;
    }

    // ------------------------------------------------------------ comments

    /** The applicant and everyone who holds a step in the chain may comment. */
    public function canComment(LeaveRequest $leave, ?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($this->isApplicant($leave, $user)) {
            return true;
        }
        foreach ($leave->chain() as $step) {
            if ($leave->{$step . '_decision'} !== 'Skipped' && $this->entitled($leave, $user, $step)) {
                return true;
            }
        }

        return false;
    }

    /** How the author appears in the thread: Applicant, Supervisor, HR, Management. */
    public function roleIn(LeaveRequest $leave, User $user): string
    {
        if ($this->isApplicant($leave, $user)) {
            return 'Applicant';
        }
        foreach (array_reverse($leave->chain()) as $step) {
            if ($this->entitled($leave, $user, $step) && ($step !== 'supervisor' || ! $user->hasFullAccess() || $leave->isAssignedSupervisor($user))) {
                return LeaveRequest::STEPS[$step]['label'];
            }
        }

        return $user->roleLabel();
    }

    public function comment(LeaveRequest $leave, User $user, string $body, string $kind = 'comment'): \App\Models\LeaveComment
    {
        $body = trim($body);
        if ($body === '') {
            throw new RuntimeException('Please write a comment before sending.');
        }
        if ($kind === 'comment' && ! $this->canComment($leave, $user)) {
            throw new RuntimeException('Only the applicant and the people in this request\'s approval chain can comment on it.');
        }

        $c = $leave->comments()->create([
            'user_id'     => $user->id,
            'author_name' => $user->name,
            'author_role' => $this->roleIn($leave, $user),
            'kind'        => $kind,
            'body'        => $body,
        ]);

        if ($kind === 'comment') {
            LeaveNotifier::comment($leave->fresh(), $c);
        }

        return $c;
    }

    // ------------------------------------------------------------ override

    /**
     * After a revert, an approver further up the chain may override it.
     * Returns the step they would approve at, or null.
     */
    public function overrideStep(LeaveRequest $leave, ?User $user): ?string
    {
        if (! $user || $leave->status !== 'Rejected' || $this->isApplicant($leave, $user)) {
            return null;
        }
        $reverted = $leave->finalStep();
        $chain = $leave->chain();
        $i = array_search($reverted, $chain, true);
        if ($i === false) {
            return null;
        }
        foreach (array_slice($chain, $i + 1) as $step) {
            if ($leave->{$step . '_decision'} === 'Skipped') {
                continue;
            }
            if ($this->entitled($leave, $user, $step)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * A higher approver overrides a revert: their own step is recorded as
     * approved, and the request carries on (or is finally approved if theirs
     * is the last step). The revert stays on record, marked as overridden.
     */
    public function override(LeaveRequest $leave, User $actor, string $reason, bool $overrideBalance = false): LeaveRequest
    {
        $step = $this->overrideStep($leave, $actor);
        if (! $step) {
            throw new RuntimeException('Only an approver higher up the chain than the person who reverted it can override this decision.');
        }
        if (blank($reason)) {
            throw new RuntimeException('Please add a short note explaining the override, so everyone understands the change.');
        }

        $reverted = $leave->finalStep();
        $chain = $leave->chain();
        $fill = [
            'overridden_step'    => $reverted,
            'overridden_by'      => $actor->id,
            'overridden_by_name' => $actor->name,
            'overridden_at'      => now(),
        ];
        // Steps between the revert and the overrider were never asked: mark them skipped.
        $between = array_slice($chain, array_search($reverted, $chain, true) + 1, max(0, array_search($step, $chain, true) - array_search($reverted, $chain, true) - 1));
        foreach ($between as $s) {
            if (blank($leave->{$s . '_decision'})) {
                $fill[$s . '_decision'] = 'Skipped';
                $fill[$s . '_comments'] = 'Not needed: ' . $actor->name . ' overrode the revert at the ' . LeaveRequest::STEPS[$step]['label'] . ' step';
            }
        }
        $record = $fill + $this->stamp($leave, $actor, $step, 'Approved', $reason);

        $note = 'Overrode the ' . LeaveRequest::STEPS[$reverted]['label'] . ' revert and approved at the '
            . LeaveRequest::STEPS[$step]['label'] . " step.\n\n" . trim($reason);

        $leave->forceFill(array_filter($record, fn ($k) => str_ends_with($k, '_decision'), ARRAY_FILTER_USE_KEY));
        $next = $leave->nextStepAfter($step);

        if ($next) {
            $leave->update($record + ['status' => 'Pending', 'current_step' => $next]);
            $this->comment($leave, $actor, $note, 'override');
            LeaveNotifier::send($leave->fresh(), LeaveNotifier::STEP_DECISION);

            return $leave->refresh();
        }

        // Last step: final approval (balance check + deduction). Re-open first so
        // the final approval can run; put it back if the approval can't go ahead.
        LeaveRequest::whereKey($leave->id)->update(['status' => 'Pending']);
        try {
            $leave = $this->finalizeApproval($leave->refresh(), $actor, $record, $overrideBalance, notify: false);
        } catch (\Throwable $e) {
            LeaveRequest::whereKey($leave->id)->update(['status' => 'Rejected', 'current_step' => null]);
            $leave->refresh();
            throw $e;
        }
        $this->comment($leave, $actor, $note, 'override');
        LeaveNotifier::send($leave->fresh(), LeaveNotifier::FINAL_DECISION);

        return $leave->refresh();
    }

    /**
     * The step $user would decide right now: the current step if they may,
     * otherwise a later step that belongs to them (e.g. the Director), or null.
     */
    public function actingStep(LeaveRequest $leave, ?User $user): ?string
    {
        $current = $leave->currentStep();
        if (! $current) {
            return null;
        }

        $chain = $leave->chain();
        foreach (array_slice($chain, (int) array_search($current, $chain, true)) as $step) {
            if ($this->canAct($leave, $user, $step)) {
                return $step;
            }
        }

        return null;
    }

    // ------------------------------------------------------------ decisions

    /**
     * Record one step's decision.
     *
     * $step may be a later step than the current one when the actor owns it
     * (e.g. Management deciding before HR) — the steps in between are marked
     * "Skipped" so the record shows exactly what happened.
     */
    public function decide(LeaveRequest $leave, User $actor, string $step, string $decision, ?string $comments = null, bool $overrideBalance = false): LeaveRequest
    {
        if (! in_array($decision, ['Approved', 'Rejected'], true)) {
            throw new RuntimeException('Please choose Approve or Revert.');
        }
        if ($leave->isFinal()) {
            throw new RuntimeException('A final decision has already been recorded on this request, so no further action is needed.');
        }
        if (! $leave->inChain($step)) {
            throw new RuntimeException('That approval step isn\'t part of this request\'s approval chain.');
        }
        if (filled($leave->{$step . '_decision'})) {
            throw new RuntimeException(LeaveRequest::STEPS[$step]['title'] . ' has already been recorded. Thank you.');
        }
        if (! $this->canAct($leave, $actor, $step)) {
            throw new RuntimeException($leave->employee_id && (int) $actor->employee_id === (int) $leave->employee_id
                ? 'For fairness, your own leave is approved by someone else.'
                : 'Sorry, the ' . strtolower(LeaveRequest::STEPS[$step]['title']) . ' on this request is recorded by someone else.');
        }
        if ($decision === 'Rejected' && blank($comments)) {
            throw new RuntimeException('Kindly add a short reason, so the applicant understands why it was reverted.');
        }

        $chain = $leave->chain();
        $skipped = [];
        foreach ($chain as $s) {
            if ($s === $step) {
                break;
            }
            if (blank($leave->{$s . '_decision'})) {
                $skipped[$s . '_decision'] = 'Skipped';
                $skipped[$s . '_comments'] = 'Decided by ' . $actor->name . ' at the ' . LeaveRequest::STEPS[$step]['label'] . ' step';
            }
        }

        $record = $skipped + $this->stamp($leave, $actor, $step, $decision, $comments);

        // Reverted anywhere → the request ends (a higher approver may override it).
        if ($decision === 'Rejected') {
            $leave->update($record + ['status' => 'Rejected', 'current_step' => null]);
            LeaveNotifier::send($leave->fresh(), LeaveNotifier::FINAL_DECISION);

            return $leave->refresh();
        }

        $leave->forceFill($skipped);
        $next = $leave->nextStepAfter($step);

        // Approved, more steps to go → hand over to the next approver.
        if ($next) {
            $leave->update($record + ['current_step' => $next]);
            LeaveNotifier::send($leave->fresh(), LeaveNotifier::STEP_DECISION);

            return $leave->refresh();
        }

        // Approved at the last step → final.
        return $this->finalizeApproval($leave, $actor, $record, $overrideBalance);
    }

    /** Kept for older callers: the supervisor step. */
    public function supervisorDecision(LeaveRequest $leave, User $actor, string $decision, ?string $comments): LeaveRequest
    {
        if (! $leave->inChain('supervisor') || $leave->supervisor_decision === 'Skipped') {
            throw new RuntimeException('This request doesn\'t have a supervisor step, so no action is needed here.');
        }

        return $this->decide($leave, $actor, 'supervisor', $decision, $comments);
    }

    /**
     * Approve and finish in one go (used by "Record leave → approve now").
     * The actor's own step records the approval; any other undecided steps
     * are marked as skipped.
     */
    public function approve(LeaveRequest $leave, User $actor, ?string $comments = null, bool $overrideBalance = false): LeaveRequest
    {
        if ($leave->isFinal()) {
            throw new RuntimeException('A final decision has already been recorded on this request, so no further action is needed.');
        }

        $chain = $leave->chain();
        $step = match (true) {
            $actor->isDirector() && in_array('director', $chain, true) => 'director',
            in_array('hr', $chain, true) => 'hr',
            default => last($chain),
        };

        $record = $this->stamp($leave, $actor, $step, 'Approved', $comments);
        foreach ($chain as $s) {
            if ($s !== $step && blank($leave->{$s . '_decision'})) {
                $record[$s . '_decision'] = 'Skipped';
                $record[$s . '_comments'] = 'Recorded directly by ' . $actor->name;
            }
        }

        return $this->finalizeApproval($leave, $actor, $record, $overrideBalance);
    }

    /** Revert at whichever step the actor holds (or the current one). */
    public function reject(LeaveRequest $leave, User $actor, string $comments): LeaveRequest
    {
        if ($leave->isFinal()) {
            throw new RuntimeException('A final decision has already been recorded on this request, so no further action is needed.');
        }

        $step = $this->actingStep($leave, $actor) ?? $leave->currentStep();

        return $this->decide($leave, $actor, $step, 'Rejected', $comments);
    }

    /** Final approval + balance deduction, in one transaction. */
    private function finalizeApproval(LeaveRequest $leave, User $actor, array $record, bool $overrideBalance, bool $notify = true): LeaveRequest
    {
        DB::transaction(function () use ($leave, $actor, $record, $overrideBalance) {
            $locked = LeaveRequest::whereKey($leave->id)->lockForUpdate()->firstOrFail();

            if ($locked->isFinal()) {
                throw new RuntimeException('A final decision has already been recorded on this request, so no further action is needed.');
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
                            '%s has %s day(s) available, and this request is for %s. If you would still like to approve it, please tick "Approve anyway".',
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

            $locked->update($record + [
                'status'         => 'Approved',
                'current_step'   => null,
                'days_accrued'   => $employee?->annual_leave_entitlement,
                'days_available' => $before,
                'days_requested' => $locked->total_days,
                'days_balance'   => $after,
                'balance_deducted' => $deducted,
            ]);
        });

        $leave->refresh();
        if ($notify) {
            LeaveNotifier::send($leave, LeaveNotifier::FINAL_DECISION);
        }

        return $leave;
    }

    /** The columns one step writes. */
    private function stamp(LeaveRequest $leave, User $actor, string $step, string $decision, ?string $comments): array
    {
        $name = $actor->name;
        if ($step === 'supervisor' && $leave->hasAssignedSupervisor() && ! $leave->isAssignedSupervisor($actor) && $leave->assigned_supervisor_name) {
            $name .= ' (on behalf of ' . $leave->assigned_supervisor_name . ')';
        } elseif ($step === 'director' && ! $actor->isDirector()) {
            $name .= ' (on behalf of Management)';
        } elseif ($step === 'hr' && ! $actor->hasRole('hr')) {
            $name .= ' (for HR)';
        }

        return [
            $step . '_decision'       => $decision,
            $step . '_comments'       => $comments,
            $step . '_name'           => $name,
            $step . '_position'       => $this->positionOf($actor),
            $step . '_signature_date' => now(),
            $step . '_acted_by'       => $actor->id,
            $step . '_acted_at'       => now(),
        ];
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

    /**
     * Who a new request from $employee would go to, for the application form:
     * [['key' => 'supervisor', 'title' => 'Supervisor', 'name' => 'Mary Mulenga', 'note' => null], ...]
     */
    public function previewRoute(?Employee $employee): array
    {
        $settings = LeaveApprovalSetting::current();
        $route = [];

        foreach ($settings->steps() ?: ['hr'] as $step) {
            if ($step === 'supervisor') {
                $name = $employee?->supervisor_name;
                if (! $name && $employee) {
                    $probe = new LeaveRequest(['employee_id' => $employee->id]);
                    $probe->setRelation('employee', $employee);
                    $name = LeaveNotifier::supervisorEmails($probe) ? 'Your department manager' : null;
                }
                if (! $name) {
                    continue; // nobody to ask: skipped automatically
                }
                $route[] = ['key' => 'supervisor', 'title' => 'Supervisor', 'name' => $name, 'note' => null];
            } elseif ($step === 'hr') {
                $route[] = ['key' => 'hr', 'title' => 'HR', 'name' => 'Human Resources', 'note' => null];
            } elseif ($step === 'director') {
                if (! self::directorUsers()->count()) {
                    continue;
                }
                $min = (float) $settings->director_min_days;
                $route[] = ['key' => 'director', 'title' => 'Management', 'name' => $settings->director?->name ?? 'Management',
                    'note' => $min > 0 ? 'Only for ' . rtrim(rtrim(number_format($min, 1), '0'), '.') . '+ day requests' : null];
            }
        }

        return $route ?: [['key' => 'hr', 'title' => 'HR', 'name' => 'Human Resources', 'note' => null]];
    }

    /** Director logins (the named Director first, if one is set). */
    public static function directorUsers()
    {
        return User::whereRaw('LOWER(role) = ?', ['director'])->get();
    }

    private function positionOf(User $user): string
    {
        if ($user->isDirector()) {
            return 'Management';
        }

        return $user->employee?->position ?: ucfirst((string) ($user->role ?: 'Staff'));
    }

    private function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1), '0'), '.');
    }
}
