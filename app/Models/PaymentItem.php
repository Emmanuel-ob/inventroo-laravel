<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentItem extends Model
{
    use HasFactory;
    protected $fillable = ['payment_id', 'product_id', 'product_name', 'quantity', 'rate', 'total_cost', 'currency'];
    protected $table = "payment_items";


    public function items(){
        return $this->hasMany(PaymentItem::class, 'payment_id', 'id');
    }

}
