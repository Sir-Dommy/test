<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Jobs;
use App\Models\Audit;
use App\Models\Job_applications;
use App\Models\Personal_detail;
use App\Models\Previous_job_details;
use App\Models\Professional_qualifications;
use App\Models\Referees;
use App\Models\Work_experience;
use App\Models\Professional_body;
use App\Models\Academic_qualification;

class PostingDetails extends Controller
{
    // Posting jobs added by Dominick on 13/2/2024
    public function postJobs(Request $request){
       if(Jobs::create([
            'job_title'=> $request->job_title,
            'ref_no'=> $request->ref_no,
            'emp_terms'=> $request->emp_terms,
            'positions'=> $request->positions,
            'deadline'=> $request->deadline,
            ])){
            Audit::auditLog(100001, "POST", "Added a Job");
            return response()->json(['job_id' => $request->ref_no,'job_title' => $request->job_title], 200);
        }
        else{
            return response()->json(['Error!!!' => "Error occured please try again"], 200);
        }
    }

    
    //Function to post personal details
    public function personalDetails(Request $request, $user_id){
        // Start the transaction
        DB::beginTransaction();
        try {
            Personal_detail::create([
            'id_no'=> $request->id_no,
            'external_id'=> $user_id,
            'salutation'=> $request->salutation,
            'name'=> $request->name,
            'date_of_birth'=> $request->date_of_birth,
            'gender'=> $request->gender,
            'ethnicity'=> $request->ethnicity,
            'pwd_status'=> $request->pwd_status,
            'county'=> $request->county,
            'constituency'=> $request->constituency,
            'sub_county'=> $request->sub_county,
            'ward'=> $request->ward,
            'postal_address'=> $request->postal_address,
            'postal_code'=> $request->postal_code,
            'postal_town'=> $request->postal_town,
            ]);
            
            Previous_job_details::create([
            'external_id'=>$user_id,
            'institution_category'=>$request->institution_category,
            'public_institution'=>$request->public_institution,
            'station'=>$request->station,
            'employment_number'=>$request->employment_number,
            'present_designation'=>$request->present_designation,
            'current_appointment_date'=>$request->current_appointment_date,
            'previous_effective_date'=>$request->previous_effective_date,
            'previous_designation'=>$request->previous_designation,
            'job_group'=>$request->job_group,
            'terms_of_service'=>$request->terms_of_service,
            ]);
            
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "POST", "Added personal details");
            return response()->json(['success' => 'User Created'], 200);
        }
        catch (\Exception $e){
            // Rollback the transaction if any error occurs
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 200);
            return response()->json(['Error!!!' => "Error occured please try again"], 200);
        }

    }
    
    //Function to Post Professional Qualification Details
    public function professionalQualification(Request $request, $user_id){
        try {
            Professional_qualifications::create([
            'external_id'=> $user_id,
            'institution_name'=> $request->institution_name,
            'course_name'=> $request->course_name,
            'certificate_no'=> $request->certificate_no,
            'start_date'=> $request->start_date,
            'end_date'=> $request->end_date,
            ]);
            Audit::auditLog($user_id, "POST", "Added professional qualifications");
            return response()->json(['success' => 'User Created'], 200);
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }

    }
    
    //Function post referees
    public function postReferees(Request $request, $user_id){
        // Start the transaction
        DB::beginTransaction();
        try {
            Referees::create([
            'external_id'=>$user_id,
            'position'=>$request->position,
            'full_name'=>$request->full_name,
            'mobile_no'=>$request->mobile_no,
            'email'=>$request->email,
            'period'=>$request->period,
            ]);
            
            Referees::create([
            'external_id'=>$user_id,
            'position'=>$request->position2,
            'full_name'=>$request->full_name2,
            'mobile_no'=>$request->mobile_no2,
            'email'=>$request->email2,
            'period'=>$request->period2,
            ]);
            
            // Commit the transaction if all operations are successful
            DB::commit();
            Audit::auditLog($user_id, "POST", "Added referees");
            return response()->json(['success' => 'Referee Added Successfully'], 200);
        }
        catch (\Exception $e){
            // Rollback the transaction if any error occurs
            DB::rollBack();
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }

    }
    
    //Function post referees
    public function postExperience(Request $request, $user_id){
        try {
            Work_experience::create([
            'external_id'=>$user_id,
            'position'=>$request->position,
            'job_group'=>$request->job_group,
            'mcda'=>$request->mcda,
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
            ]);
            Audit::auditLog($user_id, "POST", "Added work experience");
            return response()->json(['success' => 'Work Experience Added Successfully'], 200);
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }

    }
    
        //Function post Professional Body
    public function professionalBody(Request $request, $user_id){
        try {
            Professional_body::create([
            'external_id'=>$user_id,
            'professional_body'=>$request->professional_body,
            'membership_type'=>$request->membership_type,
            'certificate_no'=>$request->certificate_no,
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
            ]);
            Audit::auditLog($user_id, "POST", "Added professional body");
            return response()->json(['success' => 'Professional Body Added Successfully'], 200);
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }

    }
    
        //Function post Academic Qualifications
    public function academicQualification(Request $request, $user_id){
        try {
            Academic_qualification::create([
            'external_id'=>$user_id,
            'institution_name'=>$request->institution_name,
            'admission_no'=>$request->admission_no,
            'award'=>$request->award,
            'programme_name'=>$request->programme_name,
            'grade'=>$request->grade,
            'certificate_no'=>$request->certificate_no,
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
            'graduation_date'=>$request->graduation_date,
            ]);
            Audit::auditLog($user_id, "POST", "Added academic qualification");
            return response()->json(['success' => 'Accademic Qualification Added Successfully'], 200);
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }

    }
    
        //Function to change job status
    public function jobStatus($user_id, $job_id, $status){
        try {
            $jobs=Job_applications::where('user_id', $user_id, $status)
                ->where('job_id', $job_id)->get();
            if(count($jobs)>0){
                Job_applications::where('user_id', $user_id)
                    ->where('job_id', $job_id)
                    ->update(['status' => $status]);
                
                Audit::auditLog($user_id, $status, "Changed job status");

                return response()->json(['success' => 'Job Status Updated Successfully'], 200);
            }
            else{
                return response()->json(['Error!!!' => 'Job does not Exist!!!'], 200);
            }
            
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }

    }
        
    //Function to apply for a job should accespt both job id and user id as parameters on the url not request
    public function applyJobs($user_id, $job_id){
        try {
            Job_applications::create([
            'job_id'=> $job_id,
            'user_id'=> $user_id,
            ]);
            
            Audit::auditLog($user_id, $job_id, "Applied for a job");
            return response()->json(['success' => 'Job Aplied Successfully'], 200);
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }

    }
}







