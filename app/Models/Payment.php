<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = ['credit', 'discount', 'tips', 'tax', 'sub_total', 'total', 'currency', 'payment_mode', 'organization_id', 'created_by_id'];
    protected $table = "payments";


    public function items(){
        return $this->hasMany(PaymentItem::class, 'payment_id', 'id');
    }
    
}
