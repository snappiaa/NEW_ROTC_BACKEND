<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cadet extends Model
{
    use HasFactory;

    protected $table = 'cadets';

    // ✅ FIXED: Use default 'id' as primary key, not 'cadetid'
    protected $primaryKey = 'id';

    // ✅ FIXED: Set back to default auto-incrementing
    public $incrementing = true;

    // ✅ FIXED: Set back to integer
    protected $keyType = 'int';

    // ✅ FIXED: Changed 'designation' to 'company' and 'platoon'
    protected $fillable = [
        'cadet_id',
        'name',
        'company',      // ✅ CHANGED
        'platoon',      // ✅ NEW
        'course_year',
        'sex'
    ];
}
