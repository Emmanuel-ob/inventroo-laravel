<?php

namespace App\Http\Resources;
Use \Carbon\Carbon;
//use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
        'userID' => $this->id, 
        'name' => $this->name, 
        'email' => $this->email, 
        'first_name' => $this->first_name, 
        'last_name' => $this->last_name, 
        'phone' => $this->phone, 
        'organization' => $this->myOrganization, 
        // 'otp' => $this->otp, 
        // 'otp_expires_in_secs' => Carbon::now()->diffInSeconds(Carbon::parse($this->otp_expires_in_secs)), 
        'profileStatus' => ($this->status == 1) ? true : false,
        //'AccountStatus' => ($this->locked == 1) ? 'Locked' : 'Active',
        'email_verified' => ($this->email_verified== 1) ? true : false,  
      ];
        
    }
}
