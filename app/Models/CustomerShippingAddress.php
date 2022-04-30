<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerShippingAddress extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'shipping_attention', 'shipping_city', 'shipping_state', 'shipping_country', 'shipping_zip_code', 'shipping_address', 'shipping_phone', 'shipping_fax', 'organization_id'];
    
    protected $table = "customer_shipping_addresses";

}
