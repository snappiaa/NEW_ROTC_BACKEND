<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cadet extends Model
{
    use HasFactory;

    // Specify the table name (if different from 'cadets')
    protected $table = 'cadets';

    // Set the primary key to 'cadetid' instead of default 'id'
    protected $primaryKey = 'cadetid';

    // If cadetid is not auto-incrementing (like "231-0282"), set this to false
    public $incrementing = false;

    // If cadetid is a string (not integer), set the key type
    protected $keyType = 'string';

    // Allow mass assignment for these fields
    protected $fillable = [
        'cadet_id',
        'name',
        'designation',
        'course_year',
        'sex'
    ];
}
