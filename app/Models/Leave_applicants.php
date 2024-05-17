<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave_applicants extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'external_id',
        'name',
        'gender',
        'department',
        'postal_address',
        'mobile_no',
        'sign'
    ];
}
