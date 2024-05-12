<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professional_body extends Model
{
    use HasFactory;
    protected $fillable = [
        'external_id',
        'professional_body',
        'membership_type',
        'certificate_no',
        'start_date',
        'end_date',
    ];
}
