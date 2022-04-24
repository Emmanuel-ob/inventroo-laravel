<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
        'customerID' => $this->id, 
        'name' => $this->first_name. ' '. $this->last_name, 
        'first_name' => $this->first_name, 
        'last_name' => $this->last_name, 
        'display_name' => $this->display_name, 
        'salutation' => $this->salutation, 
        'account_type' => $this->account_type, 
        'company_name' => $this->company_name,
        'customer_email' => $this->customer_email,
        'gender' => $this->gender,
        'mobile_phone' => $this->mobile_phone,
        'work_phone' => $this->work_phone,
        'address' => $this->address,
        'date_of_birth' => Carbon::parse($this->date_of_birth)->format('Y-m-d'),
        'website_url' => $this->website_url,
        'date_created' => Carbon::parse($this->created_at)->format('M d, Y'),
        'date_modified' => Carbon::parse($this->updated_at)->format('M d, Y') 
      ];
        
    }
}
