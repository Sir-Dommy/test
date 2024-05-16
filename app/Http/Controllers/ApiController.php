<?php

namespace App\Http\Controllers;

use App\Models\Migrations;
use App\Models\Personal_detail;
use App\Models\User;
use App\Models\Audit;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ApiController extends Controller
{
    //
    public function test(){
        $details = Personal_detail::all();
        return response()->json($details, 200);
        return response()->json(['token' => $details], 200);
    }
    public function register(Request $request){
        try {
            
            $request->validate([
                'job_id' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:3',
            ]);

            DB::beginTransaction();
            $user=User::create([
            'email'=> $request->email,
            'job_id'=> $request->job_id,
            'password'=>bcrypt($request->password),
            ]);

            $user->assignRole('employee');
            $token = Auth::login($user);
            $roles = $user->getRoleNames()[0];
            DB::commit();
            Audit::auditLog($request->job_id, "POST", "Registering");
            return response()->json([
                'user_id'=>$user->id,
                'roles'=>$roles,
                'authorzation'=> [
                    'token' => $token,
                    'type' =>'bearer',
                ],
                'message'=>'Registered Successfuly'], 200);


        } 
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['failed'=>$e->getMessage()], 500);
        }
            
    }
    public function login(Request $request){
        try{
            $credentials= request(['job_id','password']);
            // if(!Auth::attempt($credentials)){
            //     return response()->json(['message'=>'Unauthorized'],403);
            // }
            $token = Auth::attempt($credentials);
            if(!$token){
                return response()->json(['message'=>'Unauthorized'],403);
            }
            $user = Auth::user();
            $all = User::find($user->id);
            $roles = $all->getRoleNames()[0];
            Audit::auditLog($request->job_id, "POST", "Logged in");
            return response()->json([
                'user_id'=>$user->id,
                'roles'=>$roles,
                'authorzation'=> [
                    'token' => $token,
                    'type' =>'bearer',
                ],
                'message'=>'Logged Successfuly'], 200);
        }
        catch (\Exception $e){
            return response()->json(['Error!!!' => $e->getMessage()], 500);
        }
        
    }
    public function logout(Request $request){
        Auth::logout();
        return response()->json(['message'=>'You Have Been Logged Out']);
    }
    public function refresh(){
        return response()->json([
            'user_id'=>Auth::user()->id,'authorzation'=> [
                'token' => Auth::refresh(),
                'type' =>'bearer',
            ],
            'message'=>'Registered Successfuly'], 200);
    }
    public function users(){
        $user = User::all();
        return response()->json([$user], 200);
    }
    
    public function registerIntern(Request $request){
        
        try {
            $user=User::create([
                'email'=> $request->name1,
                'job_id'=> Carbon::now(),
                'password'=>bcrypt($request->password1),
            ]);
            
            $token = $user->createToken('AppName')->accessToken;
            
            $username = $request->name1;
            $users = User::all();
            return view('details', compact('username','users'));

        } 
        catch (\Exception $e) {
            
            return $e->getMessage();
        }
        // return Redirect::to('deactives')->withFlashMessage($employee->personal_file_number . '-' . $employee->first_name . ' ' . $employee->last_name . ' successfully activated!');
        
        
        // return response()->json(['token'=>$token], 200);
    }
    
    public function loginIntern(Request $request){
        $credentials= request(['email','password']);
        if(!Auth::attempt($credentials)){
            return response()->json(['message'=>'Unauthorized'],401);
        }
        // $token = Auth::user()->createToken('AppName')->accessToken;
        $username = Auth::user();
        $users = User::all();
        return view('details', compact('username','users'));
        
        
        // return response()->json(['token'=>$token], 200);
    }
    //function to list audit logs 
    public function audits(){
        $details = Audit::join('personal_details', 'audits.external_id','=','personal_details.external_id')->orderBy('audits.id','desc')
            ->select('audits.id','personal_details.name  as user_name','audits.name','audits.action','audits.created_at')->get(); 
        // return $details; //'audits.id','personal_details.name','audits.name','audits.action','audits.created_at'
        return view('audit', compact('details'));
    }
    
    public function signUp(Request $request){
        
        $ips=$request;
        
        $ip = $_SERVER['REMOTE_ADDR'];

        return $ip;
        return $ips->ip();
        // // return $ips;
        // $details = $this->getLocation($ips);
        
        // return $details;
    }
    
    public function back(){
        return redirect('/');
        return redirect()->back(); 
    }
    
    public function getLocation($ips){
        $ip = $ips->ip();
      // Initialize cURL.
      $ch = curl_init();

      // Set the URL that you want to GET by using the CURLOPT_URL option.
      curl_setopt($ch, CURLOPT_URL, 'https://ipgeolocation.abstractapi.com/v1/?api_key=6dc16af7a7c64254951654b6b8c209ba&ip_address='.$ip);

      // Set CURLOPT_RETURNTRANSFER so that the content is returned as a variable.
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      // Set CURLOPT_FOLLOWLOCATION to true to follow redirects.
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

      // Execute the request.
      $data = curl_exec($ch);

      // Close the cURL handle.
      curl_close($ch);

      // Print the data out onto the page.
      echo $data;
              
    }
}
