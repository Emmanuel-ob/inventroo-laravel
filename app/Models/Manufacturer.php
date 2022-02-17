<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'contact_person', 'contact_phone', 'organization_id'];
    protected $table = "manufacturers";
    
}
