<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

// use App\Models\Holiday;

class Audit extends Model
{
    use HasFactory;
    protected $fillable = [
        'external_id',
        'name',
        'action',
        'created_at',
    ];
    
    public static function checkUser($user_id){
        $all = User::where('id', $user_id)
            ->get();
        if(count($all) > 0){
            return 1;
        }
        return 0;
    }
    
    public static function auditLog($user_id, $name, $action){
        try{
            Audit::create([
                'external_id'=> $user_id,
                'name'=> $name,
                'action'=> $action,
                'created_at'=> Carbon::now(),
            ]);
            
            // return response()->json("Lucy", 200);
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }

    }

    public static function calculateEndDate($start_date, $leave_days)
    {
        // Get start date from request
        $startDate = Carbon::parse($start_date);

        // Get list of holidays
        // $holidays = Holiday::pluck('date')->toArray();

        // Initialize leave days counter and loop until we reach 10 leave days
        $leaveDays = 0;
        $currentDate = $startDate->copy(); // Create a copy of start date
        while ($leaveDays < $leave_days) {
            // // Check if the current date is not a weekend and not a holiday
            // if (!$currentDate->isWeekend() && !in_array($currentDate->toDateString(), $holidays)) {
            //     $leaveDays++;
            // }
            
            if (!$currentDate->isWeekend()) {
                $leaveDays++;
            }
            // Move to the next day
            $currentDate->addDay();
        }

        // Subtract 1 day to get the last leave day
        $endDate = $currentDate->subDay();

        return $endDate->toDateString();
    }
    
    public static function checkPastLeave($user_id, $leave_type){
        
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        
        // If the current month is before June, select the previous year as the starting year
        $startingYear = ($currentMonth < 6) ? $currentYear - 2 : $currentYear - 1;
        
        // If the current month is after May, select the current year as the ending year
        $endingYear = ($currentMonth > 5) ? $currentYear : $currentYear - 1;
        
        // Define the start and end dates for the leave period
        $start_date = Carbon::createFromDate($startingYear, 6, 1)->format('Y-m-d'); // June 1st of starting year
        $end_date = Carbon::createFromDate($endingYear, 5, 31)->format('Y-m-d'); // May 31st of ending year


        // $leave_details = Leave_applications::where('id', $leave_app_id)->get();
        //     if(count($leave_details) < 1){
        //         return response()->json(['message' => 'not found'], 404);
        //     }
            
        //     $applicant_id = $leave_details[0]->external_id;
        //     $leave_type = $leave_details[0]->leave_type;
            
            $leave_days_taken_pre_year = Leave_applications::join('hrmd_profiles','hrmd_profiles.external_id', '=', 'leave_applications.id')
                ->where('leave_applications.external_id',$user_id)
                ->where('leave_applications.leave_type',$leave_type)
                ->whereBetween('leave_start_date', [$start_date, $end_date])
                ->sum('hrmd_profiles.num_of_days');
                
                
        return $leave_days_taken_pre_year;

    }
    
    
    public static function checkCurrentYearLeaves($user_id, $leave_type){
        
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        
        // If the current month is before June, select the previous year as the starting year
        $startingYear = ($currentMonth < 6) ? $currentYear - 1 : $currentYear - 1;
        
        // If the current month is after May, select the current year as the ending year
        $endingYear = ($currentMonth > 5) ? $currentYear : $currentYear;
        
        // Define the start and end dates for the leave period
        $start_date = Carbon::createFromDate($startingYear, 6, 1)->format('Y-m-d'); // June 1st of starting year
        $end_date = Carbon::createFromDate($endingYear, 5, 31)->format('Y-m-d'); // May 31st of ending year


        // $leave_details = Leave_applications::where('id', $leave_app_id)->get();
        //     if(count($leave_details) < 1){
        //         return response()->json(['message' => 'not found'], 404);
        //     }
            
        //     $applicant_id = $leave_details[0]->external_id;
        //     $leave_type = $leave_details[0]->leave_type;
            
            $this_year_leave_days = Leave_applications::join('hrmd_profiles','hrmd_profiles.external_id', '=', 'leave_applications.id')
                ->where('leave_applications.external_id',$user_id)
                ->where('leave_applications.leave_type',$leave_type)
                ->whereBetween('leave_start_date', [$start_date, $end_date])
                ->sum('hrmd_profiles.num_of_days');
                
                
        return $this_year_leave_days;

    }
    
    
    public static function leaveTypeDays($leave_app_id){
        
           $leave_details = Leave_applications::where('id', $leave_app_id)->get();    
           $leave_type = $leave_details[0]->leave_type;
           
           $num_of_days = Leave_types::where('id', $leave_type)
                        ->get('num_of_days');
                
        return $num_of_days[0]->num_of_days;

    }
    
    public static function getUserLeaves($user_id){
        
        $leave_types = Leave_types::all();
        $details = [];
        foreach($leave_types as $leave_type){
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;
            
            $past_year_leaves = Audit::checkPastLeave($user_id, $leave_type->id);
            
            $this_year_leaves = Audit::checkCurrentYearLeaves($user_id, $leave_type->id);
            
            $days_carried_over = Audit::checkCarriedOverDays($user_id, $leave_type->id);
            
            $available_days = Audit::checkAvailableDays($user_id, $leave_type->id);

            $days_you_can_apply_for = Audit::daysYouCanApplyFor($user_id, $leave_type->id);
            
            $details [] = [
                "leave_type_id" => $leave_type->id, 
                "leave_type" => $leave_type->name, 
                "leave_days_per_year" => $leave_type->num_of_days, 
                "past_year_leaves"=>$past_year_leaves, 
                "this_year_leaves"=>$this_year_leaves, 
                "days_carried_over"=>$days_carried_over, 
                "available_days"=>$available_days,
                "days_you_can_apply_for"=>$days_you_can_apply_for,
            ];           
            
        }
        
        
        return $details;
    }
    
    public static function checkCarriedOverDays($user_id, $leave_type){
        $past_year_leaves = Audit::checkPastLeave($user_id, $leave_type);
        
        $all = Leave_types::where('id', $leave_type)
            ->get();
            
        foreach($all as $data){
            if($data->can_carry_forward == 1){
                $leaves_not_taken = $data->num_of_days - $past_year_leaves;
                
                //if leave days not taken is greater than or equal to max carry over days, return max carry over days from database
                if($leaves_not_taken >= $data->max_carry_over_days){
                    return $data->max_carry_over_days;
                }
                
                // if leave days not taken is greater than 0 but less than max carry over days return these days
                elseif($leaves_not_taken > 0){
                    return $leaves_not_taken;
                }
                
                // handle any other conditions including negative days not taken
                else{
                    return 0;
                }
            }
        }

        return 0;
        
    }
    
    public static function checkAvailableDays($user_id, $leave_type){   
        $this_year_leaves = Audit::checkCurrentYearLeaves($user_id, $leave_type);
        
        $days_carried_over = Audit::checkCarriedOverDays($user_id, $leave_type);
        
        $all = Leave_types::where('id', $leave_type)
            ->get();
            
        $available_days = 0;
        foreach($all as $data){
            $available_days = ($data->num_of_days - $this_year_leaves) + $days_carried_over;
        }
        
        return  $available_days;
    }

    public static function daysYouCanApplyFor($user_id, $leave_type){
        // return Audit::checkAvailableDays($user_id, $leave_type);
        $all = Leave_types::where('id', $leave_type)->get();

        if(count($all) > 0){
            $available_days = Audit::checkAvailableDays($user_id, $leave_type);
            
            if($available_days >= $all[0]->max_days_per_application){
                return $all[0]->max_days_per_application;
            }

            else{
                return $available_days;
            }
        }

        return 0;
    }
    
    public function startDate(){
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        
        // If the current month is before June, select the previous year as the starting year
        $startingYear = ($currentMonth < 6) ? $currentYear - 1 : $currentYear - 1;
        
        
        // Define the start and end dates for the leave period
        $start_date = Carbon::createFromDate($startingYear, 6, 1)->format('Y-m-d'); // June 1st of starting year
        
        return $start_date;
    }
    
    public function endDate(){
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        // If the current month is after May, select the current year as the ending year
        $endingYear = ($currentMonth > 5) ? $currentYear : $currentYear;
        
        $end_date = Carbon::createFromDate($endingYear, 5, 31)->format('Y-m-d'); // May 31st of ending year
        
        return $end_date;
    }

    public static function adminList(){
        $leave_applications = Leave_applications::leftjoin('hod_profiles', 'leave_applications.id', '=', 'hod_profiles.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('leave_applicants', 'leave_applications.external_id', '=', 'leave_applicants.external_id')
            ->leftjoin('ps_profiles', 'leave_applications.id', '=', 'ps_profiles.external_id')
            ->leftjoin('hrmd_profiles', 'leave_applications.id', '=', 'hrmd_profiles.external_id')
            ->whereNull('hod_profiles.external_id')
            ->whereNull('ps_profiles.external_id')
            ->whereNull('hrmd_profiles.external_id')
            ->select(
                'leave_applications.id  AS id',
                'leave_applicants.name AS applicant_name',
                'leave_applicants.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                DB::raw('NULL as hod_name'),
                DB::raw('NULL as hod_signed_on'),
                DB::raw('NULL as ps_name'),
                DB::raw('NULL as ps_signed_on'),
                DB::raw('NULL as hrmd_name'),
                DB::raw('NULL as hrmd_signed_on'),
                DB::raw('NULL as leave_begins_on'),
                DB::raw('NULL as days_given'),
                DB::raw('NULL as return_date'),
            )
            ->limit(250);
            // ->get();
                

        $hod_profiles = Hod_profiles::query()
            ->join('leave_applications', 'hod_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants AS applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->leftjoin('ps_profiles', 'leave_applications.id', '=', 'ps_profiles.external_id')
            ->leftjoin('hrmd_profiles', 'leave_applications.id', '=', 'hrmd_profiles.external_id')
            ->whereNull('ps_profiles.external_id')
            ->whereNull('hrmd_profiles.external_id')
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date AS applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_signed_on',
                DB::raw('NULL as ps_name'),
                DB::raw('NULL as ps_signed_on'),
                DB::raw('NULL as hrmd_name'),
                DB::raw('NULL as hrmd_signed_on'),
                DB::raw('NULL as leave_begins_on'),
                DB::raw('NULL as days_given'),
                DB::raw('NULL as return_date'),
                )
            ->limit(250);
            // ->get();
        
        $ps_profiles = Ps_profiles::query()
            ->join('leave_applications', 'ps_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants AS applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_applicants AS ps', 'ps_profiles.approved_by', '=', 'ps.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('hod_profiles', 'leave_applications.id', '=', 'hod_profiles.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->leftjoin('hrmd_profiles', 'leave_applications.id', '=', 'hrmd_profiles.external_id')
            ->whereNull('hrmd_profiles.external_id')
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_signed_on',
                'ps.name AS ps_name',
                'ps_profiles.date AS ps_signed_on',
                DB::raw('NULL as hrmd_name'),
                DB::raw('NULL AS hrmd_signed_on'),
                DB::raw('NULL AS leave_begins_on'),
                DB::raw('NULL AS days_given'),
                DB::raw('NULL AS return_date'),
                )
            ->limit(250);
            // ->get();


        $hrmd_profiles = Hrmd_profiles::query()
            ->join('leave_applications', 'hrmd_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants as applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('hod_profiles', 'leave_applications.id', '=', 'hod_profiles.external_id')
            ->join('ps_profiles', 'leave_applications.id', '=', 'ps_profiles.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->join('leave_applicants AS hrmd', 'hrmd_profiles.approved_by', '=', 'hrmd.external_id')
            ->join('leave_applicants AS ps', 'ps_profiles.approved_by', '=', 'ps.external_id')
            
            
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_signed_on',
                'ps.name as ps_name',
                'ps_profiles.date as ps_signed_on',
                'hrmd.name as hrmd_name',
                'hrmd_profiles.date as hrmd_signed_on',
                'hrmd_profiles.leave_start_date AS leave_start_date',
                'hrmd_profiles.num_of_days AS days_given',
                'hrmd_profiles.to_resume_on AS return_date',
                )
            
            ->limit(250);
            // ->get();

        return $leave_applications->union($hod_profiles)->union($ps_profiles)->union($hrmd_profiles)->get();

    }

    public static function listHrmd(){
        $approved = Hrmd_profiles::query()
            ->join('leave_applications', 'hrmd_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants as applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('hod_profiles', 'leave_applications.id', '=', 'hod_profiles.external_id')
            ->join('ps_profiles', 'leave_applications.id', '=', 'ps_profiles.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->join('leave_applicants AS hrmd', 'hrmd_profiles.approved_by', '=', 'hrmd.external_id')
            // ->join('leave_applicants AS hrmd_rejected', 'hrmd_profiles.rejected_by', '=', 'hrmd_rejected.external_id')
            ->join('leave_applicants AS ps', 'ps_profiles.approved_by', '=', 'ps.external_id')
            ->whereNull('ps_profiles.rejected_by')
            
            
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_signed_on',
                'ps.name as ps_name',
                'ps_profiles.date as ps_signed_on',
                'hrmd.name as hrmd_name',
                'hrmd_profiles.date as hrmd_approved_on',
                DB::raw('NULL as hrmd_rejected_on'),
                'hrmd_profiles.leave_start_date AS leave_start_date',
                'hrmd_profiles.num_of_days AS days_given',
                'hrmd_profiles.to_resume_on AS return_date',
                )
                
            ->limit(250);
            // ->get();
        
        $rejected = Hrmd_profiles::query()
            ->join('leave_applications', 'hrmd_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants as applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('hod_profiles', 'leave_applications.id', '=', 'hod_profiles.external_id')
            ->join('ps_profiles', 'leave_applications.id', '=', 'ps_profiles.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->join('leave_applicants AS hrmd', 'hrmd_profiles.rejected_by', '=', 'hrmd.external_id')
            ->join('leave_applicants AS ps', 'ps_profiles.approved_by', '=', 'ps.external_id')
            ->whereNull('ps_profiles.rejected_by')
            
            
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_signed_on',
                'ps.name as ps_name',
                'ps_profiles.date as ps_signed_on',
                'hrmd.name as hrmd_name',
                DB::raw('NULL as hrmd_approved_on'),
                'hrmd_profiles.date as hrmd_rejected_on',
                'hrmd_profiles.leave_start_date AS leave_start_date',
                'hrmd_profiles.num_of_days AS days_given',
                'hrmd_profiles.to_resume_on AS return_date',
                )
            ->limit(250);
            // ->get();

        $ps_profiles = Ps_profiles::query()
            ->join('leave_applications', 'ps_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants AS applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_applicants AS ps', 'ps_profiles.approved_by', '=', 'ps.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('hod_profiles', 'leave_applications.id', '=', 'hod_profiles.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->leftjoin('hrmd_profiles', 'leave_applications.id', '=', 'hrmd_profiles.external_id')
            ->whereNull('ps_profiles.rejected_by')
            ->whereNull('hod_profiles.rejected_by')
            ->whereNull('hrmd_profiles.external_id')
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_signed_on',
                'ps.name AS ps_name',
                'ps_profiles.date AS ps_signed_on',
                DB::raw('NULL as hrmd_name'),
                DB::raw('NULL as hrmd_approved_on'),
                DB::raw('NULL as hrmd_rejected_on'),
                DB::raw('NULL AS leave_start_date'),
                DB::raw('NULL AS days_given'),
                DB::raw('NULL AS return_date'),
                )
            ->limit(250);
        
        return  $approved->union($rejected)->union($ps_profiles)->get();

    }
    

    public static function listPs(){
        $ps_approved = Ps_profiles::query()
            ->join('leave_applications', 'ps_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants AS applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_applicants AS ps', 'ps_profiles.approved_by', '=', 'ps.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('hod_profiles', 'leave_applications.id', '=', 'hod_profiles.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->whereNull('ps_profiles.rejected_by')
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_signed_on',
                'ps.name AS ps_name',
                'ps_profiles.date AS ps_approved_on',
                DB::raw('NULL AS ps_rejected_on'),
                )
            ->limit(250);
            // ->get();

        $ps_rejected = Ps_profiles::query()
            ->join('leave_applications', 'ps_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants AS applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_applicants AS ps', 'ps_profiles.approved_by', '=', 'ps.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('hod_profiles', 'leave_applications.id', '=', 'hod_profiles.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->whereNotNull('ps_profiles.rejected_by')
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_signed_on',
                'ps.name AS ps_name',
                DB::raw('NULL AS ps_approved_on'),
                'ps_profiles.date AS ps_rejected_on',
                )
            ->limit(250);

        
        $hod_profiles = Hod_profiles::query()
            ->join('leave_applications', 'hod_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants AS applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->leftjoin('ps_profiles', 'leave_applications.id', '=', 'ps_profiles.external_id')
            ->whereNull('ps_profiles.external_id')
            ->whereNull('hod_profiles.rejected_by')
 
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date AS applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_signed_on',
                DB::raw('NULL as ps_name'),
                DB::raw('NULL as ps_approved_on'),
                DB::raw('NULL as ps_rejected_on'),

                )
            ->limit(250);
            // ->get();
        
        return $ps_approved->union($ps_rejected)->union($hod_profiles)->get();
    }

    public static function listHod(){
        $hod_approved = Hod_profiles::query()
            ->join('leave_applications', 'hod_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants AS applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->whereNull('hod_profiles.rejected_by')
            ->where('hod.department', User::getDepartment())
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date AS applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                'hod_profiles.date AS hod_approved_on',
                DB::raw('NULL as hod_rejected_on'),
                )
            ->limit(250);
            // ->get();
        
        $hod_rejected = Hod_profiles::query()
            ->join('leave_applications', 'hod_profiles.external_id', '=', 'leave_applications.id')
            ->join('leave_applicants AS applicant', 'leave_applications.external_id', '=', 'applicant.external_id')
            ->join('leave_applicants AS hod', 'hod_profiles.approved_by', '=', 'hod.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->whereNotNull('hod_profiles.rejected_by')
            ->where('hod.department', User::getDepartment())
            ->select(
                'leave_applications.id AS id',
                'applicant.name AS applicant_name',
                'applicant.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date AS applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                'hod.name AS hod_name',
                DB::raw('NULL as hod_approved_on'),
                'hod_profiles.date AS hod_rejected_on',
                )
            ->limit(250);
            // ->get();
        
        $leave_applications = Leave_applications::leftjoin('hod_profiles', 'leave_applications.id', '=', 'hod_profiles.external_id')
            ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('leave_applicants', 'leave_applications.external_id', '=', 'leave_applicants.external_id')
            ->whereNull('hod_profiles.external_id')
            ->where('leave_applicants.department', User::getDepartment())
            ->select(
                'leave_applications.id  AS id',
                'leave_applicants.name AS applicant_name',
                'leave_applicants.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
                DB::raw('NULL as hod_name'),
                DB::raw('NULL as hod_approved_on'),
                DB::raw('NULL as hod_rejected_on'),
            )
            ->limit(250);
            // ->get();
        
        return $hod_approved->union($hod_rejected)->union($leave_applications)->get();
    }

    public static function listEmployeeLeaves(){
        $leave_applications = Leave_applications::join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
            ->join('leave_applicants', 'leave_applications.external_id', '=', 'leave_applicants.external_id')
            ->where('leave_applications.external_id', User::getUserId())
            ->select(
                'leave_applications.id  AS id',
                'leave_applicants.name AS applicant_name',
                'leave_applicants.department',
                'leave_types.name AS leave_type', 
                'leave_applications.num_of_days', 
                'leave_applications.date as applied_on', 
                'leave_applications.status', 
                'leave_applications.stage',
            )
            ->limit(250);
            // ->get();
        return $leave_applications->get();       
        
    }
    

}


























// if(($leave_type->can_carry_forward) == 1){
//                 // If the current month is before June, select the previous year as the starting year
//                 $startingYear = ($currentMonth < 6) ? $currentYear - 2 : $currentYear - 1;            
                
//             }
//             else{
//                 $startingYear = ($currentMonth < 6) ? $currentYear - 1 : $currentYear;
//             }
            
            
            
//             // Define the start and for the leave period
//             $start_date = Carbon::createFromDate($startingYear, 6, 1)->format('Y-m-d'); // June 1st of starting year
            
//             $all = Leave_applications::join('hrmd_profiles', 'leave_applications.id', '=', 'hrmd_profiles.external_id')
//             ->join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')
//             ->where('leave_applications.external_id', $user_id)
//             ->where('leave_applications.status', 'Approved')
//             ->where('hrmd_profiles.date', '>', $start_date)
//             ->where('leave_types.name', $leave_type->name)
//             ->select(DB::raw('SUM(leave_applications.num_of_days) as total_days ,leave_types.name'))
//             ->groupBy('leave_types.name')
//             ->get();
            
//             foreach($all as $data){
//                 $max_carry_over_days = $leave_type->max_carry_over_days;
//                 $remaining_days = 0;
//                 if(($leave_type->can_carry_forward) == 1){
//                     $remaining_days = (($leave_type->num_of_days) * 2) - $data->total_days;
//                 }
//                 else{
//                     $remaining_days = $leave_type->num_of_days - $data->total_days;
//                 }
                    
//                 // $detail->num_of_days_taken = $data->total_days;
//                 // $detail[] = ["num_of_days_taken" => $data->total_days,"leave_type" => $data->name, "leave_days_per_year" => $leave_type[0]->num_of_days];
                
//                 // (json_encode($detail));
//                 $details [] = ["num_of_days_taken" => $data->total_days,"num_of_days_remaining"=>$remaining_days,"leave_type" => $data->name, "leave_days_per_year" => $leave_type->num_of_days, "days_carried_over"=>$max_carry_over_days, "past_year_leaves"=>$past_year_leaves, "this_year_leaves"=>$this_year_leaves];
//             }
