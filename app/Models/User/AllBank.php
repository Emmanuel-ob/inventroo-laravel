<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\Structure;

class AllBank extends Structure {
    protected $primaryKey = 'id';
    protected $table = 'all_banks';
    protected $fillable = array('bank_name', 'status');
}
