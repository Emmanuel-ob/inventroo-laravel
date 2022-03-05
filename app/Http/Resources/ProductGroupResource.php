<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductGroupResource extends JsonResource
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
        'productGroupID' => $this->id, 
        'name' => $this->name, 
        'reference' => $this->reference, 
        'type' => $this->type, 
        'returnable' => $this->returnable, 
        'unit' => $this->unit, 
        'brand' => $this->brand, 
        'manufacturer' => $this->manufacturer,
        'tax' => $this->tax,
        'attributes' => $this->attributes,
        'products' => $this->products,
        'created_by' => $this->creator,
        'productGroupStatus' => ($this->status == 1) ? 'Active' : 'Inactive',
        'date_created' => Carbon::parse($this->created_at)->format('M d, Y'),
        'date_modified' => Carbon::parse($this->updated_at)->format('M d, Y') 
      ];
        
    }
}
