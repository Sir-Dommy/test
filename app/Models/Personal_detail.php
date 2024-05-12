<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal_detail extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id_no',
        'external_id',
        'salutation',
        'name',
        'date_of_birth',
        'gender',
        'ethnicity',
        'pwd_status',
        'county',
        'constituency',
        'sub_county',
        'ward',
        'postal_address',
        'postal_code',
        'postal_town',
    ];
}
