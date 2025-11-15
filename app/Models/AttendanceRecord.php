<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    // ✅ FIXED: Added 'attendance_time' to fillable
    protected $fillable = ['cadet_id', 'status', 'timestamp', 'attendance_date', 'attendance_time'];

    public function cadet()
    {
        return $this->belongsTo(Cadet::class, 'cadet_id', 'cadet_id');
    }
}
