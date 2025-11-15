<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceHistory extends Model
{
    // ✅ FIXED: Specify the correct table name
    protected $table = 'attendance_history';

    protected $fillable = [
        'attendance_date',
        'total_cadets',
        'present_count',
        'late_count',
        'absent_count'
    ];

    // Disable timestamps if not using them, or keep if you want created_at/updated_at
    public $timestamps = true;
}
