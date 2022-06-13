<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
        'paymentID' => $this->id,
        'credit' => $this->credit,
        'discount' => $this->discount,
        'tips' => $this->tips,
        'tax' => $this->tax,
        'sub_total' => $this->sub_total,
        'total' => $this->total,
        'currency' => $this->currency,
        'items' => $this->items,
        'date_created' => Carbon::parse($this->created_at)->format('M d, Y'),
        'date_modified' => Carbon::parse($this->updated_at)->format('M d, Y') 
      ];
      
    }
}
