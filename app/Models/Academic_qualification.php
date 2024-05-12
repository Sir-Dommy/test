<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Academic_qualification extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'external_id',
        'institution_name',
        'admission_no',
        'award',
        'programme_name',
        'grade',
        'certificate_no',
        'start_date',
        'end_date',
        'graduation_date',
    ];
}
