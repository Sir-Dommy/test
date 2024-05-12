<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Personal_detail;
use App\Models\Audit;
use App\Models\User;
use App\Models\Leave_applicants;
use App\Models\Leave_types;
use App\Models\Hrmd_profiles;
use App\Models\Leave_applications;
use App\Models\Hod_profiles;
use App\Models\Holiday_types;
use App\Models\Ps_profiles;

class LeaveController extends Controller
{
    public function checkLeave($leave_id){
        $id = Leave_types::where('id',$leave_id)
                        ->get();
        return count($id);
    }
    //apply for a leave
    public function applyLeave(Request $request, $user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            $all = Leave_applicants::where('external_id', $user_id)
                ->select('id', 'external_id', 'name', 'department', 'postal_address', 'mobile_no')
                ->get();
            if(count($all) > 0){
                return response()->json($all, 200);
            }
            $all = Personal_detail::join('users', 'personal_details.external_id', '=', 'users.id')
                ->select('personal_details.name','personal_details.postal_address', 'personal_details.postal_town', 'personal_details.postal_code', 'users.job_id AS p_no')
                ->where('personal_details.external_id', $user_id)
                ->get();
            // $all = Personal_detail::where('external_id','!=', $request->user_id)
            // ->select('name')
            // ->get();
            if(count($all)>0){
                return response()->json($all, 200);
            }
            
            $all = User::where('id',$user_id)
                ->select('job_id AS p_no')
                ->get();
                
            if(count($all) > 0 ){
                return response()->json($all, 200);
            }
            
            else{
                return response()->json(["message" => 'user_id '.$user_id.' not found'], 404);
            }
                
            // Audit::auditLog($user_id, "GET", "Viewing All Jobs");   
        }
        
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    //submit personal details
    public function submitDetails(Request $request, $user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            DB::beginTransaction();
            // Leave_applicants::where
            Leave_applicants::create([
                'external_id'=> $user_id,
                'name'=> $request->name,
                'department'=> $request->department,
                'postal_address'=> $request->postal_address,
                'mobile_no'=> $request->mobile_no,
                'sign'=> $request->sign,
                ]);
                
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "POST", "Created Leave Application Profile");
            return response()->json(['user_id' => $user_id, 'message'=>'success'], 200);
        }
        
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
    }
    
    //post Leave type
    public function createLeaveType(Request $request, $user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $id = Leave_types::where('name',$request->name)
                        ->get();
            // if leave type does not exist
            if(count($id)<1){
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
            
            // else give an error leave already exists
            else{
                return response()->json(['error' => 'leave already exist!!!', 'leave_name'=>$request->name], 409);
            }
            
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
            
            DB::beginTransaction();
            Leave_types::where('id', $leave_id)
                    ->update(['added_by'=> $user_id,
                        'name'=> $request->name,
                        'num_of_days'=> $request->num_of_days,
                        'can_carry_forward'=> $request->can_carry_forward,
                        'is_main'=> $request->is_main,
                        'deduct_from_main'=> $request->deduct_from_main,
                        'max_carry_over_days'=> $request->max_carry_over_days,
                        'max_days_per_application'=> $request->max_days_per_application,
                        'weekends_included'=> $request->weekends_included,
                        'holidays_included'=> $request->holidays_included,
                        'status'=> $request->status,]);
                
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "UPDATE", "Updated this Leave Type : ".$leave_id);
            return response()->json(['user_id' => $user_id, 'leave_id' => $leave_id, 'message'=>'updated'], 200);
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
    public function getMyLeaves(Request $request, $user_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
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
    public function getLeaveReport(Request $request, $user_id, $leave_app_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            $details = [];
            
            $user = Leave_applicants::join('users', 'leave_applicants.external_id', '=', 'users.id')
                    ->where('external_id',$user_id)
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
                
            $leave_details = Leave_applications::where('id', $leave_app_id)
                ->get();
                
            $leave_days = Leave_types::where('id', $leave_details[0]->leave_type)->get();
                
            $hrmd = Hrmd_profiles::join('leave_applicants', 'hrmd_profiles.signed_by', '=', 'leave_applicants.external_id')
                    ->where('hrmd_profiles.external_id',$leave_app_id)
                    ->select('hrmd_profiles.num_of_days','hrmd_profiles.to_resume_on','hrmd_profiles.date','leave_applicants.name','leave_applicants.sign','leave_applicants.department')
                    ->get();
            
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
                    'user_desgnation' => $leave_details[0]->designation,
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
                    'hod_station' => $user[0]->department,
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
    public function hodApproval(Request $request, $user_id, $leave_app_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Leave_applications::where('id',$leave_app_id)->get();
            
            if(count($all)<1){
                return response()->json(['error' => "leave not found"], 404);   
            }
            $all = Hod_profiles::where('external_id',$leave_app_id)
                        ->where('approved_by',$user_id)
                        ->get();
            if(count($all)>0){
                return response()->json(['error'=>'leave already signed'], 400);
            }
            DB::beginTransaction();
            Hod_profiles::create([
                'approved_by'=>$user_id,
                'external_id'=>$leave_app_id,
                'recommend_other'=>$request->recommend_other,
                'date'=>$request->date,
                'signed'=>1,
                ]);   
            
            Leave_applications::where('id',$leave_app_id)
                ->update([
                    'stage'=> 2,
                    ]);
                    
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Signed", " Signed this leave application : ".$leave_app_id);
            // $user_id = Audit::calculateEndDate($request->leave_begins_on, $request->num_of_days);
            return response()->json(['user_id' => $user_id, 'leave_app_id'=>$leave_app_id, 'message'=>'signed'], 200);
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
    public function psApproval(Request $request, $user_id, $leave_app_id){
        try{
            if(!(Audit::checkUser($user_id))){
                return response()->json(['message' => 'action forbidden'], 403);
            }
            
            $all = Leave_applications::where('id',$leave_app_id)->get();
            
            if(count($all)<1){
                return response()->json(['error' => "leave not found"], 404);   
            }
            
            $hod = Hod_profiles::where('external_id',$leave_app_id)
                                ->where('signed',1)
                                ->get();
            
            if(count($hod)<1){
                return response()->json(['error' => "leave not yet signed by HOD"], 403);   
            }
            
            $all = Ps_profiles::where('external_id',$leave_app_id)
                        ->where('approved_by',$user_id)
                        ->get();
            if(count($all)>0){
                return response()->json(['error'=>'leave already signed'], 400);
            }
            DB::beginTransaction();
            Ps_profiles::create([
                'approved_by'=>$user_id,
                'external_id'=>$leave_app_id,
                'date'=>$request->date,
                'signed'=>1,
                ]);   
            
            Leave_applications::where('id',$leave_app_id)
                ->update([
                    'stage'=> 3,
                    ]);
                    
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Signed", " Signed this leave application : ".$leave_app_id);
            // $user_id = Audit::calculateEndDate($request->leave_begins_on, $request->num_of_days);
            return response()->json(['user_id' => $user_id, 'leave_app_id'=>$leave_app_id, 'message'=>'signed'], 200);
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
    public function createHrmdProfile(Request $request, $user_id, $leave_app_id){
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
            
            $signed = Hrmd_profiles::where('external_id',$leave_app_id)
                                    ->where('signed',1)
                                    ->get();
            if(count($all)>0){
                return response()->json(['error' => "leave application already appproved"], 422);   
            }
            
            DB::beginTransaction();
            $to_resume_on = Audit::calculateEndDate($request->leave_start_date, $request->num_of_days);
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
                    'stage'=> 4,
                    ]);
                    
            Leave_applications::where('id',$leave_app_id)
                ->update([
                    'status'=> "Approved",
                    ]);
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "Approved", " Approved this Application : ".$leave_app_id);
            return response()->json(['user_id' => $user_id, 'leave_app_id' => $leave_app_id, 'message'=>'approved'], 200);
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
