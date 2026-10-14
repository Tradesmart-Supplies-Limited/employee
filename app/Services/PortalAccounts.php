<?php

namespace App\Services;

use App\Mail\PortalAccountHrNoticeMail;
use App\Mail\PortalWelcomeMail;
use App\Models\Employee;
use App\Models\User;
use App\Support\HrRecipients;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates / resets / removes employee portal logins and sends the emails:
 *   - employee  → welcome email with login details (sent immediately, not queued)
 *   - HR/admins → notice without the password (queued)
 */
class PortalAccounts
{
    /** @return array{user: User, password: string, emailed: bool} */
    public function create(Employee $employee, string $actorName): array
    {
        if ($employee->user) {
            throw new RuntimeException('This employee already has a portal account.');
        }

        $email = $employee->company_email ?: $employee->personal_email;

        if (! $email) {
            throw new RuntimeException('Add a company or personal email to this employee before creating a portal account.');
        }

        if (User::where('email', $email)->exists()) {
            throw new RuntimeException("A user with the email {$email} already exists. Update the employee's email first.");
        }

        $temp = $this->tempPassword();

        $user = User::create([
            'name'                 => $employee->full_name,
            'email'                => $email,
            'password'             => $temp,
            'role'                 => 'employee',
            'employee_id'          => $employee->id,
            'must_change_password' => true,
        ]);

        $employee->setRelation('user', $user);

        $emailed = $this->sendWelcome($user, $employee, $temp, false);
        $this->notifyHr($employee, 'created', $email, $actorName, $emailed);

        return ['user' => $user, 'password' => $temp, 'emailed' => $emailed];
    }

    /** @return array{user: User, password: string, emailed: bool} */
    public function reset(Employee $employee, string $actorName): array
    {
        $user = $employee->user ?? throw new RuntimeException('This employee has no portal account.');

        $temp = $this->tempPassword();

        $user->update(['password' => $temp, 'must_change_password' => true]);

        $emailed = $this->sendWelcome($user, $employee, $temp, true);
        $this->notifyHr($employee, 'reset', $user->email, $actorName, $emailed);

        return ['user' => $user, 'password' => $temp, 'emailed' => $emailed];
    }

    public function remove(Employee $employee, string $actorName): void
    {
        $user = $employee->user ?? throw new RuntimeException('This employee has no portal account.');
        $email = $user->email;

        $user->delete();

        $this->notifyHr($employee, 'removed', $email, $actorName);
    }

    private function sendWelcome(User $user, Employee $employee, string $temp, bool $isReset): bool
    {
        try {
            Mail::to($user->email)->send(new PortalWelcomeMail($user, $employee, $temp, $isReset));

            return true;
        } catch (\Throwable $e) {
            Log::error('PortalAccounts: welcome email failed for ' . $user->email, ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function notifyHr(Employee $employee, string $event, string $loginEmail, string $actorName, bool $emailed = true): void
    {
        $recipients = HrRecipients::all(except: [$loginEmail]);

        foreach ($recipients as $to) {
            try {
                Mail::to($to)->queue(new PortalAccountHrNoticeMail($employee, $event, $loginEmail, $actorName, $emailed));
            } catch (\Throwable $e) {
                Log::error("PortalAccounts: HR notice to {$to} failed", ['error' => $e->getMessage()]);
            }
        }
    }

    private function tempPassword(): string
    {
        // Readable but random, without look-alike characters: e.g. "Kx7m-Qp3w-92"
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $part = fn () => collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');

        return $part() . '-' . $part() . '-' . random_int(10, 99);
    }
}
