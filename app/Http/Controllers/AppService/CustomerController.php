<?php

namespace App\Http\Controllers\AppService;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
Use \Carbon\Carbon;
use Exception;
use DB;
use Illuminate\Support\Str;
//use JWTAuth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\PayloadFactory;
use Tymon\JWTAuth\JWTManager as JWT;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerBillingAddress;
use App\Models\CustomerShippingAddress;
use App\Http\Resources\ProductGroupResource;
use App\Http\Resources\CustomerResource;
use GuzzleHttp\Client;

use App\Repositories\InventrooMailUtils;
use App\Repositories\TransactionLogUtils;
use App\Repositories\ImageUtils;


class CustomerController extends Controller
{   

  //The constructor function
    public function __construct(InventrooMailUtils $invUtil, TransactionLogUtils $transLogUtil, ImageUtils $imageUtil){
        $this->invUtil = $invUtil;
        $this->transLogUtil = $transLogUtil;
        $this->imageUtil = $imageUtil;
        $this->perPage = 10;
    }


    protected function getAuthUser(Request $request)
    {  
      try {
          if (! $user = JWTAuth::parseToken()->authenticate()) {
            return false;
            return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found', "ResponseCode" => 400],400);
          }
           
          return $user;
         
      } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
          return false;
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Expired Token', "ResponseCode" => $e->getStatusCode()],$e->getStatusCode());
      } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
          return false;
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Invalid token', "ResponseCode" => $e->getStatusCode()],$e->getStatusCode());
      } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
          return false;
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Token not provided', "ResponseCode" => $e->getStatusCode()],$e->getStatusCode());
      }
      return $user;
    }

    

    
    //This Adds a Customer
    public function addCustomer(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'first_name'  => 'string|required',
            'last_name'  => 'string|required',
            'display_name'  => 'string|required',
            'salutation'  => 'string|required',
            'account_type'  => 'string|required',
            'company_name'  => 'string|nullable',
            'customer_email'  => 'string|email|max:255|unique:customers',
            'gender'  => 'string|required',
            'mobile_phone'  => 'numeric|required',
            'work_phone'  => 'numeric|nullable',
            'address'  => 'string|required',
            'date_of_birth'  => 'date|nullable',
            'website_url'  => 'string|nullable',
            'use_delivey_address'  => 'integer|nullable',
            
        ]);
      
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
      
        //$this->transLogUtil->logRequest($request);
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          if (!is_null($request->input('date_of_birth'))) {
            $dob = Carbon::createFromFormat('Y-m-d', $request->input('date_of_birth'));
          }else{
            $dob = $request->input('date_of_birth');
          }

          $customer = Customer::create([
                "first_name" => $request->input('first_name'),
                "last_name" => $request->input('display_name'),
                'display_name' => $request->input('display_name'), 
                'salutation' => $request->input('salutation'), 
                'account_type' => $request->input('account_type'), 
                'company_name' => $request->input('company_name'), 
                'customer_email' => $request->input('customer_email'), 
                'gender' => $request->input('gender'), 
                'mobile_phone' => $request->input('mobile_phone'), 
                'work_phone' => $request->input('work_phone'), 
                'address' => $request->input('address'), 
                'date_of_birth' => $dob, 
                'website_url' => $request->input('website_url'), 
                'organization_id' => $user->organization_id,
              ]);

          if (!is_null($customer) && $request->input('account_type') == 'business') {
            CustomerBillingAddress::create([
                   'customer_id' => $customer->id, 
                   'billing_attention' => $request->input('billing_attention'), 
                   'billing_city' => $request->input('billing_city'), 
                   'billing_state' => $request->input('billing_state'), 
                   'billing_country' => $request->input('billing_country'), 
                   'billing_zip_code' => $request->input('billing_zip_code'), 
                   'billing_address' => $request->input('billing_address'), 
                   'billing_phone' => $request->input('billing_phone'), 
                   'billing_fax' => $request->input('billing_fax'), 
                   'organization_id' => $user->organization_id
                 ]);

            CustomerShippingAddress::create([
                   'customer_id' => $customer->id, 
                   'shipping_attention' => $request->input('shipping_attention'), 
                   'shipping_city' => $request->input('shipping_city'), 
                   'shipping_state' => $request->input('shipping_state'), 
                   'shipping_country' => $request->input('shipping_country'), 
                   'shipping_zip_code' => $request->input('shipping_zip_code'), 
                   'shipping_address' => $request->input('shipping_address'), 
                   'shipping_phone' => $request->input('shipping_phone'), 
                   'shipping_fax' $request->input('shipping_fax'), 
                   'organization_id' => $user->organization_id
                 ]);

          }

          
         $customer = new CustomerResource($customer);

          return response()->json(compact('customer'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


     //This function Modifies a customer's info
    public function editCustomer(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'customerID'  => 'integer|required',
            'first_name'  => 'string|required',
            'last_name'  => 'string|required',
            'display_name'  => 'string|required',
            'salutation'  => 'string|required',
            'account_type'  => 'string|required',
            'company_name'  => 'string|nullable',
            'customer_email'  => 'string|email',
            'gender'  => 'string|nullable',
            'mobile_phone'  => 'numeric|required',
            'work_phone'  => 'numeric|nullable',
            'address'  => 'string|required',
            'date_of_birth'  => 'date|nullable',
            'website_url'  => 'string|nullable',
            'use_delivey_address'  => 'integer|nullable',
        ]);
        
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          $customer = Customer::find($request->input('customerID'));
          $customer_bk = $customer;
          if (!is_null($customer)) {
            if (!is_null($request->input('date_of_birth'))) {
              $dob = Carbon::createFromFormat('Y-m-d', $request->input('date_of_birth'));
            }
              $customer->update(["first_name" => $request->filled('first_name') ? $request->input('first_name') : $customer->first_name,
                'last_name' => $request->filled('last_name') ? $request->input('last_name') : $customer->last_name, 
                'display_name' => $request->filled('display_name') ? $request->input('display_name') : $customer->display_name, 
                'salutation' => $request->filled('salutation') ? $request->input('salutation') : $customer->salutation, 
                'account_type' => $request->filled('account_type') ? $request->input('account_type') : $customer->account_type, 
                'company_name' => $request->filled('company_name') ? $request->input('company_name') : $customer->company_name, 
                'customer_email' => $request->filled('customer_email') ? $request->input('customer_email') : $customer->customer_email, 
                'gender' => $request->filled('gender') ? $request->input('gender') : $customer->gender, 
                'mobile_phone' => $request->filled('mobile_phone') ? $request->input('mobile_phone') : $customer->mobile_phone, 
                'work_phone' => $request->filled('work_phone') ? $request->input('work_phone') : $customer->work_phone, 
                'address' => $request->filled('address') ? $request->input('address') : $customer->address, 
                'date_of_birth' => $request->filled('date_of_birth') ? $dob : $customer->date_of_birth, 
                'website_url' => $request->filled('website_url') ? $request->input('website_url') : $customer->website_url, 
                
              ]);

              $billingAddr = $customer->billingAddress;
              $shippingAddr = $customer->shippingAddress;

              if (!is_null($billingAddr)) {
                $billingAddr->update([
                       'billing_attention' => $request->filled('billing_attention') ? $request->input('billing_attention') : $billingAddr->billing_attention, 
                       'billing_city' => $request->filled('billing_city') ? $request->input('billing_city') : $billingAddr->billing_city, 
                       'billing_state' => $request->filled('billing_state') ? $request->input('billing_state') : $billingAddr->billing_state, 
                       'billing_country' => $request->filled('billing_country') ? $request->input('billing_country') : $billingAddr->billing_country, 
                       'billing_zip_code' => $request->filled('billing_zip_code') ? $request->input('billing_zip_code') : $billingAddr->billing_zip_code, 
                       'billing_address' => $request->filled('billing_address') ? $request->input('billing_address') : $billingAddr->billing_address, 
                       'billing_phone' => $request->filled('billing_phone') ? $request->input('billing_phone') : $billingAddr->billing_phone, 
                       'billing_fax' => $request->filled('billing_fax') ? $request->input('billing_fax') : $billingAddr->billing_fax
                     ]);
                
              }

              if (!is_null($shippingAddr)) {
                
                $shippingAddr->update([
                       'shipping_attention' => $request->filled('shipping_attention') ? $request->input('shipping_attention') : $billingAddr->shipping_attention, 
                       'shipping_city' => $request->filled('shipping_city') ? $request->input('shipping_city') : $billingAddr->shipping_city, 
                       'shipping_state' => $request->filled('shipping_state') ? $request->input('shipping_state') : $billingAddr->shipping_state, 
                       'shipping_country' => $request->filled('shipping_country') ? $request->input('shipping_country') : $billingAddr->shipping_country, 
                       'shipping_zip_code' => $request->filled('shipping_zip_code') ? $request->input('shipping_zip_code') : $billingAddr->shipping_zip_code, 
                       'shipping_address' => $request->filled('shipping_address') ? $request->input('shipping_address') : $billingAddr->shipping_address, 
                       'shipping_phone' => $request->filled('shipping_phone') ? $request->input('shipping_phone') : $billingAddr->shipping_phone, 
                       'shipping_fax' => $request->filled('shipping_fax') ? $request->input('shipping_fax') : $billingAddr->shipping_fax
                     ]);
                
              }
              
              $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Customer Modification', $customer_bk, $customer);

            }
            $customer = new CustomerResource($customer);

            return response()->json(compact('customer'),201);
          
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns an org's customers
    public function getCustomers(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          
          $customers = Customer::where('organization_id', $user->organization_id)->orderBy('first_name', 'asc')->paginate(30);
          
          $customers = CustomerResource::collection($customers);
          
          return response()->json(compact('customers'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   //This returns a customer
    public function findCustomer(Request $request){
       try{
        $validator = Validator::make($request->all() , [
            'customerID'  => 'integer|required',
        ]);
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        
        $customer = Customer::find($request->input('customerID'));
        if (!is_null($customer)) {
          
          $customer = new CustomerResource($customer);
          return response()->json(compact('customer'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'customer not found', "ResponseMessage" => 'customer not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   
    
    //This function deletes a customer
    public function deleteCustomer(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'customerID'  => 'integer|required',
        ]);
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          $customer = Customer::find($request->input('customerID'));
          if (!is_null($customer)) {
            $customer->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'customer deleted successfully', "ResponseMessage" => 'customer deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'customer not found', "ResponseMessage" => 'customer not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

 


}

