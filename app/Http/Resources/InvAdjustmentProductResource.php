<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class InvAdjustmentProductResource extends JsonResource
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
        'product_id' => $this->id,
        'current_value' => $this->current_value, 
        'changed_value' => $this->changed_value, 
        'adjustment_value' => $this->adjustment_value, 
        'quantity_available' => $this->quantity_available, 
        'quantity_on_hand' => $this->quantity_on_hand, 
        'adjusted_quantity_value' => $this->adjusted_quantity_value, 
        'purchase_price' => $this->purchase_price,
        'cost_price' => $this->cost_price,
        'product' => $this->product, 
        'date_created' => Carbon::parse($this->created_at)->format('M d, Y'),
        'date_modified' => Carbon::parse($this->updated_at)->format('M d, Y') 
      ];
        
    }
}
