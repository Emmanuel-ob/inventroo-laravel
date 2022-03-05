<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductGroupProduct extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'product_group_id', 'organization_id'];
    protected $table = "product_group_products";
    
}
