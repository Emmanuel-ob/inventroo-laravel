<?php

namespace App\Models\User;

use App\Models\Structure;

class MerchantUser extends Structure {
    protected $primaryKey = 'id';
    protected $table = 'merchant_users';

    protected $fillable = array('merchant_id', 'user_id', 'role_id', 'created_by', 'created_at', 'updated_at', 'deleted_by', 'deleted_at');

    public function merchant_detail(){
        return $this->belongsTo('App\Models\User\Merchant', 'merchant_id', 'id');
    }
        
    public function role_detail(){
        return $this->belongsTo('App\Models\User\Role', 'role_id', 'id');
    }

    public function user_detail(){
        return $this->belongsTo('App\Models\User', 'user_id', 'id');        
    }
}