<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The leave approval chain an Admin sets up:
 *   Employee → Supervisor → HR → Director (the last switched-on step is final).
 */
class LeaveApprovalSetting extends Model
{
    protected $fillable = ['supervisor_step', 'hr_step', 'director_step', 'director_min_days', 'director_user_id', 'updated_by'];

    protected $casts = [
        'supervisor_step'   => 'boolean',
        'hr_step'           => 'boolean',
        'director_step'     => 'boolean',
        'director_min_days' => 'float',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'supervisor_step' => true, 'hr_step' => true, 'director_step' => true, 'director_min_days' => 0,
        ]);
    }

    /** Switched-on steps, in order. */
    public function steps(): array
    {
        return array_values(array_filter(['supervisor', 'hr', 'director'], fn ($s) => $this->{$s . '_step'}));
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_user_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
