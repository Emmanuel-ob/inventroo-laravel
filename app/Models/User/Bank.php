<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\Structure;

class Bank extends Structure {
    protected $primaryKey = 'id';
    protected $table = 'banks';
    protected $fillable = array('name', 'bank_name', 'purpose', 'status', 'number'); 
}