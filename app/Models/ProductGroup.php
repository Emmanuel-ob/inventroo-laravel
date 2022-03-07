<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductGroup extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'reference', 'status', 'type', 'returnable', 'unit_id', 'brand_id', 'manufacturer_id', 'tax_id', 'organization_id', 'created_by_id', 'image_link'];
    protected $table = "product_groups";

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

    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by_id')->select(['id', 'name', 'email']);
    }

    public function attributes(){
        return $this->hasMany(ProductGroupAttribute::class, 'product_group_id', 'id');
    }
    
    public function products(){
        return $this->hasMany(Product::class, 'product_group_id', 'id');
    }

    // public function products(){
    //     return $this->hasManyThrough(
    //         Product::class,
    //         ProductGroupProduct::class,
    //         'product_id',
    //         'id',
    //         'id',
    //         'product_group_id'
    //     );
    // }

    
    
}
