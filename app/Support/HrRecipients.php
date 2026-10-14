<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Everyone on the HR side who should hear about account events:
 * HR_NOTIFICATION_EMAILS from .env  +  every admin / HR user account.
 */
class HrRecipients
{
    /** @return string[] lower-cased, unique, valid addresses */
    public static function all(array $except = []): array
    {
        $users = User::whereNull('employee_id')
            ->whereIn(DB::raw('LOWER(role)'), ['admin', 'hr'])
            ->pluck('email')
            ->all();

        $except = array_map('strtolower', array_filter($except));

        return collect(config('company.hr_emails', []))
            ->merge($users)
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL) && ! in_array($e, $except, true))
            ->unique()
            ->values()
            ->all();
    }

    /** First HR address, shown to employees as "contact HR". */
    public static function contact(): ?string
    {
        return config('company.hr_emails')[0] ?? config('mail.from.address');
    }
}
