<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
//use App\Notifications\LarashopAdminResetPassword as ResetPasswordNotification;

//use Spatie\Permission\Traits\HasRoles;
//use Spatie\Permission\Models\Role;
//use Spatie\Permission\Models\Permission;

//use DB;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, SoftDeletes;
    // public function sendPasswordResetNotification($token)
    // {
    //     $this->notify(new ResetPasswordNotification($token));
    // }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'first_name', 'middle_name', 'last_name', 'bvn', 'email', 'password', 'unique_id', 'username', 'street', 'street2', 'city', 'postal_code', 'state', 'country', 'phone', 'branch_id', 'organization_id', 'gender', 'profile_image_link', 'account_type', 'status', 'otp', 'otp_expires_at', 'verification_ref', 'email_verified', 'email_verified_at'
        ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function myOrganization()
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'id');
    }

    public function role(){
        return $this->hasOneThrough(
            Role::class,
            RoleUser::class,
            'user_id',
            'id',
            'id',
            'role_id'
        );
    }

    // public function orders()
    // {
    //     return $this->hasOneThrough(
    //         Order::class,
    //         Product::class,
    //         'supplier_id', // Foreign key on products table...
    //         'product_id', // Foreign key on orders table...
    //         'id', // Local key on suppliers table...
    //         'id' // Local key on products table...
    //     );
    // }

    

    public function area(){
        return $this->belongsTo('App\Models\User\Area', 'area_id', 'id');
        }
        
    /*public function branch(){
        return $this->belongsTo('App\Models\User\Branch', 'branch_id', 'id');
        }*/
    
    public function departments(){
        return $this->belongsTo('App\Models\Ums\Department', 'department_id', 'id');
    }

    public function state(){
        return $this->belongsTo('App\Models\User\State', 'state_id', 'id');        
        }

    public function merchant(){
        return $this->hasOne('App\Models\User\MerchantUser', 'user_id', 'id');
    }

    public function savings(){
        return $this->hasMany('App\Models\Saving', 'user_id', 'id');
        }
    
    public function default_saving() {
        return $this->savings()->where('name','=', 'Contributions');
    }

    protected $hidden = ['password', 'remember_token', 'pin'];
}
