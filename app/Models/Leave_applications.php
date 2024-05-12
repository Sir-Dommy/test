<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave_applications extends Model
{
    use HasFactory;
    
    public $fillable = [
        'external_id',
        'designation',
        'leave_type',
        'num_of_days',
        'leave_begins_on',
        'last_leave_taken_from',
        'last_leave_taken_to',
        'leave_address',
        'salary_paid_to',
        'account_no',
        'date',
        'signed',
        'stage',
        'status'
    ];
}
