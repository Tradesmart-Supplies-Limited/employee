# Add to app/Models/Employee.php  (inside the class; add these imports at the top)

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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


# Add to app/Models/LeaveRequest.php

1) In $fillable, add:   'employee_id',

2) Inside the class, add:

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
