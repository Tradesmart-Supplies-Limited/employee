<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    /*
    |--------------------------------------------------------------------------
    | APPROVAL CHAIN
    |--------------------------------------------------------------------------
    | Employee submits → each step in the chain approves in turn → the last
    | step's approval is final and updates the balance. A rejection at any
    | step ends the request. The chain is fixed when the request is submitted
    | (see LeaveApprovalSetting); older requests use supervisor → HR.
    */
    public const STEPS = [
        'supervisor' => ['label' => 'Supervisor', 'title' => 'Supervisor approval', 'icon' => 'bi-person-check',   'who' => 'Supervisor'],
        'hr'         => ['label' => 'HR',         'title' => 'HR approval',         'icon' => 'bi-building-check', 'who' => 'HR'],
        'director'   => ['label' => 'Management', 'title' => 'Management approval', 'icon' => 'bi-briefcase',      'who' => 'Management'],
    ];

    public const LEGACY_CHAIN = ['supervisor', 'hr'];

    public const STAGE_SUPERVISOR = 'awaiting_supervisor';
    public const STAGE_HR         = 'awaiting_hr';
    public const STAGE_DIRECTOR   = 'awaiting_director';
    public const STAGE_APPROVED   = 'approved';
    public const STAGE_REJECTED   = 'rejected';

    public const STAGES = [
        self::STAGE_SUPERVISOR => ['label' => 'Awaiting supervisor', 'color' => 'warning', 'icon' => 'bi-person-check'],
        self::STAGE_HR         => ['label' => 'Awaiting HR',         'color' => 'info',    'icon' => 'bi-building-check'],
        self::STAGE_DIRECTOR   => ['label' => 'Awaiting Management', 'color' => 'primary', 'icon' => 'bi-briefcase'],
        self::STAGE_APPROVED   => ['label' => 'Approved',            'color' => 'success', 'icon' => 'bi-check-circle'],
        self::STAGE_REJECTED   => ['label' => 'Rejected',            'color' => 'danger',  'icon' => 'bi-x-circle'],
    ];

    protected $fillable = [

        // Employee Details
        'employee_id',
        'employee_name',
        'employee_number',
        'position',

        // Leave Details
        'leave_type',
        'other_leave_type',
        'date_from',
        'date_to',
        'total_days',
        'calendar_days',
        'exclude_sundays',
        'exclude_saturdays',
        'exclude_holidays',
        'excluded_dates',
        'return_date',
        'reason',

        // Late Application
        'late_application_reason',

        // Applicant
        'applicant_signature_date',

        // Supervisor Section
        'status',
        'supervisor_employee_id',
        'supervisor_user_id',
        'supervisor_comments',
        'supervisor_decision',
        'supervisor_name',
        'supervisor_position',
        'supervisor_signature_date',
        'supervisor_acted_by',
        'supervisor_acted_at',

        // HR Section
        'days_accrued',
        'days_available',
        'days_requested',
        'days_balance',
        'hr_name',
        'hr_position',
        'hr_signature_date',
        'hr_decision',
        'hr_comments',
        'hr_acted_by',
        'hr_acted_at',
        'balance_deducted',

        // Approval chain + Management
        'approval_chain',
        'current_step',
        'director_decision',
        'director_comments',
        'director_name',
        'director_position',
        'director_signature_date',
        'director_acted_by',
        'director_acted_at',

        // Supporting Documents
        'supporting_document',

        // Emails
        'email',
    ];

    /** Pending requests always know which step they are waiting on (also for rows saved outside LeaveWorkflow). */
    protected static function booted(): void
    {
        static::saving(function (self $leave) {
            if ($leave->status === 'Pending' && blank($leave->current_step)) {
                $leave->current_step = $leave->currentStep();
            } elseif (in_array($leave->status, ['Approved', 'Rejected'], true)) {
                $leave->current_step = null;
            }
        });
    }

    protected $casts = [
        'date_from'                 => 'date',
        'date_to'                   => 'date',
        'return_date'               => 'date',
        'applicant_signature_date'  => 'date',
        'supervisor_signature_date' => 'date',
        'hr_signature_date'         => 'date',
        'supervisor_acted_at'       => 'datetime',
        'hr_acted_at'               => 'datetime',
        'director_signature_date'   => 'date',
        'director_acted_at'         => 'datetime',
        'approval_chain'            => 'array',
        'days_available'            => 'decimal:1',
        'days_balance'              => 'decimal:1',
        'balance_deducted'          => 'decimal:1',
        'exclude_sundays'           => 'boolean',
        'exclude_saturdays'         => 'boolean',
        'exclude_holidays'          => 'boolean',
        'excluded_dates'            => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function supervisorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id');
    }

    public function supervisorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function supervisorActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_acted_by');
    }

    public function hrActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_acted_by');
    }

    public function directorActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_acted_by');
    }

    /*
    |--------------------------------------------------------------------------
    | STAGE HELPERS
    |--------------------------------------------------------------------------
    */

    public function getStageAttribute(): string
    {
        if ($this->status === 'Approved') {
            return self::STAGE_APPROVED;
        }
        if ($this->status === 'Rejected') {
            return self::STAGE_REJECTED;
        }

        return 'awaiting_' . ($this->currentStep() ?? 'hr');
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage]['label'];
    }

    public function getStageColorAttribute(): string
    {
        return self::STAGES[$this->stage]['color'];
    }

    public function getStageIconAttribute(): string
    {
        return self::STAGES[$this->stage]['icon'];
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['Approved', 'Rejected'], true);
    }

    public function awaitingSupervisor(): bool
    {
        return $this->stage === self::STAGE_SUPERVISOR;
    }

    // ---------------------------------------------------------- chain

    /** The steps this request goes through, in order (e.g. supervisor, hr, director). */
    public function chain(): array
    {
        $c = $this->approval_chain;

        return is_array($c) && $c ? array_values($c) : self::LEGACY_CHAIN;
    }

    public function inChain(string $step): bool
    {
        return in_array($step, $this->chain(), true);
    }

    /** The step it is waiting on, or null once decided. */
    public function currentStep(): ?string
    {
        if ($this->isFinal()) {
            return null;
        }
        if ($this->current_step && $this->inChain($this->current_step)) {
            return $this->current_step;
        }
        foreach ($this->chain() as $step) {
            if (blank($this->{$step . '_decision'})) {
                return $step;
            }
        }

        return last($this->chain());
    }

    public function isLastStep(?string $step): bool
    {
        return $step !== null && last($this->chain()) === $step;
    }

    /** The chain step after $step (skipping any already skipped), or null. */
    public function nextStepAfter(?string $step): ?string
    {
        $chain = $this->chain();
        $i = array_search($step, $chain, true);
        foreach (array_slice($chain, $i === false ? count($chain) : $i + 1) as $s) {
            if ($this->{$s . '_decision'} !== 'Skipped') {
                return $s;
            }
        }

        return null;
    }

    /** Everything recorded for one step. */
    public function stepRecord(string $step): array
    {
        return [
            'step'     => $step,
            'decision' => $this->{$step . '_decision'},
            'comments' => $this->{$step . '_comments'},
            'name'     => $this->{$step . '_name'},
            'position' => $this->{$step . '_position'},
            'at'       => $this->{$step . '_acted_at'} ?? $this->{$step . '_signature_date'},
        ];
    }

    /** The most recent step someone actually decided (Approved / Rejected). */
    public function lastDecidedStep(): ?string
    {
        $last = null;
        $latest = null;
        foreach ($this->chain() as $step) {
            if (in_array($this->{$step . '_decision'}, ['Approved', 'Rejected'], true)) {
                $at = $this->{$step . '_acted_at'} ?? $this->{$step . '_signature_date'};
                if (! $last || ($at && (! $latest || $at >= $latest))) {
                    $last = $step;
                    $latest = $at;
                }
            }
        }

        return $last;
    }

    /** The step whose decision finished the request (Approved or Rejected). */
    public function finalStep(): ?string
    {
        if (! $this->isFinal()) {
            return null;
        }
        $want = $this->status;
        foreach (array_reverse($this->chain()) as $step) {
            if ($this->{$step . '_decision'} === $want) {
                return $step;
            }
        }

        return filled($this->hr_decision) ? 'hr' : null; // older records
    }

    public function getFinalDeciderNameAttribute(): ?string
    {
        $s = $this->finalStep();

        return $s ? $this->{$s . '_name'} : $this->hr_name;
    }

    public function getFinalCommentsAttribute(): ?string
    {
        $s = $this->finalStep();

        return $s ? $this->{$s . '_comments'} : $this->hr_comments;
    }

    public function getFinalActedAtAttribute()
    {
        $s = $this->finalStep();

        return $s ? ($this->{$s . '_acted_at'} ?? $this->{$s . '_signature_date'}) : $this->hr_acted_at;
    }

    /** "Supervisor, then HR, then Management" */
    public function getChainLabelAttribute(): string
    {
        $names = array_map(fn ($s) => $s === 'director' ? 'Management' : self::STEPS[$s]['label'], array_filter($this->chain(), fn ($s) => $this->{$s . '_decision'} !== 'Skipped'));

        return match (count($names)) {
            0 => 'HR',
            1 => reset($names),
            default => implode(', ', array_slice($names, 0, -1)) . ' and then ' . end($names),
        };
    }

    public function isDeductible(): bool
    {
        return in_array($this->leave_type, config('leave.deductible_types', ['Annual']), true);
    }

    /** Filter by workflow stage: ->stage('awaiting_hr') */
    public function scopeStage(Builder $query, ?string $stage): Builder
    {
        return match ($stage) {
            self::STAGE_SUPERVISOR => $query->where('status', 'Pending')->where('current_step', 'supervisor'),
            self::STAGE_HR         => $query->where('status', 'Pending')->where('current_step', 'hr'),
            self::STAGE_DIRECTOR   => $query->where('status', 'Pending')->where('current_step', 'director'),
            self::STAGE_APPROVED   => $query->where('status', 'Approved'),
            self::STAGE_REJECTED   => $query->where('status', 'Rejected'),
            default                => $query,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | SUPERVISOR HELPERS
    |--------------------------------------------------------------------------
    */

    public function hasAssignedSupervisor(): bool
    {
        return $this->supervisor_employee_id || $this->supervisor_user_id;
    }

    public function getAssignedSupervisorNameAttribute(): ?string
    {
        return $this->supervisorUser?->name ?? $this->supervisorEmployee?->full_name;
    }

    public function supervisorEmail(): ?string
    {
        if ($this->supervisorUser) {
            return $this->supervisorUser->email;
        }

        return $this->supervisorEmployee?->contact_email;
    }

    /** Is this user the assigned supervisor (directly, or as the supervising employee's login)? */
    public function isAssignedSupervisor(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return ($this->supervisor_user_id && $this->supervisor_user_id === $user->id)
            || ($this->supervisor_employee_id && $this->supervisor_employee_id === $user->employee_id);
    }

    /*
    |--------------------------------------------------------------------------
    | TRACKER
    |--------------------------------------------------------------------------
    | Returns the 4 steps shown in the stage tracker (dashboards + emails).
    | state: done | current | declined | skipped | cancelled | upcoming
    */
    public function timeline(): array
    {
        $fmt = fn ($d) => $d ? Carbon::parse($d) : null;
        $days = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
        $current = $this->currentStep();
        $passedCurrent = false;

        // 1. Submitted
        $steps[] = [
            'key'   => 'submitted',
            'title' => 'Submitted',
            'icon'  => 'bi-send-check',
            'state' => 'done',
            'who'   => $this->employee_name,
            'when'  => $this->created_at,
            'note'  => $days($this->total_days) . ' day(s) ' . $this->leave_type,
        ];

        // 2. Each approval step in the chain
        foreach ($this->chain() as $step) {
            $meta = self::STEPS[$step];
            $r = $this->stepRecord($step);
            $last = $this->isLastStep($step);

            if ($r['decision'] === 'Approved') {
                $state = 'done';
                $note = $last ? 'Approved (final)' : 'Approved';
            } elseif ($r['decision'] === 'Rejected') {
                $state = 'declined';
                $note = 'Rejected';
            } elseif ($r['decision'] === 'Skipped') {
                $state = 'skipped';
                $note = $r['comments'] ?: 'Skipped';
            } elseif ($this->isFinal()) {
                $state = $this->status === 'Rejected' ? 'cancelled' : 'skipped';
                $note = $this->status === 'Rejected' ? 'Not needed' : 'Decided at another step';
            } elseif ($step === $current) {
                $state = 'current';
                $note = 'Waiting for approval';
                $passedCurrent = true;
            } else {
                $state = 'upcoming';
                $note = $passedCurrent ? 'Next' : 'Waiting';
            }

            $steps[] = [
                'key'   => $step,
                'title' => $meta['title'],
                'icon'  => $meta['icon'],
                'state' => $state,
                'who'   => $r['name'] ?? match ($step) {
                    'supervisor' => $this->assigned_supervisor_name ?? 'Supervisor',
                    default      => $meta['who'],
                },
                'when'  => in_array($state, ['done', 'declined'], true) ? $fmt($r['at']) : null,
                'note'  => $note,
            ];
        }

        // 3. Leave balance
        if ($this->status === 'Approved') {
            if (! $this->employee_id) {
                $note = 'Not linked to an employee record';
            } elseif ((float) $this->balance_deducted > 0) {
                $note = $days($this->balance_deducted) . ' day(s) deducted · ' . $days($this->days_balance) . ' left';
            } else {
                $note = $this->leave_type . ' leave does not reduce the balance';
            }

            $steps[] = [
                'key' => 'balance', 'title' => 'Balance updated', 'icon' => 'bi-wallet2',
                'state' => 'done', 'who' => null, 'when' => $fmt($this->final_acted_at), 'note' => $note,
            ];
        } else {
            $steps[] = [
                'key' => 'balance', 'title' => 'Balance updated', 'icon' => 'bi-wallet2',
                'state' => $this->status === 'Rejected' ? 'cancelled' : 'upcoming',
                'who' => null, 'when' => null,
                'note' => $this->status === 'Rejected' ? 'No change to balance' : 'On final approval',
            ];
        }

        return $steps;
    }

    /** e.g. "Sundays & public holidays not counted" */
    public function getDayRulesLabelAttribute(): ?string
    {
        if ($this->exclude_sundays === null && $this->exclude_saturdays === null && $this->exclude_holidays === null) {
            return null; // older request, counted in calendar days
        }

        $parts = array_keys(array_filter([
            'Saturdays'       => $this->exclude_saturdays,
            'Sundays'         => $this->exclude_sundays,
            'public holidays' => $this->exclude_holidays,
        ]));

        return $parts
            ? implode(', ', array_slice($parts, 0, -1)) . (count($parts) > 1 ? ' & ' : '') . end($parts) . ' not counted'
            : 'Every calendar day counted';
    }

    /** 0–100, for compact progress bars. */
    public function getProgressAttribute(): int
    {
        $steps = $this->timeline();
        $done = collect($steps)
            ->whereIn('state', ['done', 'declined', 'skipped', 'cancelled'])
            ->count();

        return (int) round($done / max(1, count($steps)) * 100);
    }
}
