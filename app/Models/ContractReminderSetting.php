<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractReminderSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'recipients',
        'use_days_before',
        'days_before',
        'use_monthly_fixed',
        'monthly_fixed_days',
        'monthly_lookahead_days',
        'is_active',
        'last_run_at',
    ];

    protected $casts = [
        'recipients' => 'array',
        'days_before' => 'array',
        'monthly_fixed_days' => 'array',
        'use_days_before' => 'boolean',
        'use_monthly_fixed' => 'boolean',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(ContractReminderLog::class);
    }

    /**
     * Recipients as a comma/newline separated string, handy for populating
     * a <textarea> in the settings form.
     */
    public function getRecipientsTextAttribute(): string
    {
        return implode(", ", $this->recipients ?? []);
    }

    /**
     * "14, 7" style string for the days-before input field.
     */
    public function getDaysBeforeTextAttribute(): string
    {
        return implode(', ', $this->days_before ?? []);
    }

    /**
     * "1, 14" style string for the monthly fixed-day input field.
     */
    public function getMonthlyFixedDaysTextAttribute(): string
    {
        return implode(', ', $this->monthly_fixed_days ?? []);
    }
}
