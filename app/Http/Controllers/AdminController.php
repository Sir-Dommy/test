<?php

namespace App\Http\Controllers;

use App\Models\Departments;
use App\Models\Leave_applicants;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function testAdmin(){
        $user = User::getUser(); 
        $token = Auth::payload();
        // $token->invalidate(true);
        return $token;
        // return explode(',' ,Config::get('app.superadmins'));
        if(collect(explode(',' ,Config::get('app.superadmins')))->contains($user->email)){
            return $user->email;
            return Config::get('app.superadmins');
        }
        
    }

    public function getUsers(){
        $all = User::select('id')->get();

        $details = [];
        $user_role = "none";

        foreach($all as $single){
            $applicant = Leave_applicants::where('external_id', $single->id)
                ->select('name', 'department')
                ->get();

            $name  = null;
            $department = null;
            if(count($applicant) > 0){
                $name = $applicant[0]->name;
                $department = $applicant[0]->department;
            }
            
            $roles = Role::all();
            $user = User::find($single->id);
            if($user){
                $user_role = $user->getRoleNames();
                if(count($user_role) > 0){
                    $user_role = $user_role [0]; 
                } 
                else{
                    $user_role = "none";
                }
            }
            
            $details[] = [
                "user_id" => $single->id,
                "name" => $name,
                "department" => $department,
                "role" => $user_role
            ];
        }
          
        return response()->json([
            "roles" => $this->getRoles(),
            "departments" => $this->listDepartments(),
            "users" => $details,
        ], 200);
    }

    public function updateUser(Request $request){
        try{
            // if (!$request->hasHeader('Authorization')) {
            //     return response()->json(['error' => 'Authorization header not found'], 401);
            // } 
            // else{
            //     $token = JWTAuth::parseToken()->getPayload()->toArray();
            //     return $token;
            // }   
            
            $request->validate([
                'user_id' => 'required|integer|min:1|exists:users,id',
                // 'name' => 'required|string|min:1|max:70',
                'department' => 'required|string|min:3|max:150|exists:departments,department_name',
                'role' => 'required|string|min:1|max:20|exists:roles,name',
            ]);

            DB::beginTransaction();
            
            Leave_applicants::where('external_id', $request->user_id)
                ->update([
                    // 'name' => $request->name,
                    'department' => $request->department,
                ]);

            $user = User::find($request->user_id);
            // removeAllRolesFromUser($user);
            $user -> syncRoles([]);

            $user->assignRole($request->role);

            DB::commit();

            return response()->json([
                'user_id' => $request->user_id,
                'name' => $request->name,
                'department' => $request->department,
                'role' => $request->role
            ], 200);
        }
        catch(ValidationException $e){
            return response()->json(['validation error'=>$e->getMessage()], 422);
        }
        catch(\Exception $e){
            DB::rollBack();
            return response()->json(['failed'=>$e->getMessage()], 500);
        }
        
    }
    public function getRoles(){
        $roles = Role::select('name')->get();

        $roles_array = [];

        foreach($roles as $role){
            array_push($roles_array, $role->name);
        }

        return $roles_array;
    }
    //assign roles
    public function assignRole(Request $request){
        try{
            $request->validate([
                'user_id' => 'required|string|max:255|exists:users,id',
                'role' => 'required|string|max:255|exists:roles,name',
            ]);

            DB::beginTransaction();
            $user = User::find($request->user_id);
            $role = Role::findByName($request->role);
            // $permission = Permission::findByName($request->permission);

            $user -> syncRoles([]);
            // var_dump($user);
            $user->assignRole($role);
            // $user->givePermissionTo($permission);

            DB::commit();
            return response()->json([
                "user_id" => $request->user_id,
                "role" => $role,
                "message" => "role assigned successifully"
            ], 200);

        }
        catch (ValidationException $e) {
            return response()->json(['failed'=>$e->getMessage()], 422);
        }
        catch(\Exception $e){
            DB::rollback();
            return response()->json(['failed'=>$e->getMessage()], 500);
        }
        
    }

    public function listDepartments(){
        $all = Departments::select('id', 'department_name', 'status')
            ->get();

        $details = [];
        foreach($all as $single){
            array_push($details, $single->department_name);
        }

        return $details;
    }

    public function createDepartment(Request $request){
        try{
            $request->validate([
                'department_name'=> 'required|string|min:1|max:150|unique:departments,department_name',
            ]);
    
            DB::beginTransaction();
            $department = Departments::create([
                'department_name' => $request->department_name
            ]);

            DB::commit();

            return response()->json([
                "department_id" => $department->id,
                "department_name" => $department->department_name,
                "status" => 1,
                "message" => "created",
            ], 200);
        }
        catch(ValidationException $e){
            return response()->json(['validation error'=>$e->getMessage()], 422);
        }
        catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()], 500);
        }
    }

    public function updateDepartment(Request $request){
        try{
            $request->validate([
                'department_id'=> 'required|integer|min:1|exists:departments,id',
                'status'=> 'required|boolean',
                'department_name'=> 'required|string|min:1|max:150|unique:departments,department_name',
            ]);
    
            DB::beginTransaction();
            Departments::where('id', $request->department_id)
                ->update([
                    'department_name' => $request->department_name,
                    'status' => $request->status
                ]);

            DB::commit();

            return response()->json([
                "department_id" => $request->department_id,
                "department_name" => $request->department_name,
                "status" => $request->status,
                "message" => "updated"
            ], 200);
        }
        catch(ValidationException $e){
            if($e->getMessage() == "The department name has already been taken."){
                DB::beginTransaction();
                Departments::where('id', $request->department_id)
                    ->update([
                        'department_name' => $request->department_name,
                        'status' => $request->status
                    ]);

                DB::commit();

                return response()->json([
                    "department_id" => $request->department_id,
                    "department_name" => $request->department_name,
                    "status" => $request->status,
                    "message" => "updated"
                ], 200);
            }

            return response()->json(['validation error'=>$e->getMessage()], 422);
        }
        catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error'=>$e->getMessage()], 500);
        }
    }
}
