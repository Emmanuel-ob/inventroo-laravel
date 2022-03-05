<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'reference_no', 'adjustment_type', 'account_id', 'description', 'reason', 'current_value', 'changed_value', 'adjustment_value', 'quantity_available', 'quantity_on_hand', 'adjusted_quantity_value', 'purchase_price', 'cost_price', 'status', 'organization_id', 'created_by_id'];
    protected $table = "inventory_adjustments";
    
}
