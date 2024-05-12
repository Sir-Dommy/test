<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    
    use HasFactory;
    // public $table = 'jobs';
    protected $fillable = [
        'id',
        'external_id',
        'ip',
        'time_in',
        'time_out',
    ];
}