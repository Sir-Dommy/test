<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jobs extends Model
{
    
    use HasFactory;
    // public $table = 'jobs';
    protected $fillable = [
        'job_title',
        'ref_no',
        'emp_terms',
        'positions',
        'deadline',
    ];
}
