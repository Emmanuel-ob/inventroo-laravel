<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    protected $table ="audit_trails";
    protected $fillable = ['user_id','user_ip', 'event', 'before', 'after', 'location'];
    
}
