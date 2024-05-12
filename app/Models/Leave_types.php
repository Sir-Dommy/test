<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave_types extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'added_by',
        'name',
        'num_of_days',
        'can_carry_forward',
        'is_main',
        'deduct_from_main',
        'max_carry_over_days',
        'max_days_per_application',
        'weekends_included',
        'holidays_included',
        'status'
    ];
}
