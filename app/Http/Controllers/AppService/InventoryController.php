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
use App\Models\InventoryAdjustmentProduct;
use App\Models\PriceList;
use App\Models\PriceListProduct;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\InventoryAdjustmentResource;
use App\Http\Resources\PriceListResource;
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
            //'productID'  => 'integer|required',
            'adjustment_type'  => 'string|required',
            'account_id'  => 'integer|required',
            'description'  => 'string|required',
            'reason'  => 'string|required',
            'reference_no'  => 'string|nullable',
            // 'current_value'  => 'numeric|required',
            // 'changed_value'  => 'numeric|required',
            // 'adjustment_value'  => 'string|required',
            // 'quantity_available'  => 'numeric|required',
            // 'quantity_on_hand'  => 'numeric|nullable',
            // 'adjusted_quantity_value'  => 'string|nullable',
            // 'purchase_price'  => 'numeric|nullable',
            // 'cost_price'  => 'numeric|nullable',
            'products'  => 'required|array',
            
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
                'reference_no' =>  $request->input('reference_no'),
                //"product_id" => $request->input('productID'),
                'adjustment_type' => $request->input('adjustment_type'), 
                'account_id' => $request->input('account_id'), 
                'description' => $request->input('description'), 
                'reason' => $request->input('reason'), 
                'warehouse_name' => $request->input('warehouse_name'),  
                
              ]);

          if (!is_null($inv_adjustmt)) {
            if (is_null($inv_adjustmt->reference_no)) {
              $inv_adjustmt->update(['reference_no' => generateInventoryAdjRef($inv_adjustmt->id)]);
            }
            
            $products = $request->input('products');
            if (!is_null($products)) {
              foreach ($products as $product) {
                $product = json_decode($product);
                InventoryAdjustmentProduct::create([
                  'inventory_adjustment_id' => $inv_adjustmt->id, 
                  'product_id' => $product->product_id, 
                  'current_value' => $product->current_value, 
                  'changed_value' => $product->changed_value, 
                  'adjustment_value' => $product->adjustment_value, 
                  'quantity_available' => $product->quantity_available, 
                  'quantity_on_hand' => $product->quantity_on_hand, 
                  'adjusted_quantity_value' => $product->adjusted_quantity_value, 
                  'purchase_price' => $product->purchase_price, 
                  'cost_price' => $product->cost_price, 
                ]);
              }
            }
            
           
            $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Inventory Adjustment Creation', '', $inv_adjustmt);
            
          }
          $inventory_adjustment = new InventoryAdjustmentResource($inv_adjustmt);

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
            //'productID'  => 'integer|required',
            'inventoryAdjustmentID'  => 'integer|required',
            'adjustment_type'  => 'string|required',
            'account_id'  => 'integer|required',
            'description'  => 'string|required',
            'reason'  => 'string|required',
            'reference_no'  => 'string|nullable',
            'products'  => 'required|array',
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
          if (!is_null($inv_adjustmt) && $inv_adjustmt->status == 0) {
            $inv_adjustmt->update([
              'adjustment_type' => $request->filled('adjustment_type') ? $request->input('adjustment_type') : $inv_adjustmt->adjustment_type, 
              'account_id' => $request->filled('account_id') ? $request->input('account_id') : $inv_adjustmt->account_id, 
              'description' => $request->filled('description') ? $request->input('description') : $inv_adjustmt->description, 
              'reason' => $request->filled('reason') ? $request->input('reason') : $inv_adjustmt->reason, 
              'reference_no' => $request->filled('reference_no') ? $request->input('reference_no') : $inv_adjustmt->reference_no, 
              'warehouse_name' => $request->filled('warehouse_name') ? $request->input('warehouse_name') : $inv_adjustmt->warehouse_name, 
                
            ]);

            $products = $request->input('products');
            if (!is_null($products)) {
              InventoryAdjustmentProduct::where('inventory_adjustment_id', $inv_adjustmt->id)->delete();
              foreach ($products as $product) {
                $product = json_decode($product);
                InventoryAdjustmentProduct::create([
                  'inventory_adjustment_id' => $inv_adjustmt->id, 
                  'product_id' => $product->product_id, 
                  'current_value' => $product->current_value, 
                  'changed_value' => $product->changed_value, 
                  'adjustment_value' => $product->adjustment_value, 
                  'quantity_available' => $product->quantity_available, 
                  'quantity_on_hand' => $product->quantity_on_hand, 
                  'adjusted_quantity_value' => $product->adjusted_quantity_value, 
                  'purchase_price' => $product->purchase_price, 
                  'cost_price' => $product->cost_price, 
                ]);
              }
            }

              $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Inventory Adjustment Modification', $inv_adjustmt_bk, $inv_adjustmt);
            $inventory_adjustment = new InventoryAdjustmentResource($inv_adjustmt);

            return response()->json(compact('inventory_adjustment'),201);
          }
          
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'Inventory adjustment not found or it has been processed', "ResponseMessage" => 'Inventory adjustment not found or it has been processed', "ResponseCode" => 401],401);
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
          $products = Product::where('organization_id', $user->organization_id)->orderBy('name', 'asc')->get();
          $products = ProductResource::collection($products);
          $inventory_adjustments = InventoryAdjustmentResource::collection($inv_adjustmts);
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
          $products = Product::where('organization_id', $user->organization_id)->orderBy('name', 'asc')->get();
          $products = ProductResource::collection($products);
          $inventory_adjustment = new InventoryAdjustmentResource($inv_adjustmt);
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
            InventoryAdjustmentProduct::where('inventory_adjustment_id', $inv_adjustmt->id)->delete();
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
    public function addPriceList(Request $request){
      try{
        $validator = Validator::make($request->all() , [
          'name'  => 'string|required',
          'type'  => 'string|required',
          'description'  => 'string|nullable',
          'mark_type'  => 'string|required',
          'percentage'  => 'numeric|required',
          'roundoff'  => 'string|required',
          'currency'  => 'string|nullable',
          'products'  => 'nullable|array',
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
          
          $priceList = PriceList::create([
                        "name" => $request->input('name'),
                        "type" => $request->input('type'),
                        "description" => $request->input('description'),
                        "mark_type" => $request->input('mark_type'),
                        "percentage" => $request->input('percentage'),
                        "roundoff" => $request->input('roundoff'),
                        "organization_id" => $user->organization_id,
                        "created_by_id" => $user->id
                      ]);
          if (!is_null($priceList)) {
            $products = $request->input('products');
            if (!is_null($products)) {
              foreach ($products as $product) {
                $product = json_decode($product);
                PriceListProduct::create([
                    'price_list_id' => $priceList->id, 
                    'product_id' => $product->product_id, 
                    'reference' => $product->reference, 
                    'sales_rate' => $product->sales_rate, 
                    'custom_rate' => $product->custom_rate, 
                    'discount_percent' => $product->discount_percent, 
                    //'currency' => $product->currency, 
                    'organization_id' => $user->organization_id
                ]);
              }
            }
          }
          $priceList = new PriceListResource($priceList);
          return response()->json(compact('priceList'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This function a Manufacturer's information
    public function editPriceList(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'priceListID'  => 'integer|required',
            'name'  => 'string|required',
            'type'  => 'string|required',
            'description'  => 'string|nullable',
            'mark_type'  => 'string|required',
            'percentage'  => 'numeric|required',
            'roundoff'  => 'string|required',
            'currency'  => 'string|nullable',
            'products'  => 'nullable|array',
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
          $priceList = PriceList::find($request->input('priceListID'));
          if (!is_null($priceList)) {
            $priceList->update([
                    "name" => $request->filled('name') ? $request->input('name') : $priceList->name,
                    "type" => $request->filled('type') ? $request->input('type') : $priceList->type,
                    "description" => $request->filled('description') ? $request->input('description') : $priceList->description,
                    "mark_type" => $request->filled('mark_type') ? $request->input('mark_type') : $priceList->mark_type,
                    "percentage" => $request->filled('percentage') ? $request->input('percentage') : $priceList->percentage,
                    "roundoff" => $request->filled('roundoff') ? $request->input('roundoff') : $priceList->roundoff,
                 ]);

            $products = $request->input('products');
            if (!is_null($products)) {
              PriceListProduct::where('price_list_id', $priceList->id)->delete();
              foreach ($products as $product) {
                $product = json_decode($product);
                PriceListProduct::create([
                    'price_list_id' => $priceList->id, 
                    'product_id' => $product->product_id, 
                    'reference' => $product->reference, 
                    'sales_rate' => $product->sales_rate, 
                    'custom_rate' => $product->custom_rate, 
                    'discount_percent' => $product->discount_percent, 
                    //'currency' => $product->currency, 
                    'organization_id' => $user->organization_id
                ]);
              }
            }
            $priceList = new PriceListResource($priceList); 
            return response()->json(compact('priceList'),201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'manufacturer not found', "ResponseMessage" => 'manufacturer not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns all Manufacturers
    public function getPriceLists(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        
        $priceLists = PriceList::where("organization_id", $user->organization_id)->get();
        if (!is_null($priceLists)) {
          $priceLists = PriceListResource::collection($priceLists);
          return response()->json(compact('priceLists'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'price List not found', "ResponseMessage" => 'price List not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
        
    }

   //This returns find a Manufacturer
    public function findPriceList(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'priceListID'  => 'integer|required',
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
        
        $priceList = PriceList::find($request->input('priceListID'));
        if (!is_null($priceList)) {
          $priceList = new PriceListResource($priceList);
          return response()->json(compact('priceList'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'price List not found', "ResponseMessage" => 'price List not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

    
    
    //This function deletes a Manufacturer
    public function deletePriceList(Request $request){
        try{

        $validator = Validator::make($request->all() , [
            'priceListID'  => 'integer|required',
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
          $priceList = PriceList::find($request->input('priceListID'));
          if (!is_null($priceList)) {
            $priceList->delete();
            PriceListProduct::where('price_list_id', $priceList->id)->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'price List deleted successfully', "ResponseMessage" => 'price List deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'price List not found', "ResponseMessage" => 'price List not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }




}

