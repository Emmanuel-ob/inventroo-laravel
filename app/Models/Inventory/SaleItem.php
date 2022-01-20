<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'sale_items';
    protected $fillable = array('sale_id', 'price', 'quantity', 'product_id', 'item_type');
   
    public function product(){
    	return $this->belongsTo('App\Models\Inventory\Product', 'product_id', 'id');
    }

    public function sale(){
    	return $this->belongsTo('App\Models\Inventory\Sale', 'sale_id', 'id');
    }
}