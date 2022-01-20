<?php

namespace App\Models\User;

use App\Models\Structure;

class Merchant extends Structure {
    protected $primaryKey = 'id';
    protected $table = 'merchants';

    protected $fillable = array('id', 'name', 'logo', 'created_by', 'created_at', 'updated_at', 'deleted_by', 'deleted_at');
}