<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceListResource extends JsonResource
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
        'priceListID' => $this->id, 
        'name' => $this->name, 
        'type' => $this->type, 
        'description' => $this->description, 
        'mark_type' => $this->mark_type, 
        'percentage' => $this->percentage, 
        'roundoff' => $this->roundoff,
        'currency' => $this->currency,
        'products' => $this->items,
        'created_by' => $this->creator,
        'productGroupStatus' => ($this->status == 1) ? 'Active' : 'Inactive',
        'date_created' => Carbon::parse($this->created_at)->format('M d, Y'),
        'date_modified' => Carbon::parse($this->updated_at)->format('M d, Y') 
      ];
        
    }
}
