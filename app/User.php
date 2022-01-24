<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Customer;
use App\Models\SocialProvider;
use App\Models\SocialLink;
use App\Models\UserPasswordHistory;
use App\Models\UserLoginAttempt;
Use \Carbon\Carbon;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;


// class User extends Authenticatable
// {
//     use Notifiable;

    protected $fillable = [
        'name', 'firstName', 'lastName', 'email', 'password', 'status', 'customerID', 'two_factor_code', 'two_factor_expires_at', 'phoneNo', 'email_verified', 'email_verified_at', 'profile_image_link', 'address', 'locked', 'unlock_at', 'jwt_expires_at', 'active_jwt', 'is_supervisor', 'sent_welcome_msg', 'resetPassword'
    ];
    
    protected $dates = ['unlock_at', 'two_factor_expires_at', 'jwt_expires_at'];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }


    function socialProviders()
    {
        return $this->hasMany(SocialProvider::class);
    }

    function loginAttempts()
    {
        return $this->hasMany(UserLoginAttempt::class);
    }

    function lastLoginAttempt()
    {
        return $this->hasOne(UserLoginAttempt::class)->latest()->select(['user_ip', 'count', 'login_status', 'initial_login_at', 'last_login_at']);
        //return $this->hasOne(UserLoginAttempt::class)->latest()->select(['user_ip', 'count', 'login_status', 'initial_login_at', 'last_login_at']);
    }

    function passwordHistory()
    {
        return $this->hasMany(UserPasswordHistory::class);
    }

    function userInfo()
    {
        return $this->hasOne(Customer::class, 'id', 'customerID');
    }

    function mediaLinks()
    {
        return $this->hasMany(SocialLink::class, 'user_id', 'id');
    }

    public function generateTwoFactorCode()
    {
        $this->timestamps = false;
        $this->two_factor_code = rand(100000, 999999);
        $this->two_factor_expires_at = Carbon::now()->addMinutes(5);
        $this->save();
    }

    public function resetTwoFactorCode()
    {
        $this->timestamps = false;
        $this->two_factor_code = null;
        $this->two_factor_expires_at = null;
        $this->save();
    }

    
}
