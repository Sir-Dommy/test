<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\PostingDetails;
use App\Http\Controllers\GettingDetails;
use App\Http\Controllers\LeaveController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('test', [ApiController::class, 'test']);
route::get('test2', [LeaveController::class, 'test']);
Route::post('register', [ApiController::class, 'register']);
Route::post('login', [ApiController::class, 'login']);

// comment to disable jwt authentication
Route::middleware('jwt.auth')->group(function(){
    Route::post('logout', [ApiController::class, 'logout']);

    Route::get('getLocation', [ApiController::class, 'getLocation']);
    Route::get('user', [ApiController::class, 'users']);

    // Routes to get and post Jobs
    Route::post('postJobs', [PostingDetails::class, 'postJobs']);
    Route::get('getJobs', [GettingDetails::class, 'getJobs']);
    Route::get('getUserApplications/{user_id}', [GettingDetails::class, 'getUserApplications']);
    Route::get('getJobApplications/{job_id}', [GettingDetails::class, 'getJobApplications']);
    Route::get('getAllApplications', [GettingDetails::class, 'getAllApplications']);
    Route::get('getUserDetails/{user_id}', [GettingDetails::class, 'getUserDetails']);

    // Routes to apply and check applications
    Route::post('applyJobs/{user_id}/{job_id}', [PostingDetails::class, 'applyJobs']);
    Route::get('getJobApplication/', [GettingDetails::class, 'getJobApplication']);

    //Route to post personal Details
    Route::post('personalDetails/{user_id}', [PostingDetails::class, 'personalDetails']);

    //Route to post Professional Qualifications
    Route::post('professionalQualification/{user_id}', [PostingDetails::class, 'professionalQualification']);

    //Route to post Professional Qualifications
    Route::post('postReferees/{user_id}', [PostingDetails::class, 'postReferees']);

    //Route to post Work Eperience
    Route::post('postExperience/{user_id}', [PostingDetails::class, 'postExperience']);

    //Route to post Professional Body
    Route::post('professionalBody/{user_id}', [PostingDetails::class, 'professionalBody']);

    //Route to post Academic Qualification
    Route::post('academicQualification/{user_id}', [PostingDetails::class, 'academicQualification']);

    //Route to post change job status
    Route::post('jobStatus/{user_id}/{job_id}/{status}', [PostingDetails::class, 'jobStatus']);




    //Delete a job application
    Route::delete('/deleteJobApplications/{user_id}/{job_id}', [GettingDetails::class, 'deleteJobApplications']);



















    // All these routes serve leave application module
    Route::get('/applyLeave', [LeaveController::class, 'applyLeave']);
    Route::get('/getLeaveApplications/{user_id}', [LeaveController::class, 'getLeaveApplications']);
    Route::get('/getMyLeaves/{user_id}', [LeaveController::class, 'getMyLeaves']);
    Route::get('/getLeaveReport/{user_id}/{leave_app_id}', [LeaveController::class, 'getLeaveReport']);
    Route::post('/submitDetails', [LeaveController::class, 'submitDetails']);
    Route::post('/createLeaveType/{user_id}', [LeaveController::class, 'createLeaveType']);
    Route::put('/updateLeaveType/{user_id}/{leave_id}', [LeaveController::class, 'updateLeaveType']);
    Route::put('/deactivateLeaveType/{user_id}/{leave_id}', [LeaveController::class, 'deactivateLeaveType']);
    Route::put('/activateLeaveType/{user_id}/{leave_id}', [LeaveController::class, 'activateLeaveType']);
    Route::get('/showLeaveTypes/{user_id}', [LeaveController::class, 'showLeaveTypes']);
    Route::post('/createLeaveApplication/{user_id}', [LeaveController::class, 'createLeaveApplication']);
    Route::put('/hodApproveReject', [LeaveController::class, 'hodApproveReject']);
    Route::put('/ammendHodApproval/{user_id}/{leave_app_id}', [LeaveController::class, 'ammendHodApproval']);
    Route::post('/psApproval/{user_id}/{leave_app_id}', [LeaveController::class, 'psApproval']);
    Route::put('/ammendPsApproval/{user_id}/{leave_app_id}', [LeaveController::class, 'ammendPsApproval']);
    Route::post('/createHrmdProfile/{user_id}/{leave_app_id}', [LeaveController::class, 'createHrmdProfile']);
    Route::post('/rejectHrmdProfile/{user_id}/{leave_app_id}', [LeaveController::class, 'rejectHrmdProfile']);
    Route::get('/checkApplicationDetails/{user_id}/{leave_app_id}', [LeaveController::class, 'checkApplicationDetails']);
    Route::get('/checkHodLevelDetails/{user_id}', [LeaveController::class, 'checkHodLevelDetails']); 
    Route::get('/checkHrmdProfile/{user_id}', [LeaveController::class, 'checkHrmdProfile']);
    Route::get('/checkHrmdLevelDetails/{user_id}', [LeaveController::class, 'checkHrmdLevelDetails']);
    Route::get('/checkPsProfile/{user_id}', [LeaveController::class, 'checkHrmdProfile']);
    Route::get('/populateHrmdDetails/{user_id}/{leave_app_id}', [LeaveController::class, 'populateHrmdDetails']);
    Route::get('/getUserLeaves/{user_id}', [LeaveController::class, 'getUserLeaves']);
    Route::get('/checkAvailableDays/{user_id}/{leave_type}', [LeaveController::class, 'checkAvailableDays']);
    Route::get('/listLeaves', [LeaveController::class, 'listLeaves']);















    // Admin routes
    Route::middleware('check.admin')->group(function(){
        Route::post('assignRole', [AdminController::class, 'assignRole']);
        Route::get('testAdmin', [AdminController::class, 'testAdmin']);
        Route::get('getRoles', [AdminController::class, 'getRoles']);
        Route::get('getUsers', [AdminController::class, 'getUsers']);
        Route::put('updateUser', [AdminController::class, 'updateUser']);
        Route::get('listDepartments', [AdminController::class, 'listDepartments']);
        Route::post('createDepartment', [AdminController::class, 'createDepartment']);
        Route::put('updateDepartment', [AdminController::class, 'updateDepartment']);
    });
        
// comment to disable jwt authentication
});



// File manipulation test routes
Route::post('upload', [FileController::class, 'upload']);









