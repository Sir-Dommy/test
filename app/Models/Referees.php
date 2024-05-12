<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referees extends Model
{
    use HasFactory;
    protected $fillable = [
        'external_id',
        'position',
        'full_name',
        'mobile_no',
        'email',
        'period',
    ];
}
