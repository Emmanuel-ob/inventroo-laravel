<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAdjustmentResource extends JsonResource
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
        'iventoryAdjustmentID' => $this->id, 
        'reference_no' => $this->reference_no, 
        'adjustment_type' => $this->adjustment_type, 
        'account_id' => $this->account_id, 
        'description' => $this->description, 
        'reason' => $this->reason, 
        'brand' => $this->brand, 
        'status' => ($this->status == 0) ? 'Not processed' : 'Processed',
        'warehouse_name' => $this->warehouse_name,
        'adjustmentProducts' => InvAdjustmentProductResource::collection($this->adjustmentProducts),
        'created_by' => $this->creator,
        'date_created' => Carbon::parse($this->created_at)->format('M d, Y'),
        'date_modified' => Carbon::parse($this->updated_at)->format('M d, Y') 
      ];
        
    }
}
