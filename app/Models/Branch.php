<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Structure;

class Branch extends Structure {
    protected $primaryKey = 'id';
    protected $table = 'branches';
    protected $fillable = array('name', 'state_id');
}