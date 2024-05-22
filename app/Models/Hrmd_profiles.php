<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hrmd_profiles extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'external_id',
        'leave_start_date',
        'num_of_days',
        'to_resume_on',
        'date',
        'signed',
        'approved_by',
        'rejected_by',
    ];
}
