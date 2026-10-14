<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotaAssignment extends Model
{
    public const KINDS = ['shift', 'backup', 'off'];

    protected $fillable = ['rota_id', 'employee_id', 'date', 'kind', 'shift_id', 'note'];

    protected $casts = ['date' => 'date'];

    public function rota(): BelongsTo
    {
        return $this->belongsTo(Rota::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(AttendanceShift::class, 'shift_id');
    }

    /** Cell code used by the editor: "s:3", "backup", "off". */
    public function getCodeAttribute(): string
    {
        return $this->kind === 'shift' ? 's:' . $this->shift_id : $this->kind;
    }
}
