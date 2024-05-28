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
        $user = User::getUser();

        if($user->hasRole('admin')){
            if(collect(explode(',' ,Config::get('app.superadmins')))->contains($user->email)){
                return $next($request);
            }
            
        }

        // If user is not authenticated or is not an admin or their email doesn't match superadmin email, deny access
        return response()->json(['error' => 'action forbidden, you do not have dmin permissions'], 403);
    }
}
