<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class UserBranch extends Structure {
    protected $primaryKey = 'id';
    protected $table = 'user_branches';
    protected $fillable = array('user_id', 'branch_id');
	}