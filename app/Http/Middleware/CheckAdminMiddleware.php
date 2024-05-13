<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Config;
// use Auth;
use Illuminate\Support\Facades\Auth;

class CheckAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = User::getUserId();

        if($user->hasRole('admin')){
            if(collect(explode(',' ,Config::get('app.superadmins')))->contains($user->email)){
                return $next($request);
            }
            
        }
        // // Check if user is authenticated
        // if (Auth::check()) {
        //     $user = Auth::user();
        //     // Check if user is admin
        //     if ($user->hasRole('sir')) {
        //         // Check if admin's email matches superadmin email from config
        //         $superAdminEmail = Config::get('yourconfig.superadmin_email');
        //         if ($user->email === $superAdminEmail) {
        //             // User is admin and matches superadmin email, allow access
        //             return $next($request);
        //         }
        //     }
        // }

        // If user is not authenticated or is not an admin or their email doesn't match superadmin email, deny access
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
