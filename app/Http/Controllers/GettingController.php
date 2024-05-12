<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jobs;
use App\Models\Job_applications;
use App\Models\Personal_detail;
use App\Models\Previous_job_details;
use App\Models\Professional_qualifications;
use App\Models\Referees;
use App\Models\Work_experience;
use App\Models\Professional_body;
use App\Models\Academic_qualification;
use App\Models\User;
use App\Models\Audit;

class GettingDetails extends Controller
{
    //get jobs functions
    public function getJobs(Request $request){
        $all = Jobs::all();
        // Audit::auditLog($user_id, "GET", "Viewing All Jobs");
        return response()->json($all, 200);
    }
    
    //get jobs functions for a single user
    public function getUserApplications($user_id){
        try{
            $all = Job_applications::join('jobs', 'job_applications.job_id', '=', 'jobs.id')
                ->select('job_applications.status', 'jobs.job_title', 'jobs.ref_no', 'jobs.deadline', 'jobs.emp_terms')
                ->where('job_applications.user_id',$user_id)
                ->get();
                
            if(count($all)>0){
                Audit::auditLog($user_id, "GET", "Viewing user job application");
               return response()->json($all, 200); 
            }
            else{
                return response()->json(['Error!!!' => 'No Job Applications Found!!!'], 200);
            }

        }
        catch (\Exception $e){
            
        }
        
    }
    
    //get jobs application for a specific job
    public function getJobApplications($job_id){
        try{
            $all = Job_applications::join('jobs', 'job_applications.job_id', '=', 'jobs.id')
            ->join('personal_details', 'job_applications.user_id', '=', 'personal_details.external_id')
            ->select('personal_details.name','job_applications.status', 'jobs.job_title', 'jobs.ref_no', 'jobs.deadline', 'jobs.emp_terms')
            ->where('job_applications.job_id', $job_id)
            ->get();

                
            if(count($all)>0){
                Audit::auditLog(10001, $job_id, "Viewed a job application");
                return response()->json($all, 200); 
            }
            else{
                return response()->json(['Error!!!' => 'No Job Applications Found!!!'], 200);
            }

        }
        catch (\Exception $e){
            
        }
        
    }
    
    //get all job applications
    public function getAllApplications(){
        try{
            $all = Job_applications::join('jobs', 'job_applications.job_id', '=', 'jobs.id')
            ->join('personal_details', 'job_applications.user_id', '=', 'personal_details.external_id')
            ->select('personal_details.name','job_applications.user_id','job_applications.job_id','job_applications.status', 'jobs.job_title', 'jobs.ref_no', 'jobs.deadline', 'jobs.emp_terms')
            ->get();

                
            if(count($all)>0){
                Audit::auditLog(10001, "GET", "Viewed all job applications");
                return response()->json($all, 200); 
            }
            else{
                return response()->json(['Error!!!' => 'No Job Applications Found!!!'], 200);
            }

        }
        catch (\Exception $e){
            
        }
        
    }
     
    public function getUserDetails($user_id){
        try{
            $personal_details=Personal_detail::where('external_id',$user_id)
                                ->select('personal_details.salutation','personal_details.name','personal_details.date_of_birth','personal_details.gender','personal_details.ethnicity','personal_details.pwd_status','personal_details.county','personal_details.constituency','personal_details.sub_county','personal_details.ward')
                                ->get();
            $academic_qualification=Academic_qualification::where('external_id',$user_id)
                                ->select('academic_qualifications.institution_name','academic_qualifications.admission_no','academic_qualifications.award','academic_qualifications.programme_name','academic_qualifications.Grade','academic_qualifications.certificate_no','academic_qualifications.start_date','academic_qualifications.end_date','academic_qualifications.graduation_date','academic_qualifications.graduation_date')
                                ->get();
            $previous_job_details=Previous_job_details::where('external_id',$user_id)
                                ->select('previous_job_details.institution_category','previous_job_details.public_institution','previous_job_details.station','previous_job_details.employment_number','previous_job_details.present_designation','previous_job_details.current_appointment_date','previous_job_details.previous_effective_date','previous_job_details.previous_designation','previous_job_details.job_group','previous_job_details.terms_of_service')
                                ->get();
            $professional_body=Professional_body::where('external_id',$user_id)
                                ->select('professional_bodies.professional_body','professional_bodies.membership_type','professional_bodies.certificate_no','professional_bodies.start_date','professional_bodies.end_date')
                                ->get();
            $professional_qualifications=Professional_qualifications::where('external_id',$user_id)
                                ->select('professional_qualification.institution_name','professional_qualification.course_name','professional_qualification.certificate_no','professional_qualification.start_date','professional_qualification.end_date')
                                ->get();
            $referees=Referees::where('external_id',$user_id)
                                ->select('referees.full_name','referees.position','referees.email','referees.mobile_no','referees.period')
                                ->get();
            $work_experience=Work_experience::where('external_id',$user_id)
                                ->select('work_experiences.position','work_experiences.job_group','work_experiences.mcda','work_experiences.start_date','work_experiences.end_date')
                                ->get();

            Audit::auditLog(10001, $user_id, "Viewed a user profile");
            return response()->json(["personal_details"=>$personal_details, "academic_qualification"=>$academic_qualification, "previous_job_details"=>$previous_job_details, "professional_body"=>$professional_body, "professional_qualifications"=>$professional_qualifications, "referees"=>$referees, "work_experience"=>$work_experience], 200);
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }
    }
    
    //function to delete a job application
    public function deleteJobApplications($user_id, $job_id){
        try{
            $all = Job_applications::where('user_id', $user_id)
            ->where('job_id', $job_id)
            ->get();
            if(count($all) > 0){
                Job_applications::where('user_id', $user_id)
                    ->where('job_id', $job_id)
                    ->delete();
            
                return response()->json(['job_deleted' => $job_id], 200);
            }
            else{
                return response()->json(['Count' => count($all)], 200);
            }
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 200);
        }
            
    }
    
}
