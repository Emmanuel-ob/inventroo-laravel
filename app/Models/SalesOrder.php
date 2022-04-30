<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;
    
    protected $fillable = ['customer_id', 'customer_name', 'sales_order', 'reference', 'sales_date', 'expected_shipment_date', 'payment_term', 'delivery_method', 'customer_note', 'sales_person', 'total', 'organization_id'];
    
    protected $table = "sales_orders";

    
    public function items(){
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id', 'id');
    }
    
}
