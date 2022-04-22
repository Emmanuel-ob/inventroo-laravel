<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name', 'display_name', 'salutation', 'account_type', 'company_name', 'customer_email', 'gender', 'mobile_phone', 'work_phone', 'address', 'date_of_birth', 'website_url', 'organization_id'];
    
    protected $table = "customers";
    
}
