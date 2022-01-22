<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\Structure;

class Area extends Structure {
    protected $primaryKey = 'id';
    protected $table = 'areas';
    protected $fillable = array('name', 'state_id');
}