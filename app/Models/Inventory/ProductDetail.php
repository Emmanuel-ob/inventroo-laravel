<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Structure;

class ProductDetail extends Structure
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'product_details';
    protected $fillable = array('product_id', 'mpn', 'isbn', 'dimensions', 'weight', 'ean', 'created_by', 'created_at', 'updated_by', 'updated_at', 'deleted_by', 'deleted_at');

}