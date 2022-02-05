<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use App\Models\AuditTrailedModel;

class Module extends Model
{
	use SoftDeletes;
    protected $fillable = ['name', 'description'];
    // public $table = "roles";

    
}
