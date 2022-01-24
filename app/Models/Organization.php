<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Organization extends Model
{
    protected $table ="organizations";
    protected $fillable = ['clientName','business_email', 'description', 'country', 'address', 'state', 'city', 'business_phone_no', 'account_type', 'admin_user_id', 'sub_user_count'];
    
     
   function adminUser()
    {
        return $this->hasOne(User::class, 'id', 'admin_user_id');
    }
}
