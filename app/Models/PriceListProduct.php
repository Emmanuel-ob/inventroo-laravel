<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceListProduct extends Model
{
    use HasFactory;
    protected $fillable = ['price_list_id', 'product_id', 'reference', 'sales_rate', 'custom_rate', 'discount_percent', 'currency', 'organization_id'];
    protected $table = "price_list_products";
    
}
