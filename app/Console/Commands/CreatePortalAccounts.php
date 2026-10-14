<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use App\Services\PortalAccounts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreatePortalAccounts extends Command
{
    /** php artisan portal:create-accounts [--dry-run] */
    protected $signature = 'portal:create-accounts
        {--dry-run : List who would get an account without creating anything}
        {--send-emails : Email each employee their login details (and notify HR) instead of only saving a CSV}';

    protected $description = 'Create self-service portal logins for every Active employee that does not have one yet.';

    public function handle(): int
    {
        $employees = Employee::whereDoesntHave('user')
            ->where('employment_status', 'Active')
            ->orderBy('first_name')
            ->get();

        if ($employees->isEmpty()) {
            $this->info('Every active employee already has a portal account.');
            return self::SUCCESS;
        }

        $rows    = [];
        $skipped = [];

        foreach ($employees as $employee) {
            $email = $employee->company_email ?: $employee->personal_email;

            if (! $email) {
                $skipped[] = [$employee->employee_id, $employee->full_name, 'no email on record'];
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $skipped[] = [$employee->employee_id, $employee->full_name, "email {$email} already used by another user"];
                continue;
            }

            if ($this->option('dry-run')) {
                $rows[] = [$employee->employee_id, $employee->full_name, $email, '(dry run)'];
                continue;
            }

            if ($this->option('send-emails')) {
                $result = app(PortalAccounts::class)->create($employee, 'System (portal:create-accounts)');
                // Only keep the password in the CSV when the email could not be delivered.
                $rows[] = [$employee->employee_id, $employee->full_name, $email, $result['emailed'] ? '(emailed)' : $result['password']];
                continue;
            }

            $temp = Str::random(4) . '-' . Str::random(4) . '-' . random_int(10, 99);

            User::create([
                'name'                 => $employee->full_name,
                'email'                => $email,
                'password'             => $temp,
                'role'                 => 'employee',
                'employee_id'          => $employee->id,
                'must_change_password' => true,
            ]);

            $rows[] = [$employee->employee_id, $employee->full_name, $email, $temp];
        }

        $this->table(['EIN', 'Name', 'Login email', 'Temporary password'], $rows);

        if ($skipped) {
            $this->warn('Skipped:');
            $this->table(['EIN', 'Name', 'Reason'], $skipped);
        }

        if (! $this->option('dry-run') && $rows) {
            $file = 'portal-credentials-' . now()->format('Ymd-His') . '.csv';
            $csv  = "EIN,Name,Email,Temporary password\n";
            foreach ($rows as $r) {
                $csv .= '"' . implode('","', array_map(fn ($v) => str_replace('"', '""', $v), $r)) . "\"\n";
            }
            Storage::disk('local')->put($file, $csv);

            $this->info('Created ' . count($rows) . " account(s). Credentials saved to storage/app/private/{$file}");
            $this->warn('Distribute the passwords securely, then delete that file.');
        }

        return self::SUCCESS;
    }
}
