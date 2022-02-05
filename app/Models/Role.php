<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use App\Models\AuditTrailedModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends AuditTrailedModel
{
    use SoftDeletes;
    protected $fillable = ['name', 'description', 'organization_id'];
    // public $table = "roles";
    public function modules()
    {
        return $this->hasManyThrough(Module::class, ModuleRole::class, 'role_id', 'id', 'id', 'module_id')->select(['name','description']);
    }

    public function role_users()
    {
    	return $this->hasMany('App\Models\RoleUser');
    	// return $this->hasMany('App\Models\Module', 'module_roles');
    }
}
