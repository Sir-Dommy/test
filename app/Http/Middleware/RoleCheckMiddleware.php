<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = User::getUser();

        if($user->hasAnyRole($roles)){
            return $next($request);
            
        }

        // If user is not authenticated or does not have given permissions deny access
        return response()->json(['error' => 'action forbidden'], 403);
    }
}
