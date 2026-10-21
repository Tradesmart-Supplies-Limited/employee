<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Announcement extends Model
{
    public const CATEGORIES = [
        'general'  => ['General', 'bi-megaphone', '#185FA5'],
        'policy'   => ['Policy', 'bi-journal-text', '#7c3aed'],
        'event'    => ['Event', 'bi-calendar-event', '#0891b2'],
        'holiday'  => ['Holiday', 'bi-flag', '#16a34a'],
        'training' => ['Training', 'bi-mortarboard', '#d97706'],
        'safety'   => ['Health & safety', 'bi-shield-check', '#dc2626'],
        'welfare'  => ['Staff welfare', 'bi-heart', '#db2777'],
    ];

    public const PRIORITIES = [
        'normal'    => ['Normal', '#64748b', '#f1f5f9'],
        'important' => ['Important', '#b45309', '#fef3c7'],
        'urgent'    => ['Urgent', '#b91c1c', '#fee2e2'],
    ];

    protected $fillable = [
        'title', 'body', 'category', 'priority', 'departments', 'employee_ids', 'pinned', 'publish_at', 'expires_at',
        'send_email', 'emailed_at', 'emailed_count', 'attachment_path', 'attachment_name', 'created_by',
    ];

    protected $casts = [
        'departments' => 'array',
        'employee_ids' => 'array',
        'pinned'      => 'boolean',
        'send_email'  => 'boolean',
        'publish_at'  => 'datetime',
        'expires_at'  => 'datetime',
        'emailed_at'  => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Live announcements an employee should see (everyone + their department), pinned first. */
    public static function visibleTo(Employee $e)
    {
        return static::live()->with('author')
            ->orderByDesc('pinned')->orderByRaw('COALESCE(publish_at, created_at) DESC')
            ->get()
            ->filter(fn ($a) => $a->isFor($e))
            ->values();
    }

    /** Everyone, or the chosen departments and / or people. */
    public function isForEveryone(): bool
    {
        return empty($this->departments) && empty($this->employee_ids);
    }

    public function isFor(Employee $e): bool
    {
        return $this->isForEveryone()
            || in_array((string) $e->department, $this->departments ?? [], true)
            || in_array((int) $e->id, array_map('intval', $this->employee_ids ?? []), true);
    }

    public function getIsLiveAttribute(): bool
    {
        return (! $this->publish_at || $this->publish_at->lte(now())) && (! $this->expires_at || $this->expires_at->gt(now()));
    }

    public function getStateAttribute(): string
    {
        if ($this->publish_at && $this->publish_at->gt(now())) {
            return 'scheduled';
        }

        return $this->expires_at && $this->expires_at->lte(now()) ? 'expired' : 'live';
    }

    /** A date field in Zambian time, for display and the form. */
    public function local(string $field): ?\Carbon\Carbon
    {
        return $this->$field?->copy()->setTimezone(\App\Services\AttendanceService::tz());
    }

    public function getAudienceLabelAttribute(): string
    {
        if ($this->isForEveryone()) {
            return 'Everyone';
        }
        $parts = $this->departments ?? [];
        if ($n = count($this->employee_ids ?? [])) {
            $parts[] = $n === 1
                ? (Employee::find($this->employee_ids[0])?->full_name ?? '1 person')
                : $n . ' people';
        }

        return implode(', ', $parts);
    }

    /** Admin, HR and Management manage every announcement; others only their own. */
    public function canBeManagedBy(?User $user): bool
    {
        return $user && ($user->hasFullAccess() || (int) $this->created_by === (int) $user->id);
    }

    public function getPostedAtAttribute()
    {
        return $this->publish_at ?? $this->created_at;
    }

    public function getCategoryMetaAttribute(): array
    {
        return self::CATEGORIES[$this->category] ?? self::CATEGORIES['general'];
    }

    public function getPriorityMetaAttribute(): array
    {
        return self::PRIORITIES[$this->priority] ?? self::PRIORITIES['normal'];
    }

    public function getExcerptAttribute(): string
    {
        return Str::limit(preg_replace('/\s+/', ' ', $this->body), 160);
    }

    /** Escaped body with line breaks and clickable links. */
    public function getBodyHtmlAttribute(): string
    {
        $html = nl2br(e($this->body));

        return preg_replace('~(https?://[^\s<]+)~', '<a href="$1" target="_blank" rel="noopener">$1</a>', $html);
    }

    /** Employees this is meant for (active only). */
    public function audience()
    {
        $depts = $this->departments ?? [];
        $ids = array_map('intval', $this->employee_ids ?? []);

        return Employee::whereRaw('LOWER(employment_status) = ?', ['active'])
            ->when(! $this->isForEveryone(), fn ($q) => $q->where(fn ($w) => $w
                ->when($depts, fn ($x) => $x->orWhereIn('department', $depts))
                ->when($ids, fn ($x) => $x->orWhereIn('id', $ids))))
            ->get();
    }
}
