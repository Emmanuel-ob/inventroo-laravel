<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    use HasFactory;
    protected $fillable = ['reference_no', 'adjustment_type', 'account_id', 'description', 'reason', 'status', 'warehouse_name', 'organization_id', 'created_by_id'];
    protected $table = "inventory_adjustments";

    public function adjustmentProducts(){
        return $this->hasMany(InventoryAdjustmentProduct::class, 'inventory_adjustment_id', 'id');
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by_id')->select(['id', 'name', 'email']);
    }
    
}
