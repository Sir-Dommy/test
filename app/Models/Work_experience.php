<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Work_experience extends Model
{
    use HasFactory;
    protected $fillable = [
        'external_id',
        'position',
        'job_group',
        'mcda',
        'start_date',
        'end_date',
    ];
}
