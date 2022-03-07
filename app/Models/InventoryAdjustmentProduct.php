<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAdjustmentProduct extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'inventory_adjustment_id', 'current_value', 'changed_value', 'adjustment_value', 'quantity_available', 'quantity_on_hand', 'adjusted_quantity_value', 'purchase_price', 'cost_price', 'organization_id'];
    protected $table = "inventory_adjustment_products";

    public function product(){
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}
