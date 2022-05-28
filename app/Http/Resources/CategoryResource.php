<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
        'id' => $this->id, 
        'category_name' => $this->category_name, 
        'description' => $this->description, 
        'products' => ProductResource::collection($this->products), 
        'date_created' => Carbon::parse($this->created_at)->format('M d, Y'),
        'date_modified' => Carbon::parse($this->updated_at)->format('M d, Y') 
      ];
        
    }
}
