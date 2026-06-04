<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST ALL EMPLOYEES
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $employees = Employee::latest()->paginate(10);

        return view('dashboard.employees.index', compact('employees'));
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW CREATE FORM
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('dashboard.employees.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE EMPLOYEE
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'date_of_birth' => 'required|date',
            'gender' => 'required',
            'primary_phone' => 'required',
            'position' => 'required',
        ]);

        // Generate Employee ID (EIN)
        $employeeId = 'EMP-' . date('Y') . '-' . strtoupper(Str::random(5));

        // Handle passport photo upload
        $passportPath = null;
        if ($request->hasFile('passport_photo')) {
            $file = $request->file('passport_photo');
            $passportPath = $file->store('uploads/employees/passports', 'public');
        }

        $employee = Employee::create([
            'employee_id' => $employeeId,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'nationality' => $request->nationality,
            'national_id_number' => $request->national_id_number,
            'passport_number' => $request->passport_number,
            'passport_photo' => $passportPath,

            'personal_email' => $request->personal_email,
            'company_email' => $request->company_email,
            'primary_phone' => $request->primary_phone,
            'secondary_phone' => $request->secondary_phone,

            'position' => $request->position,
            'department' => $request->department,
            'supervisor' => $request->supervisor,
            'employment_status' => 'Active',

            'probation_start' => $request->probation_start,
            'probation_end' => $request->probation_end,
            'contract_start' => $request->contract_start,
            'contract_end' => $request->contract_end,

            'emergency_name' => $request->emergency_name,
            'emergency_relationship' => $request->emergency_relationship,
            'emergency_phone' => $request->emergency_phone,

            'next_of_kin_name' => $request->next_of_kin_name,
            'next_of_kin_phone' => $request->next_of_kin_phone,
            'next_of_kin_address' => $request->next_of_kin_address,

            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'nssf_number' => $request->nssf_number,
            'tin_number' => $request->tin_number,
            'salary' => $request->salary,

            'uploads' => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | HANDLE MULTIPLE DOCUMENT UPLOADS
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('documents')) {

            $uploads = [];

            foreach ($request->file('documents') as $file) {
                $path = $file->store('uploads/employees/documents', 'public');

                $uploads[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }

            $employee->update([
                'uploads' => $uploads
            ]);
        }

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW SINGLE EMPLOYEE
    |--------------------------------------------------------------------------
    */
    public function show(Employee $employee)
    {
        return view('dashboard.employees.show', compact('employee'));
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT FORM
    |--------------------------------------------------------------------------
    */
    public function edit(Employee $employee)
    {
        return view('dashboard.employees.edit', compact('employee'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE EMPLOYEE
    |--------------------------------------------------------------------------
    */
public function update(Request $request, Employee $employee)
{
    // 1. VALIDATION
    $request->validate([
        'first_name' => 'required',
        'last_name' => 'required',
        'date_of_birth' => 'nullable|date',
        'gender' => 'nullable|string',
        'primary_phone' => 'nullable',
        'position' => 'required',
        'salary' => 'nullable|numeric',
    ]);

    // 2. SAFE DATA (NO $request->all())
    $employee->first_name = $request->first_name;
    $employee->middle_name = $request->middle_name;
    $employee->last_name = $request->last_name;
    $employee->date_of_birth = $request->date_of_birth;
    $employee->gender = $request->gender;
    $employee->nationality = $request->nationality;
    $employee->national_id_number = $request->national_id_number;
    $employee->passport_number = $request->passport_number;

    $employee->personal_email = $request->personal_email;
    $employee->company_email = $request->company_email;
    $employee->primary_phone = $request->primary_phone;
    $employee->secondary_phone = $request->secondary_phone;

    $employee->position = $request->position;
    $employee->department = $request->department;
    $employee->supervisor = $request->supervisor;
    // $employee->employment_status = $request->employment_status;
    $employee->employment_status = $request->employment_status ?? 'Active';

    $employee->probation_start = $request->probation_start;
    $employee->probation_end = $request->probation_end;
    $employee->contract_start = $request->contract_start;
    $employee->contract_end = $request->contract_end;

    $employee->emergency_name = $request->emergency_name;
    $employee->emergency_relationship = $request->emergency_relationship;
    $employee->emergency_phone = $request->emergency_phone;

    $employee->next_of_kin_name = $request->next_of_kin_name;
    $employee->next_of_kin_phone = $request->next_of_kin_phone;
    $employee->next_of_kin_address = $request->next_of_kin_address;

    $employee->bank_name = $request->bank_name;
    $employee->bank_account_number = $request->bank_account_number;
    $employee->nssf_number = $request->nssf_number;
    $employee->tin_number = $request->tin_number;

    $employee->salary = $request->salary ? (float) $request->salary : null;

    // 3. PASSPORT PHOTO UPLOAD
    if ($request->hasFile('passport_photo')) {
        $path = $request->file('passport_photo')
            ->store('uploads/employees/passports', 'public');

        $employee->passport_photo = $path;
    }

    // 4. DOCUMENT UPLOADS (MERGE WITH EXISTING)
    if ($request->hasFile('documents')) {

        $uploads = $employee->uploads ?? [];

        foreach ($request->file('documents') as $file) {
            $path = $file->store('uploads/employees/documents', 'public');

            $uploads[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        $employee->uploads = $uploads;
    }

    // 5. SAVE
    $employee->save();

    return redirect()->route('employees.index')
        ->with('success', 'Employee updated successfully');
}

    /*
    |--------------------------------------------------------------------------
    | DELETE EMPLOYEE
    |--------------------------------------------------------------------------
    */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully');
    }
}