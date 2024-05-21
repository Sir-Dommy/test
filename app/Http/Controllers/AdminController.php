<?php

namespace App\Http\Controllers;

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
        $all = Leave_applicants::select('external_id', 'name', 'department')->get();

        $details = [];
        $user_role = "none";

        foreach($all as $single){
            $roles = Role::all();
            $user = User::find($single->external_id);
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
                "user_id" => $single->external_id,
                "name" => $single->name,
                "department" => $single->department,
                "role" => $user_role
            ];
        }
          
        return response()->json([
            "roles" => $this->getRoles(),
            "users" => $details,
        ], 200);
    }

    public function getRoles(){
        $roles = Role::select('id', 'name')->get();

        return $roles;
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
            $permission = Permission::findByName($request->permission);
            // var_dump($user);
            $user->assignRole($role);
            $user->givePermissionTo($permission);

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
}
