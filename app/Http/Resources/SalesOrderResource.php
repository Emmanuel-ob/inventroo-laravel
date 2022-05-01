<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
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
        'salesOrderID' => $this->id, 
        'customer_id' => $this->customer_id, 
        'customer_name' => $this->customer_name, 
        'sales_order' => $this->sales_order, 
        'reference' => $this->reference, 
        'sales_date' => Carbon::parse($this->sales_date)->format('Y-m-d'), 
        'expected_shipment_date' => Carbon::parse($this->expected_shipment_date)->format('Y-m-d'), 
        'payment_term' => $this->payment_term, 
        'delivery_method' => $this->delivery_method,
        'customer_note' => $this->customer_note,
        'sales_person' => $this->sales_person,
        'total' => $this->total,
        'items' => $this->items,
        'date_created' => Carbon::parse($this->created_at)->format('M d, Y'),
        'date_modified' => Carbon::parse($this->updated_at)->format('M d, Y') 
      ];
      
    }
}
