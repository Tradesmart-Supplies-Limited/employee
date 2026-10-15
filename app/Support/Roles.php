<?php

namespace App\Support;

/**
 * The five user roles and what each may do.
 *
 *   admin     – everything (system owner)
 *   hr        – everything; can also switch to their own self-service portal
 *   director  – everything in the admin area, but is NOT treated as an employee
 *               (left out of attendance, rotas and leave reports; no portal)
 *   manager   – their department only: leave (supervisor step), attendance, shift rota;
 *               can also switch to their own self-service portal
 *   employee  – self-service portal only
 */
final class Roles
{
    public const ADMIN = 'admin';
    public const HR = 'hr';
    public const DIRECTOR = 'director';
    public const MANAGER = 'manager';
    public const EMPLOYEE = 'employee';

    public const ALL = [self::ADMIN, self::HR, self::DIRECTOR, self::MANAGER, self::EMPLOYEE];

    /** Every admin-area permission. */
    public const FULL = [self::ADMIN, self::HR, self::DIRECTOR];

    /** Can open the admin area at all. */
    public const ADMIN_AREA = [self::ADMIN, self::HR, self::DIRECTOR, self::MANAGER];

    /** Can have a self-service portal (needs a linked, active employee record). */
    public const PORTAL = [self::ADMIN, self::HR, self::MANAGER, self::EMPLOYEE];

    /**
     * What a manager may do in the admin area (always limited to their department).
     *   dashboard        – team dashboard
     *   leave.view       – see the department's leave requests
     *   leave.supervise  – record the supervisor recommendation (not the final HR decision)
     *   leave.reports    – department leave balances and planner
     *   attendance.view  – department attendance, register, monthly summary
     *   attendance.edit  – correct the department's times in the register
     *   rota.manage      – plan and publish the department's shift rota
     */
    public const MANAGER_PERMISSIONS = [
        'dashboard', 'leave.view', 'leave.supervise', 'leave.reports',
        'attendance.view', 'attendance.edit', 'rota.manage',
    ];

    public static function label(?string $role): string
    {
        return match (self::normalize($role)) {
            self::ADMIN    => 'Admin',
            self::HR       => 'HR',
            self::DIRECTOR => 'Director',
            self::MANAGER  => 'Manager',
            self::EMPLOYEE => 'Employee',
            default        => 'No role',
        };
    }

    /** @return array<string, array{label: string, desc: string, color: string, icon: string}> */
    public static function options(): array
    {
        return [
            self::ADMIN    => ['label' => 'Admin',    'color' => '#b91c1c', 'icon' => 'bi-shield-lock',  'desc' => 'Everything, including user roles and system settings.'],
            self::HR       => ['label' => 'HR',       'color' => '#185FA5', 'icon' => 'bi-person-badge', 'desc' => 'Everything. Can switch to their own self-service portal.'],
            self::DIRECTOR => ['label' => 'Director', 'color' => '#7c3aed', 'icon' => 'bi-briefcase',    'desc' => 'Everything in the admin area. Not counted as an employee in attendance, rotas or leave reports. No portal.'],
            self::MANAGER  => ['label' => 'Manager',  'color' => '#0f766e', 'icon' => 'bi-diagram-3',    'desc' => 'Their department only: leave (supervisor step), attendance and shift rota. Can switch to their own portal.'],
            self::EMPLOYEE => ['label' => 'Employee', 'color' => '#475569', 'icon' => 'bi-person',       'desc' => 'Self-service portal only.'],
        ];
    }

    /** Lower-cases and maps old spellings ("Administrator", "Human Resources"…). Unknown → ''. */
    public static function normalize(?string $role): string
    {
        $r = strtolower(trim((string) $role));
        $r = match ($r) {
            'administrator', 'super admin', 'superadmin', 'super-admin', 'system admin' => self::ADMIN,
            'human resources', 'human resource', 'hr manager', 'hr officer'            => self::HR,
            'md', 'managing director', 'ceo', 'executive'                               => self::DIRECTOR,
            'supervisor', 'head of department', 'hod', 'line manager'                   => self::MANAGER,
            'staff', 'user'                                                             => self::EMPLOYEE,
            default                                                                     => $r,
        };

        return in_array($r, self::ALL, true) ? $r : '';
    }

    /**
     * Plain-language guide per role for the welcome and sign-in pages:
     * who it is for, what it opens, where you land, and a sign-in tip.
     *
     * @return array<string, array{who: string, can: string[], lands: string, tip: string}>
     */
    public static function guide(): array
    {
        return [
            self::EMPLOYEE => [
                'who'   => 'Everyone who works at Tradesmart.',
                'can'   => ['See your leave balance and apply for leave', 'Check this week\'s shifts and the team rota', 'Show your QR badge and attendance record', 'Download payslips and update your details'],
                'lands' => 'Your self-service portal',
                'tip'   => 'Your first password is in the welcome email from HR. You\'ll choose your own the first time you sign in.',
            ],
            self::MANAGER => [
                'who'   => 'Team leads and heads of department.',
                'can'   => ['Recommend your team\'s leave before HR decides', 'See who is in, late or absent in your department', 'Plan and publish the weekly shift rota', 'Switch to your own portal for your leave and payslips'],
                'lands' => 'Your department dashboard',
                'tip'   => 'You only see your own department. If your team looks wrong, ask HR to check your employee record.',
            ],
            self::HR => [
                'who'   => 'The HR team.',
                'can'   => ['Manage employee records, contracts and documents', 'Give the final decision on leave and overtime', 'Run attendance, kiosks and rotas for every department', 'Send announcements and switch to your own portal'],
                'lands' => 'The HR dashboard',
                'tip'   => 'Use your work email. HR logins are created under Users & Roles.',
            ],
            self::DIRECTOR => [
                'who'   => 'Company directors.',
                'can'   => ['See everything HR sees, across the company', 'Review leave, attendance and staff reports', 'Approve when HR is away', 'Stay out of attendance and leave reports yourself'],
                'lands' => 'The HR dashboard',
                'tip'   => 'Directors don\'t have a self-service portal and aren\'t asked to scan in.',
            ],
            self::ADMIN => [
                'who'   => 'Whoever looks after the system.',
                'can'   => ['Add logins and decide what each person can do', 'Change attendance, leave and reminder settings', 'Everything HR can do', 'Keep at least one Admin so nobody gets locked out'],
                'lands' => 'The HR dashboard',
                'tip'   => 'The first Admin is created during setup. After that, an Admin adds others under Users & Roles.',
            ],
        ];
    }
}
