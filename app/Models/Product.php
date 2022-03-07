<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'reference', 'status', 'type', 'dimension', 'unit_id', 'brand_id', 'manufacturer_id', 'tax_id', 'organization_id', 'upc', 'mpn', 'ean', 'isbn', 'currency', 'sale_price', 'sale_tax_percent', 'cost_price', 'cost_tax_percent', 'inventory_account_id', 'opening_stock', 'opening_stock_rate_per_unit', 'recorder_point', 'prefered_vendor', 'image_link', 'product_group_id', 'sku'];
    
    protected $table = "products";

    public function unit()
    {
        return $this->hasOne(Unit::class, 'id', 'unit_id')->select(['id', 'name', 'display_name']);
    }

    public function brand()
    {
        return $this->hasOne(Brand::class, 'id', 'brand_id')->select(['id', 'name']);
    }

    public function manufacturer()
    {
        return $this->hasOne(Manufacturer::class, 'id', 'manufacturer_id')->select(['id', 'name']);
    }

    public function tax()
    {
        return $this->hasOne(Tax::class, 'id', 'tax_id')->select(['id', 'name', 'percentage']);
    }
}
