<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Personal_detail;
use App\Models\Audit;
use App\Models\Departments;
use App\Models\User;
use App\Models\Leave_applicants;
use App\Models\Leave_types;
use App\Models\Hrmd_profiles;
use App\Models\Leave_applications;
use App\Models\Hod_profiles;
use App\Models\Holiday_types;
use App\Models\Ps_profiles;
use App\Models\Threat;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    public function checkLeave($leave_id){
        $id = Leave_types::where('id',$leave_id)
                        ->get();
        return count($id);
    }

    public function test(){
        // return Audit::checkAvailableDays(987, 4);
        return User::getUser()->getRoleNames();
        return Audit::getUserLeaves(167);
    }

    //apply for a leave
    public function applyLeave(Request $request){
        try{
            // $user_id = User::getUserId();
            $user = User::getUser();
            if(!(Audit::checkUser($user->id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }

            // $leave_types = Leave_types::all();
            $details = Audit::getUserLeaves($user->id);
            
            $applicant = Leave_applicants::join('users', 'leave_applicants.external_id', '=', 'users.id')
                ->where('leave_applicants.external_id', $user->id)//$user_id
                ->select('leave_applicants.external_id', 'leave_applicants.name', 'leave_applicants.gender', 'leave_applicants.department', 'leave_applicants.postal_address', 'leave_applicants.mobile_no', 'users.job_id as p_no')
                ->get();
            if(count($applicant) > 0){
                return response()->json([
                    "job_id" => $applicant[0]->p_no,
                    "name" => $applicant[0]->name,
                    "gender" => $applicant[0]->gender,
                    "department" => Departments::getDepartmentName($applicant[0]->department),
                    "postal_address" => $applicant[0]->postal_address,
                    "mobile_no" => $applicant[0]->mobile_no,
                    "leave_types" => $details,
                ], 200);
            }

            $all = Personal_detail::join('users', 'personal_details.external_id', '=', 'users.id')
                ->select('personal_details.name','personal_details.postal_address', 'personal_details.postal_town', 'personal_details.postal_code', 'personal_details.gender', 'users.job_id AS p_no')
                ->where('personal_details.external_id', $user->id)//$user_id
                ->get();

            
            // return $all;
            $p_no = $user->job_id;
            $name = count($all) ? $all[0]->name : null;
            $gender = count($all) ? $all[0]->gender : null;
            $postal_address = count($all)  ? $all[0]->name ." ".$all[0]->postal_town." ".$all[0]->postal_code : null;

            return response()->json([
                "p_no" => $p_no,
                "name" => $name,
                "gender" => $gender,
                "department" => null,
                "postal_address" => $postal_address,
                "mobile_no" => null,
                "leave_types" => $details,
            ], 200);

            if(count($all)>0){
                return response()->json($all, 200);
            }
            
            $all = User::where('id',$user->id)
                ->select('job_id AS p_no')
                ->get();
                
            if(count($all) > 0 ){
                return response()->json($all, 200);
            }
            
            else{
                return response()->json(["message" => 'user_id '.$user->id.' not found'], 404);
            }
                
            // Audit::auditLog($user_id, "GET", "Viewing All Jobs");   
        }
        
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }

    public function checkDaysYouCanApplyFor($user_id, $leave_type){
        return Audit::daysYouCanApplyFor($user_id, $leave_type);
    }

    public function rejectLeave($leave_app_id){
        Leave_applications::where('id', $leave_app_id)
            ->update([
                'status'=>2,
            ]);
    }
    
    public function createApplicant(Request $request){
        try{
            $request->validate([
                'user_id' => 'required|integer|min:1|exists:users,id|unique:leave_applicants,external_id',
                'name' => 'required|string|min:3|max:70',
                'gender' => 'required|string|min:3|max:10',
                'department' => 'required|integer|min:1|exists:departments,id',
                'postal_address' => 'required|string|min:3|max:200',
                'mobile_no' => 'required|string|min:9|max:14',
                'sign' => 'required',
            ]);

            // insert new applicant
            DB::beginTransaction();
            Leave_applicants::create([
                'external_id'=> $request->user_id,
                'name'=> $request->name,
                'gender'=>$request->gender,
                'department'=> $request->department,
                'postal_address'=> $request->postal_address,
                'mobile_no'=> $request->mobile_no,
                'sign'=> $request->sign,
                ]);

            DB::commit();

            Audit::auditLog($request->user_id, "CREATING", "Create profile as leave applicant");
            return response()->json([
                'user_id'=> $request->user_id,
                'name'=> $request->name,
                'gender'=>$request->gender,
                'department'=> $request->department,
                'postal_address'=> $request->postal_address,
                'mobile_no'=> $request->mobile_no,
                'message'=>"success",
            ], 200);
        }
        catch(ValidationException $e){
            return response()->json(['validation error' => $e->getMessage()], 422);
        }
        
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    //submit personal details
    public function submitDetails(Request $request){
        try{
            // $gender = Audit::checkGender($request->user_id);

            // $leave_type_gender = Leave_types::where('id', $request->leave_type)->get('gender_allowed');

            // return $leave_type_gender;

            // if($gender != $leave_type_gender && $leave_type_gender != "all"){
            //     return response()->json(['validation error' => "You cannot apply other genders leaves, apply leave that apply to your gender"], 422);
            // }

            $existing_leaves = Leave_applications::where('leave_type', $request->leave_type)
                ->where('external_id', $request->user_id)
                ->where('status', 0)
                ->get();

            if(count($existing_leaves) > 0){
                return response()->json(['message' => 'existing leave application exists pending approval cancel or wait'], 403);
            }
            $date = Carbon::now()->format('Y-m-d');

            DB::beginTransaction();
            // Leave_applicants::where
            $applicant = Leave_applicants::where('external_id', $request->user_id)->get();
            if(count($applicant)<1){
                $request->validate([
                    'user_id' => 'required|integer|min:1|exists:users,id|unique:leave_applicants,external_id',
                    'name' => 'required|string|min:3|max:70',
                    'gender' => 'required|string|min:3|max:10',
                    'department' => 'required|integer|min:1|exists:departments,id',
                    'postal_address' => 'required|string|min:3|max:200',
                    'mobile_no' => 'required|string|min:9|max:14|unique:leave_applicants,mobile_no',
                    'sign' => 'required',
                ]);
                
                Leave_applicants::create([
                    'external_id'=> $request->user_id,
                    'name'=> $request->name,
                    'gender'=>$request->gender,
                    'department'=> $request->department,
                    'postal_address'=> $request->postal_address,
                    'mobile_no'=> $request->mobile_no,
                    'sign'=> $request->sign,
                    ]);
            }
            
            $request->validate([
                'user_id' => 'required|integer|min:1',
                'designation' => 'required|string|min:3|max:50',
                'leave_type' => 'required|integer|min:1|exists:leave_types,id',
                'num_of_days' => 'required|integer|min:1|max:'.Audit::daysYouCanApplyFor($request->user_id, $request->leave_type),
                'leave_begins_on' => 'required|date|after_or_equal:today',
                'leave_address' => 'required|string|min:3|max:200',
                'salary_paid_to' => 'required',
                'account_no' => 'string|min:3|max:50',
            ]);

            $leave_application = Leave_applications::create([
                'external_id'=>$request->user_id,
                'designation'=>$request->designation,
                'leave_type'=>$request->leave_type,
                'num_of_days'=>$request->num_of_days,
                'leave_begins_on'=>$request->leave_begins_on,
                'leave_address'=>$request->leave_address,
                'salary_paid_to'=>$request->salary_paid_to,
                'account_no'=>$request->account_no,
                'date'=>$date,
                'signed'=>1,
                // 'stage'=>$request->stage,
                // 'status'=>$request->status,
                ]); 

            if(User::getUser()->hasRole('hod') || User::getUser()->hasRole('sa')){
                Hod_profiles::create([
                    'external_id'=> $leave_application->id,
                    'date'=> $date,
                    'approved_by'=>$request->user_id,
                    'signed'=>1,
                ]);

                Leave_applications::where('id', $leave_application->id)
                    ->update([
                        'stage'=>2,
                    ]);
            }
                
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($request->user_id, "POST", "Created Leave Application Profile and application");
            return response()->json([
                'user_id' => $request->user_id,
                'leave_app_id'=> $leave_application->id,
                'leave_type' => $request->leave_type, 
                'number_of_days' => $request->num_of_days, 
                'message'=>'success'], 
                200);
        }

        catch(ValidationException $e){
            DB::rollBack();
            return response()->json(['validation error' => $e->getMessage()], 422);
        }
        
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    //post Leave type
    public function createLeaveType(Request $request, $user_id){
        try{
            // if(!(Audit::checkUser($user_id))){
            //     return response()->json(['message' => 'action forbidden'], 403);
            // }

            $leave_type = Leave_types::where('is_main', 1)
                        ->get();
            
            // if leave type does not exist
            if(count($leave_type) > 0 && $request->is_main == 1){
                return response()->json(['validation error' => $leave_type[0]->name." is already set as main, do bot set this leave as main"], 422);
            }

            $request->validate([
                'name' => 'required|string|min:3|max:70|unique:leave_types,name',
                'num_of_days' => 'required|integer|min:1|max:365',
                'can_carry_forward' => 'required|boolean',
                'is_main' => 'required|boolean',
                'deduct_from_main' => 'required|boolean',
                'max_carry_over_days' => 'required|integer|min:0|max:365',
                'max_days_per_application' => 'required|integer|min:1|max:365',
                'gender_allowed' => 'required|string|min:3|max:10',
                'weekends_included' => 'required|boolean',
                'holidays_included' => 'required|boolean',
                'status' => 'required|boolean',
            ]);


            DB::beginTransaction();
            // Leave_applicants::where
            Leave_types::create([
                'added_by'=> $user_id,
                'name'=> $request->name,
                'num_of_days'=> $request->num_of_days,
                'can_carry_forward'=> $request->can_carry_forward,
                'is_main'=> $request->is_main,
                'deduct_from_main'=> $request->deduct_from_main,
                'max_carry_over_days'=> $request->max_carry_over_days,
                'max_days_per_application'=> $request->max_days_per_application,
                'gender_allowed'=> $request->gender_allowed,
                'weekends_included'=> $request->weekends_included,
                'holidays_included'=> $request->holidays_included,
                'status'=> $request->status,
                ]);
                
            $id = Leave_types::where('name',$request->name)
                        ->select('id')
                        ->get();
            $id = $id[0]['id'];
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "POST", "Created Leave Type : ".$request->name);
            return response()->json(['user_id' => $user_id,'leave_type'=>$request->name, 'leave_id'=>$id, 'message'=>'leave type created'], 200);
            
            
        }

        catch(ValidationException $e){
            return response()->json(['validation error' => $e->getMessage()], 422);
        }
        
        catch (\Exception $e){
            // Rollback the transaction if any error occurs
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    //update Leave type
    public function updateLeaveType(Request $request, $user_id, $leave_id){
        if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
        if($this->checkLeave($leave_id)<1){
            return response()->json(['error' => 'leave type not found'], 404);
        }
        try{
            $request->validate([
                'name' => 'required|string|min:3|max:70|unique:leave_types,name',
                'num_of_days' => 'required|integer|min:1|max:365',
                'can_carry_forward' => 'required|boolean',
                'is_main' => 'required|boolean',
                'deduct_from_main' => 'required|boolean',
                'max_carry_over_days' => 'required|integer|min:0|max:365',
                'max_days_per_application' => 'required|integer|min:1|max:365',
                'gender_allowed' => 'required|string|min:3|max:10',
                'weekends_included' => 'required|boolean',
                'holidays_included' => 'required|boolean',
                'status' => 'required|boolean',
            ]);
            
            DB::beginTransaction();
            Leave_types::where('id', $leave_id)
                ->update([
                    'added_by'=> $user_id,     
                    'name'=> $request->name,
                    'num_of_days'=> $request->num_of_days,
                    'can_carry_forward'=> $request->can_carry_forward,
                    'is_main'=> $request->is_main,
                    'deduct_from_main'=> $request->deduct_from_main,
                    'max_carry_over_days'=> $request->max_carry_over_days,
                    'max_days_per_application'=> $request->max_days_per_application,
                    'gender_allowed'=> $request->gender_allowed,
                    'weekends_included'=> $request->weekends_included,
                    'holidays_included'=> $request->holidays_included,
                    'status'=> $request->status,
                ]);
                
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "UPDATING", "Updated this Leave Type : ".$leave_id);
            return response()->json(['user_id' => $user_id, 'leave_id' => $leave_id, 'message'=>'updated'], 200);
        }

        catch(ValidationException $e){
            return response()->json(['validation error' => $e->getMessage()], 422);
        }
        
        catch (\Exception $e){
            // Rollback the transaction if any error occurs
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    //Deactivate Leave type
    public function deactivateLeaveType($user_id, $leave_id){
        if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
        if($this->checkLeave($leave_id)<1){
            return response()->json(['error' => 'leave type not found'], 404);
        }
        try{
            DB::beginTransaction();
            Leave_types::where('id', $leave_id)
                    ->update([
                        'status'=> 0,]);
                
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Deactivate ", "Deactivated this Leave Type : ".$leave_id);
            return response()->json(['user_id' => $user_id, 'leave_id' => $leave_id, 'message'=>'deactivated'], 200);
        }
        
        catch (\Exception $e){
            // Rollback the transaction if any error occurs
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    //Activate Leave type
    public function activateLeaveType($user_id, $leave_id){
        if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
        if($this->checkLeave($leave_id)<1){
            return response()->json(['error' => 'leave type not found'], 404);
        }
        try{
            DB::beginTransaction();
            Leave_types::where('id', $leave_id)
                    ->update([
                        'status'=> 1,]);
                
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Activated", " Activated this Leave Type : ".$leave_id);
            return response()->json(['user_id' => $user_id, 'leave_id' => $leave_id, 'message'=>'activated'], 200);
        }
        
        catch (\Exception $e){
            // Rollback the transaction if any error occurs
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    //Show all Leave types
    public function showLeaveTypes($user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Leave_types::select('id','name','num_of_days','weekends_included','holidays_included','status')
                ->get();
                
            // Commit the transaction if all operations are successful
            Audit::auditLog($user_id, "GET", "Checked All leave Types");
            return response()->json($all, 200);
        }
        
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    //function to add a leave appplication by user
    public function createLeaveApplication(Request $request, $user_id){
            
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            DB::beginTransaction();
            Leave_applications::create([
                'external_id'=>$user_id,
                'designation'=>$request->designation,
                'leave_type'=>$request->leave_type,
                'num_of_days'=>$request->num_of_days,
                'leave_begins_on'=>$request->leave_begins_on,
                'leave_address'=>$request->leave_address,
                'salary_paid_to'=>$request->salary_paid_to,
                'account_no'=>$request->account_no,
                'date'=>$request->date,
                'signed'=>$request->signed,
                // 'stage'=>$request->stage,
                // 'status'=>$request->status,
                ]);  
            
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Created", " Made a leave application");
            // $user_id = Audit::calculateEndDate($request->leave_begins_on, $request->num_of_days);
            return response()->json(['user_id' => $user_id, 'message'=>'leave applied'], 200);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
            
            
    }
    
    // function to my leaves for each user
    public function getMyLeaves(Request $request){
        try{
            $user_id = User::getUserId();
            
            $all = Leave_applications::join('leave_types', 'leave_applications.leave_type', '=', 'leave_types.id')->where('leave_applications.date', '>' ,Audit::startDate())
                ->where('leave_applications.external_id', $user_id)
                ->select('leave_applications.id','leave_types.name','leave_applications.leave_begins_on','leave_applications.num_of_days','leave_applications.stage','leave_applications.status')
                ->get(); 
            Audit::auditLog($user_id, "Read", " Checked user leaves ");
            // $user_id = Audit::calculateEndDate($request->leave_begins_on, $request->num_of_days);
            return response()->json($all, 200);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function aggregated report for each leave application
    public function getLeaveReport(Request $request, $leave_app_id){
        try{
            $user_id = User::getUserId();
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            $details = [];

            $hrmd = Hrmd_profiles::join('leave_applicants', 'hrmd_profiles.approved_by', '=', 'leave_applicants.external_id')
                    ->where('hrmd_profiles.external_id',$leave_app_id)
                    ->select('hrmd_profiles.num_of_days','hrmd_profiles.to_resume_on','hrmd_profiles.date','leave_applicants.name','leave_applicants.sign','leave_applicants.department')
                    ->get();

            if(count($hrmd) < 1){
                return response()->json(['error' => 'leave application not approved yet'], 422);
            }

            $leave_details = Leave_applications::where('id', $leave_app_id)
                ->get();
            
            
            $user = Leave_applicants::join('users', 'leave_applicants.external_id', '=', 'users.id')
                    ->where('external_id',$leave_details[0]->external_id)
                    ->select('leave_applicants.external_id','leave_applicants.name','leave_applicants.department','leave_applicants.postal_address','leave_applicants.mobile_no','leave_applicants.sign', 'users.job_id')
                    ->get();
                    
            $hod = Hod_profiles::join('leave_applicants', 'hod_profiles.approved_by', '=', 'leave_applicants.external_id')
                ->where('hod_profiles.external_id',$leave_app_id)
                ->select('leave_applicants.department', 'hod_profiles.date', 'hod_profiles.recommend_other', 'leave_applicants.sign', 'leave_applicants.name')
                ->get();
            
            $ps = Ps_profiles::join('leave_applicants', 'ps_profiles.approved_by', '=', 'leave_applicants.external_id')
                ->where('ps_profiles.external_id',$leave_app_id)
                ->select('ps_profiles.date', 'leave_applicants.name', 'leave_applicants.sign')
                ->get();
            
            
            $leave_days = Leave_types::where('id', $leave_details[0]->leave_type)->get();
                
            $last_leave = Hrmd_profiles::where('date', '<', $hrmd[0]->date)
                ->orderBy('date', 'desc') // Sort by date in descending order
                ->first();
            
            Audit::auditLog($user_id, "Read", " getting report for leave application id :  ". $leave_app_id);
            // $user_id = Audit::calculateEndDate($request->leave_begins_on, $request->num_of_days);
            return response()->json(
                [
                    'user_id' => $user[0]->external_id,
                    'user_name' => $user[0]->name,
                    'user_job_id' => $user[0]->job_id,
                    'user_mobile_no' => $user[0]->mobile_no,
                    'user_desgnation' => $leave_details[0]->designation,
                    'user_department' => Departments::getDepartmentName($user[0]->department),
                    'user_num_of_days' => $leave_details[0]->num_of_days,
                    'user_leave_begin_on' => $leave_details[0]->leave_begins_on,
                    'user_last_leave_taken_from' => isset($last_leave->leave_start_date) > 0 ? $last_leave->leave_start_date : "None" ,
                    'user_last_leave_taken_to' => isset($last_leave->to_resume_on) > 0 ? $last_leave->to_resume_on : "None",
                    'user_leave_address' => $leave_details[0]->leave_address,
                    'user_salary_paid_to' => $leave_details[0]->salary_paid_to,
                    'user_salary_account_no' => $leave_details[0]->account_no,
                    'user_date' => $leave_details[0]->date,
                    'user_sign' => $user[0]->sign,
                    
                    'hod_approved_by' => $hod[0]->name,
                    'hod_recommend_other' => $hod[0]->recommend_other,
                    'hod_station' => Departments::getDepartmentName($user[0]->department),
                    'hod_date' => $hod[0]->date,
                    'hod_sign' => $hod[0]->sign,
                    
                    'ps_approved_by' => $ps[0]->name,
                    'ps_date' => $ps[0]->date,
                    'ps_sign' => $ps[0]->sign,
                    
                    
                    'hrmd_leave_bf_from_prev_year' => Audit::checkCarriedOverDays($user_id, $leave_details[0]->leave_type),
                    'hrmd_leave_days_for_current_year' => $leave_days[0]->num_of_days,
                    'hrmd_total_days_due' => (Audit::checkCarriedOverDays($user_id, $leave_details[0]->leave_type) + $leave_days[0]->num_of_days)  ,
                    'hrmd_less_days_already_taken' => (Audit::checkAvailableDays($user_id, $leave_details[0]->leave_type) + $hrmd[0]->num_of_days),
                    'hrmd_less_this_application' => Audit::checkAvailableDays($user_id, $leave_details[0]->leave_type),
                    'hrmd_leave_balance_for_this_year' => Audit::checkAvailableDays($user_id, $leave_details[0]->leave_type),
                    'hrmd_to_resume_on' => $hrmd[0]->to_resume_on,
                    'hrmd_date' => $hrmd[0]->date,
                    'hrmd_sign' => $hrmd[0]->sign,
                    'hrmd_designation' => $hrmd[0]->designation,
                ], 200);
        }
        catch (\Exception $e){ 
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function to get all leaves
    public function getLeaveApplications(Request $request, $user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Leave_applications::join()->where('date', '>' ,Audit::startDate())
                ->get();
            Audit::auditLog($user_id, "Read", " Checked user leaves ");
            // $user_id = Audit::calculateEndDate($request->leave_begins_on, $request->num_of_days);
            return response()->json($all, 200);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function for approval by hod
    public function hodApproveReject(Request $request){
        try{

            $request->validate([
                'user_id'=> 'required|integer|min:1|exists:users,id',
                'leave_app_id'=> 'required|integer|min:1|exists:leave_applications,id',
                'approved' => 'boolean',
                'rejected' => 'boolean',
                'recommend_other' => 'required|boolean',
            ]);

            $ps = Ps_profiles::where('external_id', $request->leave_app_id)->get();
            if(count($ps) > 0){
                return response()->json(['Error!!!' => "You cannot modify leave which is already at ps action stage!!!"], 422);
            }

            $approved = 0;
            $rejected = 0;
            $date = Carbon::now()->format('Y-m-d');
            if(isset($request->approved) && $request->approved ==1 && $request->rejected ==0 ){
                $approved = 1;
            }
            elseif(isset($request->rejected) && $request->rejected ==1 && $request->approved ==0 ){
                $rejected = 1;
            }
            else{
                return response()->json(['validation error' => "either approved or rejected must be set and only one should be set to true"], 422);
            }

            $all = Hod_profiles::where('external_id',$request->leave_app_id)
                        ->get();
            if(count($all) < 1){
                if(!empty($approved)){
                    DB::beginTransaction();
                    Hod_profiles::create([
                        'external_id'=>$request->leave_app_id,
                        'recommend_other'=>$request->recommend_other,
                        'approved_by'=>$request->user_id,
                        'rejected_by'=>null,
                        'date'=>$date,
                        'signed'=>1,
                        ]);   
                    
                    Leave_applications::where('id',$request->leave_app_id)
                        ->update([
                            'stage'=> 2,
                            ]);
                            
                    // Commit the transaction if all operations are successful
                    DB::commit();
                }
                
                else if(!empty($rejected)){
                    DB::beginTransaction();
                    Hod_profiles::create([
                        'external_id'=>$request->leave_app_id,
                        'recommend_other'=>$request->recommend_other,
                        'approved_by'=>null,
                        'rejected_by'=>$request->user_id,
                        'date'=>$date,
                        'signed'=>1,
                        ]);   
                    
                    Leave_applications::where('id',$request->leave_app_id)
                        ->update([
                            'stage'=> 1,
                            'status'=>2,
                            ]);
                            
                    // Commit the transaction if all operations are successful
                    DB::commit();
                }
            }

            else if(count($all) > 0){
                if(!empty($approved)){
                    DB::beginTransaction();
                    Hod_profiles::where('external_id', $request->leave_app_id,)
                        ->update([
                            'recommend_other'=>$request->recommend_other,
                            'approved_by'=>$request->user_id,
                            'rejected_by'=>null,
                            'date'=>$date,
                            'signed'=>1,
                            ]);   
                    
                    Leave_applications::where('id',$request->leave_app_id)
                        ->update([
                            'stage'=> 2,
                            ]);
                            
                    // Commit the transaction if all operations are successful
                    DB::commit();
                }
                
                else if(!empty($rejected)){
                    DB::beginTransaction();
                    Hod_profiles::where('external_id', $request->leave_app_id,)
                        ->update([
                            'recommend_other'=>$request->recommend_other,
                            'approved_by'=>null,
                            'rejected_by'=>$request->user_id,
                            'date'=>$date,
                            'signed'=>1,
                            ]);   
                    
                    Leave_applications::where('id',$request->leave_app_id)
                        ->update([
                            'stage'=> 1,
                            'status'=>2,
                            ]);
                            
                    // Commit the transaction if all operations are successful
                    DB::commit();
                }
            }
            
            Audit::auditLog($request->user_id, "Signed", " Signed this leave application : ".$request->leave_app_id);
            // $user_id = Audit::calculateEndDate($request->leave_begins_on, $request->num_of_days);
            return response()->json([
                'user_id' => $request->user_id, 
                'leave_app_id'=>$request->leave_app_id,
                'approved'=>$approved, 
                'rejected'=>$rejected,
                'message'=>'success'
            ], 200);
        }
        catch(ValidationException $e){
            return response()->json(['validation error' => $e->getMessage()], 422);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function to revoke  approval by hod
    public function ammendHodApproval(Request $request, $user_id, $leave_app_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Hod_profiles::where('external_id',$leave_app_id)->get();
            
            if(count($all)<1){
                return response()->json(['error' => "leave not found"], 404);   
            }
            
            DB::beginTransaction();
            Hod_profiles::where('external_id',$leave_app_id)
                        ->update([
                            'approved_by'=>$user_id,
                            'external_id'=>$leave_app_id,
                            'recommend_other'=>$request->recommend_other,
                            'date'=>$request->date,
                            'signed'=>$request->signed,
                            ]); 
            if($request->signed == 0){
                Leave_applications::where('id',$leave_app_id)
                ->update([
                    'stage'=> 1,
                    ]);
            }
            else if($request->signed == 1){
                Leave_applications::where('id',$leave_app_id)
                ->update([
                    'stage'=> 2,
                    ]);
            }
            
                    
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Ammended", " Ammended this leave application : ".$leave_app_id." to signed = ".$request->signed);
            
            return response()->json(['user_id' => $user_id, 'leave_app_id'=>$leave_app_id, 'message'=>'updated'], 200);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function for approval by ps
    public function psApproveReject(Request $request){
        try{
            
            $request->validate([
                'user_id'=> 'required|integer|min:1|exists:users,id',
                'leave_app_id'=> 'required|integer|min:1|exists:leave_applications,id',
                'approved' => 'boolean',
                'rejected' => 'boolean',
            ]);

            $hods = Hod_profiles::where('external_id', $request->leave_app_id)
                ->where('rejected_by', null)
                ->get();
            if(count($hods) < 1){
                return response()->json(['Error!!!' => "leave application does not exist or not yet approved by HOD"], 422);
            }

            $hrmd = Hrmd_profiles::where('external_id', $request->leave_app_id)->get();
            if(count($hrmd) > 0){
                return response()->json(['Error!!!' => "You cannot modify leave which is already at hrmd action stage!!!"], 422);
            }

            $approved = null;
            $rejected = null;
            $date = Carbon::now()->format('Y-m-d');
            if(isset($request->approved) && $request->approved ==1 && $request->rejected ==0 ){
                $approved = $request->user_id;
            }
            else if(isset($request->rejected) && $request->rejected ==1 && $request->approved ==0 ){
                $rejected = $request->user_id;
            }
            else{
                return response()->json(['validation error' => "either approved or rejected must be set and only one should be set to true"], 422);
            }
            
            
            $all = Ps_profiles::where('external_id',$request->leave_app_id)
                        ->get();
            if(count($all)>0){
                
                DB::beginTransaction();
                Ps_profiles::where('external_id', $request->leave_app_id)
                    ->update([
                        'approved_by'=>$approved,
                        'rejected_by'=>$rejected,
                        'external_id'=>$request->leave_app_id,
                        'date'=>$date,
                        'signed'=>1,
                    ]);   
                
                if(isset($rejected)){
                    Leave_applications::where('id',$request->leave_app_id)
                    ->update([
                        'stage'=> 2,
                        'status'=> 2,
                        ]);
                }  
                elseif(isset($approved)){
                    Leave_applications::where('id',$request->leave_app_id)
                    ->update([
                        'stage'=> 3,
                        'status'=> 0,
                        ]);
                }      
                // Commit the transaction if all operations are successful
                DB::commit();
            }
            else{
                // return $rejected;
                DB::beginTransaction();
                Ps_profiles::create([
                    'external_id'=>$request->leave_app_id,
                    'approved_by'=>$approved,
                    'rejected_by'=>$rejected,
                    'date'=>$date,
                    'signed'=>1,
                    ]);   
                
                if(isset($rejected)){
                    Leave_applications::where('id',$request->leave_app_id)
                    ->update([
                        'stage'=> 2,
                        'status'=> 2,
                        ]);
                }  
                elseif(isset($approved)){
                    Leave_applications::where('id',$request->leave_app_id)
                    ->update([
                        'stage'=> 3,
                        'status'=> 0,
                        ]);
                }       
                // Commit the transaction if all operations are successful
                DB::commit();
            }
            
            Audit::auditLog($request->user_id, "Signed", " Signed this leave application : ".$request->leave_app_id);
            // $user_id = Audit::calculateEndDate($request->leave_begins_on, $request->num_of_days);
            return response()->json([
                'user_id' => $request->user_id, 
                'leave_app_id'=>$request->leave_app_id,
                'message'=>'success'
            ], 200);
        }
        catch(ValidationException $e){
            return response()->json(['validation error' => $e->getMessage()], 422);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function to ammend  approval by hod
    public function ammendPsApproval(Request $request, $user_id, $leave_app_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Ps_profiles::where('external_id',$leave_app_id)->get();
            
            if(count($all)<1){
                return response()->json(['error' => "leave not found"], 404);   
            }
            
            $hod = Hod_profiles::where('external_id',$leave_app_id)
                                ->where('signed',1)
                                ->get();
            
            if(count($hod)<1){
                return response()->json(['error' => "leave not yet signed by HOD"], 403);   
            }
            
            DB::beginTransaction();
            Ps_profiles::where('external_id',$leave_app_id)
                        ->update([
                            'approved_by'=>$user_id,
                            'external_id'=>$leave_app_id,
                            'date'=>$request->date,
                            'signed'=>$request->signed,
                            ]); 
            if($request->signed == 0){
                Leave_applications::where('id',$leave_app_id)
                ->update([
                    'stage'=> 2,
                    ]);
            }
            else if($request->signed == 1){
                Leave_applications::where('id',$leave_app_id)
                ->update([
                    'stage'=> 3,
                    ]);
            }
            
                    
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Ammended", " Ammended this leave application : ".$leave_app_id." to signed = ".$request->signed);
            
            return response()->json(['user_id' => $user_id, 'leave_app_id'=>$leave_app_id, 'message'=>'updated'], 200);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    
    //function to add a holiday
    public function addHoliday(Request $request, $user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            DB::beginTransaction();
            Holiday_types::create([
                'added_by'=> $user_id,
                'name'=> $request->name,
                'date'=> $request->num_of_days,
                'status'=> $request->status,
                ]);
                
            
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Created", " Created this holiday : ".$request->name);
            return response()->json(['user_id' => $user_id, 'holiday' => $request->name, 'message'=>'created'], 200);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    //function to update a holiday
    public function updateHoliday(Request $request, $user_id, $holiday_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            DB::beginTransaction();
            Holiday_types::where('id',$holiday_id)
                ->update([
                    'added_by'=> $user_id,
                    'name'=> $request->name,
                    'date'=> $request->num_of_days,
                    'status'=> $request->status,
                    ]);
                
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Update", " Updated this holiday : ".$request->name);
            return response()->json(['user_id' => $user_id, 'holiday' => $request->name, 'message'=>'Updated'], 200);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    
    //function to check hrmd profile
    public function checkHrmdProfile(Request $request, $application_id, $user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Hrmd_profiles::where('external_id',$application_id)
                ->get();
            $sum_of_days = Leave_types::where('can_carry_forward', 1)->sum('num_of_days');
            if(count($all)<1){
                 return response()->json(['message'=>'no previous leaves found'], 404);
            }

                
            // Audit::auditLog($user_id, "Checked", " Updated this holiday : ".$request->name);
            return response()->json([$sum_of_days], 200);
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }

     //function to create hrmd profile
     public function hrmdApproveReject(Request $request){
        try{            
            $ps = Ps_profiles::where('external_id', $request->leave_app_id)
                ->where('rejected_by', null)
                ->get();
            if(count($ps) < 1){
                return response()->json(['validation error' => "leave application does not exist or not yet approved by PS"], 422);
            }
            $leave_type = Leave_applications::where('id', $request->leave_app_id)->get();
            
            $num_of_days = $leave_type[0]->num_of_days;

            $leave_begins_on = $leave_type[0]->leave_begins_on;

            $leave_type = $leave_type[0]->leave_type;

            $days_you_can_apply_for = Audit::daysYouCanApplyFor($request->user_id, $leave_type);

            $request->validate([
                'user_id'=> 'required|integer|min:1|exists:users,id',
                'leave_app_id'=> 'required|integer|min:1|exists:leave_applications,id',
                'approved' => 'boolean',
                'rejected' => 'boolean',
                'leave_start_date' => 'date|after_or_equal:today',
                'num_of_days' => 'integer|min:1|max:'.$days_you_can_apply_for,
            ]);

            if(isset($request->leave_start_date) || isset($request->num_of_days)){
                if(!isset($request->leave_start_date) || !isset($request->num_of_days)){
                    return response()->json(['validatio error' => "if either leave_start_date or num_of_days is given, both must have values"], 422);
                }
                $num_of_days = $request->num_of_days;

                $leave_begins_on = $request->leave_start_date;
            }

            $approved = null;
            $rejected = null;
            $date = Carbon::now()->format('Y-m-d');
            if(isset($request->approved) && $request->approved ==1 && $request->rejected ==0 ){
                $approved = $request->user_id;
            }
            elseif(isset($request->rejected) && $request->rejected ==1 && $request->approved ==0 ){
                $rejected = $request->user_id;
            }
            else{
                return response()->json(['validation error' => "either approved or rejected must be set and only one should be set to true"], 422);
            }

            $all = Hrmd_profiles::where('external_id', $request->leave_app_id)->get();

            if(count($all) < 1){
                DB::beginTransaction();
                $to_resume_on = Audit::calculateEndDate($leave_begins_on, $num_of_days);
                Hrmd_profiles::create([
                    'external_id'=> $request->leave_app_id,
                    'leave_start_date'=> $leave_begins_on,
                    'num_of_days'=> $num_of_days,
                    'to_resume_on'=> $to_resume_on,
                    'date'=> $date,
                    'signed'=> 1,
                    'approved_by'=> $approved,
                    'rejected_by'=> $rejected,
                    ]);

                if(isset($rejected)){
                    Leave_applications::where('id',$request->leave_app_id)
                    ->update([
                        'stage'=> 3,
                        'status'=> 2,
                        ]);
                }  
                elseif(isset($approved)){
                    Leave_applications::where('id',$request->leave_app_id)
                    ->update([
                        'stage'=> 4,
                        'status'=> 1,
                        ]);
                }
                // Commit the transaction if all operations are successful
                DB::commit();
            }

            else{
                DB::beginTransaction();
                $to_resume_on = Audit::calculateEndDate($leave_begins_on, $num_of_days);
                Hrmd_profiles::where('external_id', $request->leave_app_id)
                    ->update([
                        'leave_start_date'=> $leave_begins_on,
                        'num_of_days'=> $num_of_days,
                        'to_resume_on'=> $to_resume_on,
                        'date'=> $date,
                        'signed'=> 1,
                        'approved_by'=> $approved,
                        'rejected_by'=> $rejected,
                    ]);
                
                if(isset($rejected)){
                    Leave_applications::where('id',$request->leave_app_id)
                    ->update([
                        'stage'=> 3,
                        'status'=> 2,
                        ]);
                }
                elseif(isset($approved)){
                    Leave_applications::where('id',$request->leave_app_id)
                    ->update([
                        'stage'=> 4,
                        'status'=> 1,
                        ]);
                }
                
                // Commit the transaction if all operations are successful
                DB::commit();
            }
            
            Audit::auditLog($request->user_id, "Approved", " Approved this Application : ".$request->leave_app_id);
            return response()->json([
                'user_id' => $request->user_id, 
                'leave_app_id' => $request->leave_app_id, 
                'message'=>'success'
            ], 200);
        }
        catch(ValidationException $e){
            return response()->json(['validation error' => $e->getMessage()], 422);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    
    //function to reject application by hrmd
    public function rejectHrmdProfile(Request $request, $user_id, $leave_app_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Leave_applications::where('id',$leave_app_id)->get();
            
            if(count($all)<1){
                return response()->json(['error' => "leave application not found"], 404);   
            }
            $hod = Hod_profiles::where('external_id',$leave_app_id)
                                ->where('signed',1)
                                ->get();
            
            if(count($hod)<1){
                return response()->json(['error' => "leave not yet signed by HOD"], 403);   
            }
            
            $to_resume_on = Audit::calculateEndDate($request->leave_start_date, $request->num_of_days);
            DB::beginTransaction();
            Hrmd_profiles::create([
                    'external_id'=> $leave_app_id,
                    'leave_start_date'=> $request->leave_start_date,
                    'num_of_days'=> $request->num_of_days,
                    'to_resume_on'=> $to_resume_on,
                    'date'=> $request->date,
                    'signed'=> 1,
                    'signed_by'=> $user_id,
                    ]);
                
            Leave_applications::where('id',$leave_app_id)
                ->update([
                    'stage'=> 7,
                    ]);
                    
            Leave_applications::where('id',$leave_app_id)
                ->update([
                    'status'=> 'Rejected',
                    ]);
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Rejected", " Rejected this Application : ".$leave_app_id);
            return response()->json(['user_id' => $user_id, 'leave_app_id' => $leave_app_id, 'message'=>'rejected'], 200);
        }
        catch (\Exception $e){
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }

    public function listLeaves(){
        try{
            // return response()->json(count(Audit::adminList()), 200);
            //  check admin leaves  for admin or suoeradmin 
            if(User::getUser()->hasRole('admin')){    
                return response()->json(Audit::adminList(), 200);
            }

            if(User::getUser()->hasRole('hrmd')){
                
                return response()->json(Audit::listHrmd(), 200);
            }

            if(User::getUser()->hasRole('ps')){ 
                return response()->json(Audit::listPs(), 200);
            }

            if(User::getUser()->hasRole('hod')){
                return response()->json(Audit::listHod(), 200);
            }

            if(User::getUser()->hasRole('employee')){
                // return response()->json(User::getDepartment(), 200);
                return response()->json(Audit::listEmployeeLeaves(), 200);
            }
            else{
                Threat::create([
                    'done_by'=> User::getUserId(),
                    'threat'=>"Tried to access leave lists",
                ]);
            }
            // return response()->json($all, 200);
        }
        catch(\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
        
    }
    
    
    // function to check leave application details can be accessed by applicant, hod,ps and hrmd
    public function checkApplicationDetails($user_id, $leave_app_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Leave_applications::where('id', $leave_app_id)->get();
            if(count($all)<1){
                return response()->json(['error' => "leave application not found"], 404);   
            }
            
            Audit::auditLog($user_id, "Read", " Checked this Application : ".$leave_app_id);
            return response()->json($all, 200);
        }
        catch(\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function to check leave application details at hod
    public function checkHodLevelDetails($user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Hod_profiles::where('approved_by', $user_id)->get();
            if(count($all)<1){
                return response()->json(['error' => "leave application not found"], 404);   
            }
            
            Audit::auditLog($user_id, "Read", " Checked Applications");
            return response()->json($all, 200);
        }
        catch(\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function to check leave application details at ps level
    public function checkPsLevelDetails($user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Ps_profiles::where('signed_by', $user_id)->get();
            if(count($all)<1){
                return response()->json(['error' => "leave application not found"], 404);   
            }
            
            Audit::auditLog($user_id, "Read", " Checked this Application");
            return response()->json($all, 200);
        }
        catch(\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function to check leave application details at hrmd level
    public function checkHrmdLevelDetails($user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Hrmd_profiles::where('signed_by', $user_id)->get();
            if(count($all)<1){
                return response()->json(['error' => "leave application not found"], 404);   
            }
            
            Audit::auditLog($user_id, "Read", " Checked this Application");
            return response()->json($all, 200);
        }
        catch(\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // function to populate HRMD details
    public function populateHrmdDetails($user_id, $leave_app_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            if(isset(Audit::checkPastLeave($user_id, $leave_app_id)->headers)){
                return response()->json(['message' => 'not found'], 404);
            }
            $last_years_leaves = Audit::checkPastLeave($user_id, $leave_app_id);
            $current_year_leaves = Audit::checkCurrentYearLeaves($user_id, $leave_app_id);
            $num_of_days_for_leave_type = Audit::leaveTypeDays($leave_app_id);
            
            $leave_bf_pre_year = $num_of_days_for_leave_type - $last_years_leaves;
            
            $leave_days_applied = Leave_applications::where('id',$leave_app_id)
                ->get('num_of_days');
                
            $leave_days_applied = $leave_days_applied[0]->num_of_days;
            
            Audit::auditLog($user_id, "Read", " Checked this Application: ".$leave_app_id);
            $response = [
                "leave_bf_from_pre_year" => $leave_bf_pre_year,
                "leave_days_for_current_year" => $num_of_days_for_leave_type,
                "days_already_taken" => $current_year_leaves,
                "days_applied" => $leave_days_applied
                ];
            return response()->json($response, 200);
        }
        catch(\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    // Function to get user leaves
    public function getUserLeaves($user_id){
       try{
           if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Leave_applicants::where('external_id', $user_id)->get();
            if(count($all)<1){
                return response()->json(['error' => "leave applicant not found"], 404);   
            }
            
            $leaves = Audit::getUserLeaves($user_id);
            
            Audit::auditLog($user_id, "Read", " Checked this user leaves");
            return response()->json($leaves, 200);
        }
        catch(\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        } 
    }
    
    // Check available number of leaves
    public function checkAvailableDays($user_id, $leave_type){
       try{
           if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Leave_applicants::where('external_id', $user_id)->get();
            if(count($all)<1){
                return response()->json(['error' => "leave applicant not found"], 404);   
            }
            
            $leaves = Leave_types::where('id', $leave_type)
                ->get();
            if(count($leaves)<1){
                return response()->json(['error' => "leave type not found"], 404);   
            }
            
            $all = Leave_applications::where('external_id', $user_id)
            ->where('leave_type', $leave_type)
            ->where('status', 'Approved')
            ->orderBy('date', 'desc')
            ->first();

            if(!isset($all)){
                return response()->json(['error' => "No previous leaves found"], 404); 
            }
            
            $leave_app_id = $all->id;
            
            $past_leaves = Audit::checkPastLeave($user_id, $leave_type);
            
            $current_year_leaves =Audit::checkCurrentYearLeaves($user_id, $leave_type);
            
            $leave_days_per_year = Audit::leaveTypeDays($user_id, $leave_type);
        
            
            // $leaves = Audit::checkAvailableDays($user_id, $leave_type);
            
            Audit::auditLog($user_id, "Read", " Checked this user leaves");
            return response()->json(["past_year_leaves"=>$past_leaves, "current_year_leaves" => $current_year_leaves, "leave_days_per_year" => $leave_days_per_year, "leave_type_id" => $leave_type, "leave_type" => $leaves[0]->name], 200);
        }
        catch(\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        } 
    }
    
       
}
