<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        
       return [
        'productID' => $this->id, 
        'name' => $this->name, 
        'reference' => $this->reference, 
        'type' => $this->type, 
        'dimension' => $this->dimension, 
        'unit' => $this->unit, 
        'brand' => $this->brand, 
        'manufacturer' => $this->manufacturer,
        'tax' => $this->tax,
        'upc' => $this->upc, 
        'mpn' => $this->mpn, 
        'ean' => $this->ean, 
        'isbn' => $this->isbn, 
        'sku' => $this->sku, 
        'currency' => $this->currency, 
        'sale_price' => $this->sale_price, 
        'sale_tax_percent' => $this->sale_tax_percent, 
        'cost_price' => $this->cost_price, 
        'cost_tax_percent' => $this->cost_tax_percent, 
        'inventory_account_id' => $this->inventory_account_id, 
        'opening_stock' => $this->opening_stock, 
        'opening_stock_rate_per_unit' => $this->opening_stock_rate_per_unit, 
        'recorder_point' => $this->recorder_point, 
        'image_link' => $this->image_link, 
        'prefered_vendor' => $this->prefered_vendor,
        'categoy' => $this->category, 
        'productStatus' => ($this->status == 1) ? 'Active' : 'Inactive',
        'date_created' => Carbon::parse($this->created_at)->format('M d, Y'),
        'date_modified' => Carbon::parse($this->updated_at)->format('M d, Y') 
      ];
        
    }
}
