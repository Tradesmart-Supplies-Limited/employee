<?php

namespace App\Services;

use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Employee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Announcements
{
    /**
     * Email announcements that are live, asked for email, and not yet sent
     * (covers "publish now" and scheduled ones once their time comes).
     * Called on save, from the dashboards, and by the scheduler.
     */
    public function emailDue(): int
    {
        if (! Cache::add('announcements-emailing', true, 120)) {
            return 0;
        }
        $sent = 0;
        try {
            Announcement::live()->where('send_email', true)->whereNull('emailed_at')->get()->each(function (Announcement $a) use (&$sent) {
                // Mark first so a slow mail server can't cause a double send.
                $a->forceFill(['emailed_at' => now()])->save();
                $n = 0;
                foreach ($a->audience() as $e) {
                    if (! $to = $e->contact_email) {
                        continue;
                    }
                    try {
                        Mail::to($to)->queue(new AnnouncementMail($a, $e));
                        $n++;
                    } catch (\Throwable $ex) {
                        Log::error("Announcement {$a->id} email to {$to} failed: " . $ex->getMessage());
                    }
                }
                $a->forceFill(['emailed_count' => $n])->save();
                $sent += $n;
            });
        } finally {
            Cache::forget('announcements-emailing');
        }

        return $sent;
    }

    public function unreadCount(Employee $e): int
    {
        $ids = Announcement::visibleTo($e)->pluck('id');
        if ($ids->isEmpty()) {
            return 0;
        }

        return $ids->count() - AnnouncementRead::where('employee_id', $e->id)->whereIn('announcement_id', $ids)->count();
    }

    public function readIds(Employee $e): array
    {
        return AnnouncementRead::where('employee_id', $e->id)->pluck('announcement_id')->all();
    }

    public function markRead(Announcement $a, Employee $e): void
    {
        AnnouncementRead::firstOrCreate(['announcement_id' => $a->id, 'employee_id' => $e->id], ['read_at' => now()]);
    }
}
