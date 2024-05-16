<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
    //assign roles
    public function assignRole(Request $request){
        try{
            $request->validate([
                'user_id' => 'required|string|max:255|exists:users,id',
                'role' => 'required|string|max:255|exists:roles,name',
            ]);


            $user = User::find($request->user_id);
            $role = Role::findByName($request->role);
            $permission = Permission::findByName($request->permission);
            // var_dump($user);
            $user->assignRole($role);
            $user->givePermissionTo($permission);
            return $user->getRoleNames();
            return $user->can('edit_post');
            return $user->hasRole('admin');
        }
        // catch (ValidationException $e) {
        //     if ($e->errors()['role'][0] === 'The selected role is invalid.') {
        //         // Handle the error here
        //     }
        // }
        catch(\Exception $e){
            return response()->json(['failed'=>$e->getMessage()], 500);
        }
        
    }
}
