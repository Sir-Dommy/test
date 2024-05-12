<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Previous_job_details extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'external_id',
        'institution_category',
        'public_institution',
        'station',
        'employment_number',
        'present_designation',
        'current_appointment_date',
        'previous_effective_date',
        'previous_designation',
        'job_group',
        'terms_of_service',
    ];
}
