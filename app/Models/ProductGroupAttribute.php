<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductGroupAttribute extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'product_group_id', 'attribute_name', 'attribute_value', 'organization_id'];
    protected $table = "product_group_attributes";

}
