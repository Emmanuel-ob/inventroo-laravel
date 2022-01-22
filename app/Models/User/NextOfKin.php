<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Structure;

class NextOfKin extends Structure {
    protected $primaryKey = 'id';
    protected $table = 'next_of_kins';
    protected $fillable = array('user_id', 'name', 'address', 'phone', 'email', 'relationship');

    
	public function user(){
    	return $this->belongsTo('App\Models\User', 'user_id', 'id');
		}
	}