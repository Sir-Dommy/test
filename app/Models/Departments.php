<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departments extends Model
{
    use HasFactory;

    public $fillable = [
        'department_name',
        'status',
    ];

    public function leaveApplicants(){
        return $this->belongsTo(Leave_applicants::class);
    }

    public static function getDepartmentName($department_id){
        $department = Departments::where('id', $department_id)->get();

        return $department[0]->department_name;
    }
}
