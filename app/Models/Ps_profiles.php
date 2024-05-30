<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ps_profiles extends Model
{
    use HasFactory;
    public $fillable = [
        'approved_by',
        'rejected_by',
        'external_id',
        'date',
        'signed',
        ];
}
