<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalanceAdjustment extends Model
{
    public const TYPES = [
        'opening'    => 'Opening balance',
        'approval'   => 'Leave approved',
        'restore'    => 'Leave cancelled / deleted',
        'manual'     => 'Manual adjustment',
        'edit'       => 'Edited on employee form',
        'year_start' => 'New leave year',
    ];

    protected $fillable = [
        'employee_id', 'leave_request_id', 'user_id', 'type',
        'days', 'balance_before', 'balance_after', 'year', 'reason',
    ];

    protected $casts = [
        'days'           => 'decimal:1',
        'balance_before' => 'decimal:1',
        'balance_after'  => 'decimal:1',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
