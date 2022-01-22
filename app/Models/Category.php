<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Structure
{
    protected $fillable = [
        'name', 'cp_name', 'cp_phone', 'cp_email', 'email', 'website', 'unique_id', 'street', 'street2', 'city', 'area_id', 'state_id', 'phone', 'branch_id', 'image', 
    ];

    public function area(){
        return $this->belongsTo('App\Models\User\Area', 'area_id', 'id');
    }
        
    public function state(){
        return $this->belongsTo('App\Models\User\State', 'state_id', 'id');        
    }
}