<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'nationality',
        'national_id_number',
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

        /*
        |--------------------------------------------------------------------------
        | JOB INFO
        |--------------------------------------------------------------------------
        */
        'position',
        'department',
        'branch',
        'supervisor',
        'employment_status',

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
        'bank_account_number',
        'nssf_number',
        'tin_number',
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
    ];

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
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
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
}