<?php

namespace App\Models\Inventory;

use App\Models\Structure;

class ProductCategory extends Structure
{
    protected $primaryKey = 'id';
    protected $table = 'product_categories';
    protected $fillable = array('name', 'pry_category_id',);

    public function pry_category(){
        return $this->belongsTo('App\Models\Inventory\ProductCategory', 'pry_category_id', 'id');
    }
    
    public function products(){
    	return $this->hasMany('App\Models\Inventory\Product', 'category_id', 'id');
    }
}
