<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AttendanceKiosk extends Model
{
    protected $fillable = ['name', 'location', 'token', 'is_active', 'capture_photo', 'last_seen_at', 'last_ip', 'created_by'];

    protected $casts = [
        'is_active'     => 'boolean',
        'capture_photo' => 'boolean',
        'last_seen_at'  => 'datetime',
    ];

    protected $hidden = ['token'];

    public static function newToken(): string
    {
        return Str::random(48);
    }

    /** Online = heard from in the last 3 minutes (the kiosk pings every minute). */
    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(3));
    }

    public function getUrlAttribute(): string
    {
        return route('kiosk.show', $this->token);
    }
}
