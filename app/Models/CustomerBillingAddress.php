<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBillingAddress extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'billing_attention', 'billing_city', 'billing_state', 'billing_country', 'billing_zip_code', 'billing_address', 'billing_phone', 'billing_fax', 'organization_id'];
    
    protected $table = "customer_billing_addresses";

}
