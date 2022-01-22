<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\Structure;

class State extends Structure {
    protected $primaryKey = 'id';
    protected $table = 'states';
    protected $fillable = array('name');

    public function areas(){
        return $this->hasMany('App\Models\User\Area', 'state_id', 'id');
        }

	}
