<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function testAdmin(){
        $user = User::getUserId();
        
        // return explode(',' ,Config::get('app.superadmins'));
        if(collect(explode(',' ,Config::get('app.superadmins')))->contains($user->email)){
            return $user->email;
            return Config::get('app.superadmins');
        }
        
    }
    //assign roles
    public function assignRoles(Request $request){
        try{
            $user = User::find($request->user_id);
            $role = Role::findByName($request->role);
            $permission = Permission::findByName($request->permission);
            // var_dump($user);
            $user->assignRole($role);
            $user->givePermissionTo($permission);
            return $user->can('edit_post');
            return $user->hasRole('admin');
        }
        catch(\Exception $e){
            return $e->getMessage();
        }
        
    }
}
