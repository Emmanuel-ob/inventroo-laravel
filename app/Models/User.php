<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
//use App\Notifications\LarashopAdminResetPassword as ResetPasswordNotification;

//use Spatie\Permission\Traits\HasRoles;
//use Spatie\Permission\Models\Role;
//use Spatie\Permission\Models\Permission;

//use DB;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'bvn', 'email', 'password', 'unique_id', 'username', 'street', 'street2', 'city', 'area_id', 'state_id', 'phone', 'branch_id', 'sex', 'image', 'saving_id', 'pin'     
        ];

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
