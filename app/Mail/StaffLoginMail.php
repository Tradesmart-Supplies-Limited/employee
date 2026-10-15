<?php

namespace App\Mail;

use App\Models\User;
use App\Support\HrRecipients;
use App\Support\Roles;
use Illuminate\Mail\Mailable;

/**
 * Login details for Admin, HR, Director and Manager logins (Employee logins
 * get the portal welcome email instead). Contains a temporary password when
 * one was generated, so it is sent immediately and never queued.
 */
class StaffLoginMail extends Mailable
{
    public function __construct(
        public User $user,
        public ?string $tempPassword,
        public string $createdBy,
        public bool $isReset = false,
    ) {}

    public function build()
    {
        $role = Roles::label($this->user->role);

        return $this->subject($this->isReset
                ? 'Your new temporary password for the HR system'
                : "Your {$role} login for the " . config('company.name') . ' HR system')
            ->view('emails.staff-login', [
                'role'      => $role,
                'roleKey'   => $this->user->roleKey(),
                'color'     => Roles::options()[$this->user->roleKey()]['color'] ?? '#185FA5',
                'guide'     => Roles::guide()[$this->user->roleKey()] ?? null,
                'loginUrl'  => route('login', ['as' => $this->user->roleKey()]),
                'forgotUrl' => route('password.request'),
                'hrContact' => HrRecipients::contact(),
                'portal'    => $this->user->canUsePortal(),
            ]);
    }
}
