<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rota extends Model
{
    protected $fillable = [
        'department', 'week_start', 'status', 'version', 'notes', 'published_snapshot',
        'created_by', 'updated_by', 'published_by', 'published_at',
    ];

    protected $casts = [
        'week_start'         => 'date',
        'published_at'       => 'datetime',
        'published_snapshot' => 'array',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(RotaAssignment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    /** Mon … Sun as Carbon dates. */
    public function getDaysAttribute(): array
    {
        $start = Carbon::parse($this->week_start->toDateString());

        return array_map(fn ($i) => $start->copy()->addDays($i), range(0, 6));
    }

    public function getWeekEndAttribute(): Carbon
    {
        return Carbon::parse($this->week_start->toDateString())->addDays(6);
    }

    public function getTitleAttribute(): string
    {
        $s = Carbon::parse($this->week_start->toDateString());
        $e = $s->copy()->addDays(6);

        return $this->department . ' · ' . $s->format('d M') . ' – ' . $e->format('d M Y');
    }

}
