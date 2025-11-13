<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AttendanceRecord extends Model
{
    protected $fillable = ['cadetid', 'status', 'timestamp', 'attendancedate', 'attendancetime'];
    public function cadet() { return $this->belongsTo(Cadet::class, 'cadetid', 'cadetid'); }
}
