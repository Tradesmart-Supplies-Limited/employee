<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['announcement_id', 'employee_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
