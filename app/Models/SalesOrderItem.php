<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['sales_order_id', 'product_id', 'product_name', 'quantity', 'rate', 'tax', 'amount'];
    
    protected $table = "sales_order_items";

}
