<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'type', 'description', 'mark_type', 'percentage', 'roundoff', 'currency', 'organization_id', 'created_by_id'];
    protected $table = "price_lists";

    public function items(){
        return $this->hasMany(PriceListProduct::class, 'price_list_id', 'id');
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by_id')->select(['id', 'name', 'email']);
    }
}
