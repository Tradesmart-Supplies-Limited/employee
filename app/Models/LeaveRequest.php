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
    | WORKFLOW STAGES
    |--------------------------------------------------------------------------
    | 1. Submitted            → status Pending, no supervisor decision
    | 2. Supervisor reviewed  → status Pending, supervisor_decision set
    | 3. Final HR decision    → status Approved / Rejected
    | 4. Balance updated      → happens with an Approved final decision
    */
    public const STAGE_SUPERVISOR = 'awaiting_supervisor';
    public const STAGE_HR         = 'awaiting_hr';
    public const STAGE_APPROVED   = 'approved';
    public const STAGE_REJECTED   = 'rejected';

    public const STAGES = [
        self::STAGE_SUPERVISOR => ['label' => 'Awaiting supervisor', 'color' => 'warning', 'icon' => 'bi-person-check'],
        self::STAGE_HR         => ['label' => 'Awaiting HR approval', 'color' => 'info', 'icon' => 'bi-building-check'],
        self::STAGE_APPROVED   => ['label' => 'Approved', 'color' => 'success', 'icon' => 'bi-check-circle'],
        self::STAGE_REJECTED   => ['label' => 'Rejected', 'color' => 'danger', 'icon' => 'bi-x-circle'],
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

        // Supporting Documents
        'supporting_document',

        // Emails
        'email',
    ];

    protected $casts = [
        'date_from'                 => 'date',
        'date_to'                   => 'date',
        'return_date'               => 'date',
        'applicant_signature_date'  => 'date',
        'supervisor_signature_date' => 'date',
        'hr_signature_date'         => 'date',
        'supervisor_acted_at'       => 'datetime',
        'hr_acted_at'               => 'datetime',
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

    /*
    |--------------------------------------------------------------------------
    | STAGE HELPERS
    |--------------------------------------------------------------------------
    */

    public function getStageAttribute(): string
    {
        return match (true) {
            $this->status === 'Approved'        => self::STAGE_APPROVED,
            $this->status === 'Rejected'        => self::STAGE_REJECTED,
            filled($this->supervisor_decision)  => self::STAGE_HR,
            default                             => self::STAGE_SUPERVISOR,
        };
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

    public function isDeductible(): bool
    {
        return in_array($this->leave_type, config('leave.deductible_types', ['Annual']), true);
    }

    /** Filter by workflow stage: ->stage('awaiting_hr') */
    public function scopeStage(Builder $query, ?string $stage): Builder
    {
        return match ($stage) {
            self::STAGE_SUPERVISOR => $query->where('status', 'Pending')->whereNull('supervisor_decision'),
            self::STAGE_HR         => $query->where('status', 'Pending')->whereNotNull('supervisor_decision'),
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

        // 2. Supervisor review
        if (filled($this->supervisor_decision)) {
            $approved = $this->supervisor_decision === 'Approved';
            $steps[] = [
                'key'   => 'supervisor',
                'title' => 'Supervisor review',
                'icon'  => 'bi-person-check',
                'state' => $approved ? 'done' : 'declined',
                'who'   => $this->supervisor_name,
                'when'  => $fmt($this->supervisor_acted_at ?? $this->supervisor_signature_date),
                'note'  => $approved ? 'Recommended for approval' : 'Not recommended',
            ];
        } elseif ($this->isFinal()) {
            $steps[] = [
                'key'   => 'supervisor',
                'title' => 'Supervisor review',
                'icon'  => 'bi-person-check',
                'state' => 'skipped',
                'who'   => $this->assigned_supervisor_name,
                'when'  => null,
                'note'  => 'HR decided directly',
            ];
        } else {
            $steps[] = [
                'key'   => 'supervisor',
                'title' => 'Supervisor review',
                'icon'  => 'bi-person-check',
                'state' => 'current',
                'who'   => $this->assigned_supervisor_name ?? 'Supervisor / Admin',
                'when'  => null,
                'note'  => 'Waiting for review',
            ];
        }

        // 3. Final HR / Admin decision
        $steps[] = match (true) {
            $this->status === 'Approved' => [
                'key' => 'hr', 'title' => 'HR final approval', 'icon' => 'bi-building-check',
                'state' => 'done', 'who' => $this->hr_name,
                'when' => $fmt($this->hr_acted_at ?? $this->hr_signature_date), 'note' => 'Approved',
            ],
            $this->status === 'Rejected' => [
                'key' => 'hr', 'title' => 'HR final approval', 'icon' => 'bi-building-check',
                'state' => 'declined', 'who' => $this->hr_name,
                'when' => $fmt($this->hr_acted_at ?? $this->hr_signature_date), 'note' => 'Rejected',
            ],
            filled($this->supervisor_decision) => [
                'key' => 'hr', 'title' => 'HR final approval', 'icon' => 'bi-building-check',
                'state' => 'current', 'who' => 'HR / Admin', 'when' => null, 'note' => 'Waiting for final decision',
            ],
            default => [
                'key' => 'hr', 'title' => 'HR final approval', 'icon' => 'bi-building-check',
                'state' => 'upcoming', 'who' => 'HR / Admin', 'when' => null, 'note' => 'After supervisor review',
            ],
        };

        // 4. Leave balance
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
                'state' => 'done', 'who' => null, 'when' => $fmt($this->hr_acted_at), 'note' => $note,
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
        $done = collect($this->timeline())
            ->whereIn('state', ['done', 'declined', 'skipped', 'cancelled'])
            ->count();

        return (int) round($done / 4 * 100);
    }
}
