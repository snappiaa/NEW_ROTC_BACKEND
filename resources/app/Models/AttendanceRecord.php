<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'cadet_id',
        'status',
        'timestamp',
        'attendance_date',
        'attendance_time',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'attendance_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cadet()
    {
        return $this->belongsTo(Cadet::class, 'cadet_id', 'cadet_id');
    }
}
