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
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Manufacturer;
use App\Models\Brand;
use App\Models\InventoryAdjustment;
use App\Models\PriceList;
use App\Models\PriceListProduct;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProductResource;
use GuzzleHttp\Client;

use App\Repositories\InventrooMailUtils;
use App\Repositories\TransactionLogUtils;
use App\Repositories\ImageUtils;


class InventoryController extends Controller
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

    

    
    //This creates an Inventory Adjustment
    public function addInventoryAdjustment(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'productID'  => 'integer|required',
            'adjustment_type'  => 'string|required',
            'account_id'  => 'integer|required',
            'description'  => 'string|required',
            'reason'  => 'string|required',
            'current_value'  => 'numeric|required',
            'changed_value'  => 'numeric|required',
            'adjustment_value'  => 'string|required',
            'quantity_available'  => 'numeric|required',
            'quantity_on_hand'  => 'numeric|nullable',
            'adjusted_quantity_value'  => 'string|nullable',
            'purchase_price'  => 'numeric|nullable',
            'cost_price'  => 'numeric|nullable',
            
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
          
          $inv_adjustmt = InventoryAdjustment::create([
                "organization_id" => $user->organization_id,
                "created_by_id" => $user->id,
                "product_id" => $request->input('productID'),
                'adjustment_type' => $request->input('adjustment_type'), 
                'account_id' => $request->input('account_id'), 
                'description' => $request->input('description'), 
                'reason' => $request->input('reason'), 
                'brand_id' => $request->input('brand_id'), 
                'current_value' => $request->input('current_value'), 
                'changed_value' => $request->input('changed_value'), 
                'adjustment_value' => $request->input('adjustment_value'), 
                'quantity_available' => $request->input('quantity_available'), 
                'quantity_on_hand' => $request->input('quantity_on_hand'), 
                'adjusted_quantity_value' => $request->input('adjusted_quantity_value'), 
                'purchase_price' => $request->input('purchase_price'), 
                'cost_price' => $request->input('cost_price'), 
                'sale_tax_percent' => $request->input('sale_tax_percent'), 
                'cost_price' => $request->input('cost_price'), 
                'cost_tax_percent' => $request->input('cost_tax_percent'), 
                'inventory_account_id' => $request->input('inventory_account_id'), 
                'opening_stock' => $request->input('opening_stock'), 
                'opening_stock_rate_per_unit' => $request->input('opening_stock_rate_per_unit'), 
                'recorder_point' => $request->input('recorder_point'), 
                'prefered_vendor' => $request->input('prefered_vendor')
              ]);

          if (!is_null($inv_adjustmt)) {
            $inv_adjustmt->update(['reference_no' => generateInventoryAdjRef($inv_adjustmt->id)]);
           
            $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Inventory Adjustment Creation', '', $inv_adjustmt);
            
          }
          $inventory_adjustment = $inv_adjustmt;

          return response()->json(compact('inventory_adjustment'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This function modifies an inventory adjustment
    public function editInventoryAdjustment(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'productID'  => 'integer|required',
            'inventoryAdjustmentID'  => 'integer|required',
            'adjustment_type'  => 'string|required',
            'account_id'  => 'integer|required',
            'description'  => 'string|required',
            'reason'  => 'string|required',
            'current_value'  => 'numeric|required',
            'changed_value'  => 'numeric|required',
            'adjustment_value'  => 'string|required',
            'quantity_available'  => 'numeric|required',
            'quantity_on_hand'  => 'numeric|nullable',
            'adjusted_quantity_value'  => 'string|nullable',
            'purchase_price'  => 'numeric|nullable',
            'cost_price'  => 'numeric|nullable',
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
          $inv_adjustmt = InventoryAdjustment::find($request->input('inventoryAdjustmentID'));
          $inv_adjustmt_bk = $inv_adjustmt;
          if (!is_null($inv_adjustmt)) {
            $inv_adjustmt->update([
              "product_id" => $request->filled('productID') ? $request->input('productID') : $inv_adjustmt->product_id,
              'adjustment_type' => $request->filled('adjustment_type') ? $request->input('adjustment_type') : $inv_adjustmt->adjustment_type, 
              'account_id' => $request->filled('account_id') ? $request->input('account_id') : $inv_adjustmt->account_id, 
              'description' => $request->filled('description') ? $request->input('description') : $inv_adjustmt->description, 
              'reason' => $request->filled('reason') ? $request->input('reason') : $inv_adjustmt->reason, 
              'current_value' => $request->filled('current_value') ? $request->input('current_value') : $inv_adjustmt->current_value, 
              'changed_value' => $request->filled('changed_value') ? $request->input('changed_value') : $inv_adjustmt->changed_value, 
              'adjustment_value' => $request->filled('adjustment_value') ? $request->input('adjustment_value') : $inv_adjustmt->adjustment_value, 
              'quantity_available' => $request->filled('quantity_available') ? $request->input('quantity_available') : $inv_adjustmt->quantity_available, 
              'quantity_on_hand' => $request->filled('quantity_on_hand') ? $request->input('quantity_on_hand') : $inv_adjustmt->quantity_on_hand, 
              'adjusted_quantity_value' => $request->filled('adjusted_quantity_value') ? $request->input('adjusted_quantity_value') : $inv_adjustmt->adjusted_quantity_value, 
              'purchase_price' => $request->filled('purchase_price') ? $request->input('purchase_price') : $inv_adjustmt->purchase_price, 
              'cost_price' => $request->filled('cost_price') ? $request->input('cost_price') : $inv_adjustmt->cost_price, 
                
            ]);

              $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Inventory Adjustment Modification', $inv_adjustmt_bk, $inv_adjustmt);
            $inventory_adjustment = $inv_adjustmt;

            return response()->json(compact('inventory_adjustment'),201);
          }
          
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Inventory adjustment not found', "ResponseMessage" => 'Inventory adjustment not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns Inventory Adjustments
    public function getInventoryAdjustments(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          $user_ids[] = 0;
          //$user_ids[] = Auth::user()->id;
          $inv_adjustmts = InventoryAdjustment::where('organization_id', $user->organization_id)->orderBy('created_at', 'desc')->paginate(30);
          $products = Product::where('organization_id', $user->organization_id)->orderBy('name', 'asc');
          $products = ProductResource::collection($products);
          $inventory_adjustments = $inv_adjustmts;
          return response()->json(compact('products', 'inventory_adjustments'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   //This returns find user page
    public function findInventoryAdjustment(Request $request){
       try{
        $validator = Validator::make($request->all() , [
            'inventoryAdjustmentID'  => 'integer|required',
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
        
        $inv_adjustmt = InventoryAdjustment::find($request->input('inventoryAdjustmentID'));
        if (!is_null($inv_adjustmt)) {
          $products = Product::where('organization_id', $user->organization_id)->orderBy('name', 'asc');
          $products = ProductResource::collection($products);
          $inventory_adjustment = $inv_adjustmt;
          return response()->json(compact('products', 'inventory_adjustment'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Inventory adjustment not found', "ResponseMessage" => 'Inventory adjustment not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   
    
    //This function deletes a user's account
    public function deleteInventoryAdjustment(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'inventoryAdjustmentID'  => 'integer|required',
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
          $inv_adjustmt = InventoryAdjustment::find($request->input('inventoryAdjustmentID'));
          if (!is_null($inv_adjustmt)) {
            $inv_adjustmt->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'Inventory adjustment deleted successfully', "ResponseMessage" => 'Inventory adjustment deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Inventory adjustment not found', "ResponseMessage" => 'Inventory adjustment not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

 //This function blocks and unblock a user
    public function approveInventoryAdjustment(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'userID'  => 'integer|required',
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
          if($product = Product::find($request->input('productID'))){
              if($product->status == 1){
                $product->update(['status' => 0]);
                $message = 'Deactivated'; 
              }else{
                $product->update(['status' => 1]);
                $message = 'Activated'; 
              }
             
           return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'product successfully $message', "ResponseMessage" => "product successfully $message", "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'product not found', "ResponseMessage" => 'product not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This Adds a Manufacturer
    public function addManufacturer(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'name'  => 'string|required',
            'contact_person'  => 'string|nullable',
            'contact_phone'  => 'string|nullable',
            
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
          
          $manufacturer = Manufacturer::create(["name" => $request->input('name'),
                        "contact_person" => $request->input('contact_person'),
                        "contact_phone" => $request->input('contact_phone'),
                        "organization_id" => $user->organization_id
                      ]);
          
          return response()->json(compact('manufacturer'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This function a Manufacturer's information
    public function editManufacturer(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'manufacturerID'  => 'integer|required',
            'name'  => 'string|required',
            'contact_person'  => 'string|nullable',
            'contact_phone'  => 'string|nullable',
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
          $manufacturer = Manufacturer::find($request->input('manufacturerID'));
          if (!is_null($manufacturer)) {
            $manufacturer->update([
                    "name" => $request->filled('name') ? $request->input('name') : $manufacturer->name,
                    "contact_person" => $request->filled('contact_person') ? $request->input('contact_person') : $manufacturer->contact_person,
                    "contact_phone" => $request->filled('contact_phone') ? $request->input('contact_phone') : $manufacturer->contact_phone,
                 ]);
            
             
            return response()->json(compact('manufacturer'),201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'manufacturer not found', "ResponseMessage" => 'manufacturer not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns all Manufacturers
    public function getManufacturers(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        
        $manufacturers = Manufacturer::where("organization_id", $user->organization_id)->get();
        if (!is_null($manufacturers)) {
          
          return response()->json(compact('manufacturers'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'manufacturers not found', "ResponseMessage" => 'manufacturers not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
        
    }

   //This returns find a Manufacturer
    public function findManufacturer(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'manufacturerID'  => 'integer|required',
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
        
        $manufacturer = Manufacturer::find($request->input('manufacturerID'));
        if (!is_null($manufacturer)) {
          
          return response()->json(compact('manufacturer'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'manufacturer not found', "ResponseMessage" => 'manufacturer not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

    
    
    //This function deletes a Manufacturer
    public function deleteManufacturer(Request $request){
        try{

        $validator = Validator::make($request->all() , [
            'manufacturerID'  => 'integer|required',
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
          $manufacturer = Manufacturer::find($request->input('manufacturerID'));
          if (!is_null($manufacturer)) {
            $manufacturer->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'manufacturer deleted successfully', "ResponseMessage" => 'manufacturer deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'manufacturer not found', "ResponseMessage" => 'manufacturer not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This Adds a Brand
    public function addBrand(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'name'  => 'string|required',
            'manufacturer_id'  => 'integer|nullable',
            
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
          
          $brand = Brand::create(["name" => $request->input('name'),
                        "manufacturer_id" => $request->input('manufacturer_id'),
                        "organization_id" => $user->organization_id
                      ]);
          
          return response()->json(compact('brand'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This function modifies a Brand
    public function editBrand(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'brandID'  => 'integer|required',
            'name'  => 'string|required',
            'manufacturer_id'  => 'integer|nullable',
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
          $brand = Brand::find($request->input('brandID'));
          if (!is_null($brand)) {
            $brand->update([
                    "name" => $request->filled('name') ? $request->input('name') : $brand->name,
                    "manufacturer_id" => $request->filled('manufacturer_id') ? $request->input('manufacturer_id') : $brand->manufacturer_id,
                 ]);
            
            return response()->json(compact('brand'),201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'brand not found', "ResponseMessage" => 'brand not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns all Brands
    public function getBrands(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        
        $brands = Brand::where("organization_id", $user->organization_id)->get();
        if (!is_null($brands)) {
          $manufacturers = Manufacturer::where('organization_id', $user->organization_id)->get();
          return response()->json(compact('brands', 'manufacturers'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'brands not found', "ResponseMessage" => 'brands not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
        
    }

   //This returns find Brand
    public function findBrand(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'brandID'  => 'integer|required',
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
        
        $brand = Brand::find($request->input('brandID'));
        if (!is_null($brand)) {
          
          return response()->json(compact('brand'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'brand not found', "ResponseMessage" => 'brand not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

    
    
    //This function deletes a Brand
    public function deleteBrand(Request $request){
        try{

        $validator = Validator::make($request->all() , [
            'brandID'  => 'integer|required',
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
          $brand = Brand::find($request->input('brandID'));
          if (!is_null($brand)) {
            $brand->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'brand deleted successfully', "ResponseMessage" => 'brand deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'brand not found', "ResponseMessage" => 'brand not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }



    //This Adds Unit
    public function addUnit(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'name'  => 'string|required',
            'display_name'  => 'string|required',
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
          
          $unit = Unit::create(["name" => $request->input('name'),
                        "display_name" => $request->input('display_name'),
                        "organization_id" => $user->organization_id
                      ]);
          
          return response()->json(compact('unit'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This function modifies a unit's information
    public function editUnit(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'unitID'  => 'integer|required',
            'name'  => 'string|required',
            'display_name'  => 'required|nullable',
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
          $unit = Unit::find($request->input('unitID'));
          if (!is_null($unit)) {
            $unit->update([
                    "name" => $request->filled('name') ? $request->input('name') : $unit->name,
                    "display_name" => $request->filled('display_name') ? $request->input('display_name') : $unit->display_name,
                 ]);
            
            return response()->json(compact('unit'),201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'unit not found', "ResponseMessage" => 'unit not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns all units
    public function getUnits(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        
        $units = Unit::where("organization_id", $user->organization_id)->get();
        if (!is_null($units)) {
         
          return response()->json(compact('units'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'units not found', "ResponseMessage" => 'units not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
        
    }

   //This returns find user page
    public function findUnit(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'unitID'  => 'integer|required',
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
        
        $unit = Unit::find($request->input('unitID'));
        if (!is_null($unit)) {
          
          return response()->json(compact('unit'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'unit not found', "ResponseMessage" => 'unit not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

    
    
    //This function deletes a Unit type
    public function deleteUnit(Request $request){
        try{

        $validator = Validator::make($request->all() , [
            'unitID'  => 'integer|required',
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
          $unit = Unit::find($request->input('unitID'));
          if (!is_null($unit)) {
            $unit->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'unit deleted successfully', "ResponseMessage" => 'unit deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'unit not found', "ResponseMessage" => 'unit not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }



    //This Adds a Tax type
    public function addTax(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'name'  => 'string|required',
            'percentage'  => 'numeric|required',
            
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
          
          $tax = Tax::create(["name" => $request->input('name'),
                        "percentage" => $request->input('percentage'),
                        "organization_id" => $user->organization_id
                      ]);
          
          return response()->json(compact('tax'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This function a user's account information
    public function editTax(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'taxID'  => 'integer|required',
            'name'  => 'string|required',
            'percentage'  => 'numeric|required',
        ]);
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
         
        }
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          $tax = Tax::find($request->input('taxID'));
          if (!is_null($tax)) {
            $tax->update([
                    "name" => $request->filled('name') ? $request->input('name') : $tax->name,
                    "percentage" => $request->filled('percentage') ? $request->input('percentage') : $tax->percentage,
                 ]);
           
            return response()->json(compact('tax'),201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'tax not found', "ResponseMessage" => 'tax not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns all roles
    public function getTaxes(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        
        $taxes = Tax::where("organization_id", $user->organization_id)->get();
        if (!is_null($taxes)) {
          
          return response()->json(compact('taxes'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'taxes not found', "ResponseMessage" => 'taxes not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
        
    }

   //This returns find user page
    public function findTax(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'taxID'  => 'integer|required',
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
        
        $tax = Tax::find($request->input('taxID'));
        if (!is_null($tax)) {
          
          return response()->json(compact('tax'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'tax not found', "ResponseMessage" => 'tax not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500); 
      }
    }

    //This function deletes a Tax type
    public function deleteTax(Request $request){
        try{

        $validator = Validator::make($request->all() , [
            'taxID'  => 'integer|required',
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
          $tax = Tax::find($request->input('taxID'));
          if (!is_null($tax)) {
            $tax->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'tax deleted successfully', "ResponseMessage" => 'tax deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'tax not found', "ResponseMessage" => 'tax not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }



 
    

   


}

