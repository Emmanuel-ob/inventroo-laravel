<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
        'companyID' => $this->id, 
        'company_name' => $this->clientName, 
        'business_email' => $this->business_email, 
        'business_phone' => $this->business_phone_no, 
        'business_type' => $this->account_type, 
        'business_category' => $this->business_category, 
        'reg_no' => $this->reg_no, 
        'vat_id' => $this->vat_id, 
        'website_link' => $this->website_link, 
        'business_address' => $this->address, 
        'city' => $this->city,
        'state' => $this->state,
        'country' => $this->biz_acct_country,
        'acct_no' => $this->biz_acct_no,
        'acct_name' => $this->biz_acct_name,
        'acct_bank' => $this->biz_acct_bank,
        'fiscal_year_from' => $this->fiscal_year_from,
        'fiscal_year_to' => $this->fiscal_year_to,  
      ];
        
    }
}
