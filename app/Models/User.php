<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
// use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'job_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    // function to get user_id from jwttoken
    public static function getUserId(){
        $token = JWTAuth::parseToken()->getPayload()->toArray();
        return $token['sub'];
    }
    public static function getDepartment(){
        $token = JWTAuth::parseToken()->getPayload()->toArray();//$token['sub']
        $department = Leave_applicants::where('external_id',110)
        ->get('department');
        if(count($department) > 0){
            return $department[0]->department;
        }

        return 0;
        
    }
    public static function getUser(){
        $token = JWTAuth::parseToken()->getPayload()->toArray();
        $user_id = $token['sub'];
        return User::find($user_id);
    }

}
