<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Structure;

class Brand extends Structure
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'brands';
    protected $fillable = array('name', 'description', 'created_by', 'deleted_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at');

}