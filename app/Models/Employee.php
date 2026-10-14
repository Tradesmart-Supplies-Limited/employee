<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | IDENTIFICATION
        |--------------------------------------------------------------------------
        */
        'employee_id',

        /*
        |--------------------------------------------------------------------------
        | PERSONAL INFO
        |--------------------------------------------------------------------------
        */
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'nationality',
        'nrc_no',
        'passport_number',
        'passport_photo',

        /*
        |--------------------------------------------------------------------------
        | CONTACT INFO
        |--------------------------------------------------------------------------
        */
        'personal_email',
        'company_email',
        'primary_phone',
        'secondary_phone',
        'residential_address',

        /*
        |--------------------------------------------------------------------------
        | JOB INFO
        |--------------------------------------------------------------------------
        */
        'position',
        'department',
        'branch',
        'supervisor',               // display name (kept for reports / CSV import)
        'supervisor_employee_id',   // reports to another employee
        'supervisor_user_id',       // ...or to an admin / HR user
        'employment_status',
        'attendance_qr_version',
        'employment_nature',

        /*
        |--------------------------------------------------------------------------
        | LEAVE
        |--------------------------------------------------------------------------
        */
        'annual_leave_entitlement',
        'leave_balance',

        /*
        |--------------------------------------------------------------------------
        | EMPLOYMENT DATES
        |--------------------------------------------------------------------------
        */
        'probation_start',
        'probation_end',
        'contract_start',
        'contract_end',

        /*
        |--------------------------------------------------------------------------
        | EMERGENCY CONTACT
        |--------------------------------------------------------------------------
        */
        'emergency_name',
        'emergency_relationship',
        'emergency_phone',

        /*
        |--------------------------------------------------------------------------
        | NEXT OF KIN
        |--------------------------------------------------------------------------
        */
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_address',

        /*
        |--------------------------------------------------------------------------
        | FINANCE
        |--------------------------------------------------------------------------
        */
        'bank_name',
        'bank_branch',
        'bank_account_no',
        'bank_sort_code',
        'ssn',
        'nhima_no',
        'tpin',
        'salary',
        'net_salary',

        /*
        |--------------------------------------------------------------------------
        | DOCUMENTS
        |--------------------------------------------------------------------------
        */
        'uploads',
    ];

    /*
    |--------------------------------------------------------------------------
    | CASTS
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'date_of_birth'   => 'date',
        'probation_start' => 'date',
        'probation_end'   => 'date',
        'contract_start'  => 'date',
        'contract_end'    => 'date',
        'uploads'         => 'array',
        'salary'          => 'decimal:2',
        'annual_leave_entitlement' => 'decimal:1',
        'leave_balance'            => 'decimal:1',
    ];

    /*
    |--------------------------------------------------------------------------
    | KEEP `dob` (used by payroll / NAPSA) IN SYNC WITH `date_of_birth`
    |--------------------------------------------------------------------------
    */
    protected static function booted(): void
    {
        static::saving(function (Employee $e) {
            static $hasDob = null;
            $hasDob ??= \Illuminate\Support\Facades\Schema::hasColumn('employees', 'dob');

            if ($hasDob && $e->isDirty('date_of_birth')) {
                $e->setAttribute('dob', $e->date_of_birth ? \Carbon\Carbon::parse($e->date_of_birth)->toDateString() : null);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    // Auto calculate age
    public function getAgeAttribute()
    {
        return $this->date_of_birth
            ? Carbon::parse($this->date_of_birth)->age
            : null;
    }

    // Full name helper
    public function getFullNameAttribute()
    {
        // array_filter avoids a double space when there is no middle name
        return implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]));
    }

    // Passport photo URL helper
    public function getPassportPhotoUrlAttribute()
    {
        return $this->passport_photo
            ? asset($this->passport_photo)
            : asset('assets/images/avatar/default.png');
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD HELPERS
    |--------------------------------------------------------------------------
    */

    public function addUpload($name, $path)
    {
        $uploads = $this->uploads ?? [];

        $uploads[] = [
            'name' => $name,
            'path' => $path,
            'uploaded_at' => now()->toDateTimeString(),
        ];

        $this->uploads = $uploads;
        $this->save();
    }

    public function removeUpload($index)
    {
        $uploads = $this->uploads ?? [];

        if (isset($uploads[$index])) {
            unset($uploads[$index]);
            $this->uploads = array_values($uploads);
            $this->save();
        }
    }

     public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SUPERVISOR (REPORTS TO)
    |--------------------------------------------------------------------------
    */

    public function supervisorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id');
    }

    public function supervisorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    /** Employees who report to this employee. */
    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_employee_id');
    }

    /** Leave requests this employee has to review as a supervisor. */
    public function leaveRequestsToReview(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'supervisor_employee_id');
    }

    /** "employee:12" / "user:3" — the value used by the supervisor dropdown. */
    public function getSupervisorRefAttribute(): ?string
    {
        if ($this->supervisor_employee_id) {
            return 'employee:' . $this->supervisor_employee_id;
        }

        return $this->supervisor_user_id ? 'user:' . $this->supervisor_user_id : null;
    }

    public function getSupervisorNameAttribute(): ?string
    {
        return $this->supervisorEmployee?->full_name
            ?? $this->supervisorUser?->name
            ?? $this->supervisor;
    }

    /** Best email address to reach this employee on. */
    public function getContactEmailAttribute(): ?string
    {
        return $this->user?->email ?: ($this->company_email ?: $this->personal_email);
    }

    /*
    |--------------------------------------------------------------------------
    | LEAVE BALANCE HELPERS
    |--------------------------------------------------------------------------
    */

    /** Deductible leave days approved in the given year (default: current). */
    public function leaveTakenInYear(?int $year = null): float
    {
        return (float) $this->leaveRequests()
            ->where('status', 'Approved')
            ->whereIn('leave_type', config('leave.deductible_types', ['Annual']))
            ->whereYear('date_from', $year ?? now()->year)
            ->sum('total_days');
    }

    /** Deductible days currently waiting for a decision. */
    public function leavePendingDays(): float
    {
        return (float) $this->leaveRequests()
            ->where('status', 'Pending')
            ->whereIn('leave_type', config('leave.deductible_types', ['Annual']))
            ->sum('total_days');
    }

    /** Ledger of every leave balance change. */
    public function balanceAdjustments(): HasMany
    {
        return $this->hasMany(LeaveBalanceAdjustment::class)->latest('id');
    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTS (stored in the `uploads` JSON column)
    |--------------------------------------------------------------------------
    | Each: name, path, category, uploaded_by (hr|employee), uploaded_at, size
    */
    public function getDocumentsAttribute(): \Illuminate\Support\Collection
    {
        return collect($this->uploads ?? [])->map(fn ($d, $i) => (object) [
            'index'       => $i,
            'name'        => $d['name'] ?? basename($d['path'] ?? 'file'),
            'path'        => $d['path'] ?? null,
            'category'    => $d['category'] ?? 'Other',
            'uploaded_by' => $d['uploaded_by'] ?? 'hr',
            'uploaded_at' => isset($d['uploaded_at']) ? \Carbon\Carbon::parse($d['uploaded_at']) : null,
            'size'        => $d['size'] ?? null,
            'ext'         => strtolower(pathinfo($d['path'] ?? '', PATHINFO_EXTENSION)),
        ]);
    }

    /** Store an uploaded file and add it to the documents list. */
    public function attachDocument(\Illuminate\Http\UploadedFile $file, ?string $name = null, string $category = 'Other', string $by = 'hr'): array
    {
        $path = $file->store('uploads/employees/documents', 'public');
        $doc = [
            'name'        => $name ?: $file->getClientOriginalName(),
            'path'        => $path,
            'category'    => $category,
            'uploaded_by' => $by,
            'uploaded_at' => now()->toDateTimeString(),
            'size'        => $file->getSize(),
        ];

        $uploads = $this->uploads ?? [];
        $uploads[] = $doc;
        $this->uploads = array_values($uploads);
        $this->save();

        return $doc;
    }

    /** Remove a document by its index (and delete the file). */
    public function detachDocument(int $index): ?array
    {
        $uploads = $this->uploads ?? [];
        if (! isset($uploads[$index])) {
            return null;
        }

        $doc = $uploads[$index];
        if (! empty($doc['path'])) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($doc['path']);
        }
        unset($uploads[$index]);
        $this->uploads = array_values($uploads);
        $this->save();

        return $doc;
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ProfileChangeRequest::class)->latest();
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->passport_photo ? asset('storage/' . $this->passport_photo) : null;
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr((string) $this->first_name, 0, 1) . substr((string) $this->last_name, 0, 1));
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** Text inside this employee's badge QR code: TSLATT.<EIN>.<signature> */
    public function getAttendanceQrAttribute(): string
    {
        return \App\Services\AttendanceService::qrPayload($this);
    }
}
