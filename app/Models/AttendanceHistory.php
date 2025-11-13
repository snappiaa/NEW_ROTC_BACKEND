<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceHistory extends Model
{
    protected $fillable = [
        'attendancedate', 'totalcadets', 'presentcount', 'latecount', 'absentcount'
    ];
}
