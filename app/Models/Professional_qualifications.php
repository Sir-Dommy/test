<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professional_qualifications extends Model
{
    use HasFactory;
    public $table ='professional_qualification';
    protected $fillable = [
        'external_id',
        'institution_name',
        'course_name',
        'certificate_no',
        'start_date',
        'end_date',
    ];
}
