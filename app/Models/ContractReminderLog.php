<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractReminderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'contract_reminder_setting_id',
        'trigger_type',
        'run_date',
        'sent_at',
    ];

    protected $casts = [
        'run_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function setting()
    {
        return $this->belongsTo(ContractReminderSetting::class, 'contract_reminder_setting_id');
    }
}
