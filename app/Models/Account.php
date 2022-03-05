<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'product_group_id', 'sales_account', 'purchase_account', 'inventory_account', 'organization_id'];
    protected $table = "accounts";
   
}
