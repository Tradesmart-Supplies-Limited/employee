<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A comment on a leave request (or an override note). */
class LeaveComment extends Model
{
    protected $fillable = ['leave_request_id', 'user_id', 'author_name', 'author_role', 'kind', 'body'];

    public function leave(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getInitialsAttribute(): string
    {
        $p = preg_split('/\s+/', trim($this->author_name)) ?: ['?'];

        return strtoupper(mb_substr($p[0], 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : ''));
    }
}
