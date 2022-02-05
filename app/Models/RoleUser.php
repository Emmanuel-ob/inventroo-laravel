<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleUser extends AuditTrailedModel
{
    protected $fillable = ['user_id', 'role_id', 'is_checker'];
    // public $table = "role_users";

    public function user()
   	{
   		return $this->belongsTo('App\User');
   	}
    
}
