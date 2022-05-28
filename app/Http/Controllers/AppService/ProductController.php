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
use App\Models\ProductGroup;
use App\Models\ProductGroupProduct;
use App\Models\ProductGroupAttribute;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Manufacturer;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductGroupResource;
use GuzzleHttp\Client;

use App\Repositories\InventrooMailUtils;
use App\Repositories\TransactionLogUtils;
use App\Repositories\ImageUtils;


class ProductController extends Controller
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

    

    
    //This Adds a product
    public function addProduct(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'name'  => 'string|required',
            'type'  => 'string|required',
            'dimension'  => 'string|required',
            'weight'  => 'string|required',
            'unit_id'  => 'integer|required',
            'brand_id'  => 'integer|required',
            'manufacturer_id'  => 'integer|required',
            'tax_id'  => 'integer|required',
            'category_id'  => 'integer|nullable',
            'inventory_account_id'  => 'integer|nullable',
            'upc'  => 'string|nullable',
            'mpn'  => 'string|nullable',
            'ean'  => 'string|nullable',
            'isbn'  => 'string|nullable',
            'sku'  => 'string|nullable',
            'currency'  => 'string|nullable',
            'sale_price'  => 'numeric|required',
            'sale_tax_percent'  => 'numeric|nullable',
            'cost_price'  => 'numeric|required',
            'cost_tax_percent'  => 'numeric|nullable',
            'opening_stock'  => 'numeric|nullable',
            'opening_stock_rate_per_unit'  => 'numeric|nullable',
            'recorder_point'  => 'string|nullable',
            'prefered_vendor'  => 'string|nullable',
            'product_image' => 'nullable|mimes:jpeg,jpg,png,gif,bmp|max:1024',
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

          $product = Product::create(["name" => $request->input('name'),
                "organization_id" => $user->organization_id,
                'type' => $request->input('type'), 
                'dimension' => $request->input('dimension'), 
                'weight' => $request->input('weight'), 
                'unit_id' => $request->input('unit_id'), 
                'brand_id' => $request->input('brand_id'), 
                'manufacturer_id' => $request->input('manufacturer_id'), 
                'tax_id' => $request->input('tax_id'), 
                'category_id' => $request->input('category_id'), 
                'upc' => $request->input('upc'), 
                'mpn' => $request->input('mpn'), 
                'ean' => $request->input('ean'), 
                'isbn' => $request->input('isbn'), 
                'sku' => $request->input('sku'), 
                'currency' => $request->input('currency'), 
                'sale_price' => $request->input('sale_price'), 
                'sale_tax_percent' => $request->input('sale_tax_percent'), 
                'cost_price' => $request->input('cost_price'), 
                'cost_tax_percent' => $request->input('cost_tax_percent'), 
                'inventory_account_id' => $request->input('inventory_account_id'), 
                'opening_stock' => $request->input('opening_stock'), 
                'opening_stock_rate_per_unit' => $request->input('opening_stock_rate_per_unit'), 
                'recorder_point' => $request->input('recorder_point'), 
                'prefered_vendor' => $request->input('prefered_vendor')
              ]);

          if (!is_null($product)) {
            $product->update(['reference' => generateProductRef($product->id)]);
            if($request->hasFile('product_image')){
                $imageArray = $this->imageUtil->saveImgArray($request->file('product_image'), '/products/', $product->id, $request->hasFile('optional_images') ? $request->file('optional_images') : []);

                if (!is_null($imageArray)) {
                    $primaryImg = array_shift($imageArray);
                    $product->update(['image_link' => $primaryImg]);
                 } 
                 
            }
            $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Product Creation', '', $product);
            
          }
         $product = new ProductResource($product);

          return response()->json(compact('product'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


     //This function a user's account information
    public function editProduct(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'productID'  => 'integer|required',
            'name'  => 'string|required',
            'type'  => 'string|required',
            'dimension'  => 'string|required',
            'weight'  => 'string|required',
            'unit_id'  => 'integer|required',
            'brand_id'  => 'integer|required',
            'manufacturer_id'  => 'integer|required',
            'tax_id'  => 'integer|required',
            'category_id'  => 'integer|nullable',
            'inventory_account_id'  => 'integer|nullable',
            'upc'  => 'string|nullable',
            'mpn'  => 'string|nullable',
            'ean'  => 'string|nullable',
            'isbn'  => 'string|nullable',
            'sku'  => 'string|nullable',
            'currency'  => 'string|nullable',
            'sale_price'  => 'numeric|required',
            'sale_tax_percent'  => 'numeric|nullable',
            'cost_price'  => 'numeric|required',
            'cost_tax_percent'  => 'numeric|nullable',
            'opening_stock'  => 'numeric|nullable',
            'opening_stock_rate_per_unit'  => 'numeric|nullable',
            'recorder_point'  => 'string|nullable',
            'prefered_vendor'  => 'string|nullable',
            'product_image' => 'nullable|mimes:jpeg,jpg,png,gif,bmp|max:1024',
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
          $product = Product::find($request->input('productID'));
          $prod_bk = $product;
          if (!is_null($product)) {
              $product->update(["name" => $request->filled('name') ? $request->input('name') : $product->name,
                'type' => $request->filled('type') ? $request->input('type') : $product->type, 
                'dimension' => $request->filled('dimension') ? $request->input('dimension') : $product->dimension, 
                'weight' => $request->filled('weight') ? $request->input('weight') : $product->weight, 
                'unit_id' => $request->filled('unit_id') ? $request->input('unit_id') : $product->unit_id, 
                'brand_id' => $request->filled('brand_id') ? $request->input('brand_id') : $product->brand_id, 
                'manufacturer_id' => $request->filled('manufacturer_id') ? $request->input('manufacturer_id') : $product->manufacturer_id, 
                'tax_id' => $request->filled('tax_id') ? $request->input('tax_id') : $product->tax_id, 
                'category_id' => $request->filled('category_id') ? $request->input('category_id') : $product->category_id, 
                'upc' => $request->filled('upc') ? $request->input('upc') : $product->upc, 
                'mpn' => $request->filled('mpn') ? $request->input('mpn') : $product->mpn, 
                'ean' => $request->filled('ean') ? $request->input('ean') : $product->ean, 
                'isbn' => $request->filled('isbn') ? $request->input('isbn') : $product->isbn, 
                'sku' => $request->filled('sku') ? $request->input('sku') : $product->sku, 
                'currency' => $request->filled('currency') ? $request->input('currency') : $product->currency, 
                'sale_price' => $request->filled('sale_price') ? $request->input('sale_price') : $product->sale_price, 
                'sale_tax_percent' => $request->filled('sale_tax_percent') ? $request->input('sale_tax_percent') : $product->sale_tax_percent, 
                'cost_price' => $request->filled('cost_price') ? $request->input('cost_price') : $product->cost_price, 
                'cost_tax_percent' => $request->filled('cost_tax_percent') ? $request->input('cost_tax_percent') : $product->cost_tax_percent, 
                'inventory_account_id' => $request->filled('inventory_account_id') ? $request->input('inventory_account_id') : $product->inventory_account_id, 
                'opening_stock' => $request->filled('opening_stock') ? $request->input('opening_stock') : $product->opening_stock, 
                'opening_stock_rate_per_unit' => $request->filled('opening_stock_rate_per_unit') ? $request->input('opening_stock_rate_per_unit') : $product->opening_stock_rate_per_unit, 
                'recorder_point' => $request->filled('recorder_point') ? $request->input('recorder_point') : $product->recorder_point, 
                'prefered_vendor' => $request->filled('prefered_vendor') ? $request->input('prefered_vendor') : $product->prefered_vendor
              ]);

              if($request->hasFile('product_image')){
                  if (!is_null($product->image_link)) {
                      $this->imageUtil->deleteImage($product->image_link);
                  }
                  $imageArray = $this->imageUtil->saveImgArray($request->file('product_image'), '/products/', $product->id, $request->hasFile('optional_images') ? $request->file('optional_images') : []);

                  if (!is_null($imageArray)) {
                      $primaryImg = array_shift($imageArray);
                      $product->update(['image_link' => $primaryImg]);
                   } 
                   
              }

              $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Product Modification', $prod_bk, $product);

            }
            $product = new ProductResource($product);

            return response()->json(compact('product'),201);
          
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns user mgt page
    public function getProducts(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          $user_ids[] = 0;
          //$user_ids[] = Auth::user()->id;
          $products = Product::where('organization_id', $user->organization_id)->orderBy('name', 'asc')->paginate(30);
          
          $brands = Brand::where('organization_id', $user->organization_id)->get();
          $manufacturers = Manufacturer::where('organization_id', $user->organization_id)->get();
          $units = Unit::where('organization_id', $user->organization_id)->get();
          $taxes = Tax::where('organization_id', $user->organization_id)->get();
          $products = ProductResource::collection($products);
          
          return response()->json(compact('products', 'brands', 'manufacturers', 'units', 'taxes'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   //This returns find user page
    public function findProduct(Request $request){
       try{
        $validator = Validator::make($request->all() , [
            'productID'  => 'integer|required',
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
        
        $product = Product::find($request->input('productID'));
        if (!is_null($product)) {
          $brands = Brand::where('organization_id', $user->organization_id)->get();
          $manufacturers = Manufacturer::where('organization_id', $user->organization_id)->get();
          $units = Unit::where('organization_id', $user->organization_id)->get();
          $taxes = Tax::where('organization_id', $user->organization_id)->get();
          $product = new ProductResource($product);
          return response()->json(compact('product', 'brands', 'manufacturers', 'units', 'taxes'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'product not found', "ResponseMessage" => 'product not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   
    
    //This function deletes a user's account
    public function deleteProduct(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'productID'  => 'integer|required',
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
          $product = Product::find($request->input('productID'));
          if (!is_null($product)) {
            $product->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'product deleted successfully', "ResponseMessage" => 'product deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'product not found', "ResponseMessage" => 'product not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

 //This function blocks and unblock a user
    public function blockProduct(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'productID'  => 'integer|required',
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


    //This gets products in a category
    public function getProductsByCategory(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'category_id'  => 'integer|required',
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
          
          $products = Product::where([['organization_id', $user->organization_id], ['category_id', $request->input('category_id')]])->orderBy('name', 'asc')->paginate(30);
          
          $products = ProductResource::collection($products);
          
          return response()->json(compact('products'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This gets product  search result
    public function searchProduct(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'search_value'  => 'string|required',
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
          $search_value = $request->input('search_value');
          $products = Product::where('name',  'LIKE', '%' . $search_value . '%')
                      ->orWhere('sku', $search_value)
                      ->orWhere('reference', $search_value)
                      ->where('organization_id', $user->organization_id)
                      ->orderBy('name', 'asc')->paginate(30);
          
          $products = ProductResource::collection($products);
          
          return response()->json(compact('products'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   
    //This gets product  search result
    public function scanProduct(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'search_value'  => 'string|required',
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
          $search_value = $request->input('search_value');
          $product = Product::where('sku', $search_value)->first();
          if (!is_null($product)) {
            $product = new ProductResource($product); 
          }else{
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'No Product matching input was found.', "ResponseMessage" => "No Product matching input was found.", "ResponseCode" => 401], 401);     
          }
          
          return response()->json(compact('product'),201);
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

    //This Adds Product Category
    public function addCategory(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'category_name'  => 'string|required',
            'description'  => 'string|nullable',
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
          
          $category = ProductCategory::create([
                        "category_name" => $request->input('category_name'),
                        "description" => $request->input('description'),
                        "organization_id" => $user->organization_id
                      ]);
          
          return response()->json(compact('category'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This function modifies a ProductCategory's information
    public function editCategory(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'categoryID'  => 'integer|required',
            'category_name'  => 'string|required',
            'description'  => 'required|nullable',
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
          $category = ProductCategory::find($request->input('categoryID'));
          if (!is_null($category)) {
            $category->update([
                    "category_name" => $request->filled('category_name') ? $request->input('category_name') : $category->category_name,
                    "description" => $request->filled('description') ? $request->input('description') : $category->description,
                 ]);
            
            return response()->json(compact('category'),201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'category not found', "ResponseMessage" => 'category not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns all categories
    public function getCategories(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        
        $categories = ProductCategory::where("organization_id", $user->organization_id)->get();
        if (!is_null($categories)) {
         
          return response()->json(compact('categories'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'categories not found', "ResponseMessage" => 'categories not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
        
    }

   //This returns a category
    public function findCategory(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'categoryID'  => 'integer|required',
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
        
        $category = ProductCategory::find($request->input('categoryID'));
        if (!is_null($category)) {
          
          return response()->json(compact('category'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'category not found', "ResponseMessage" => 'category not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

    
    
    //This function deletes a Category
    public function deleteCategory(Request $request){
        try{

        $validator = Validator::make($request->all() , [
            'categoryID'  => 'integer|required',
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
          $category = ProductCategory::find($request->input('categoryID'));
          if (!is_null($category)) {
            $category->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'category deleted successfully', "ResponseMessage" => 'category deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'category not found', "ResponseMessage" => 'category not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }



    //This Adds a product Group
    public function addProductGroup(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'name'  => 'string|required',
            'type'  => 'string|required',
            'returnable'  => 'integer|nullable',
            'unit_id'  => 'integer|required',
            'brand_id'  => 'integer|required',
            'category_id'  => 'integer|nullable',
            'manufacturer_id'  => 'integer|required',
            'tax_id'  => 'integer|required',
            'products'  => 'required',
            'attributes'  => 'required',
            //'attributes.*'  => 'string',
            'product_image' => 'nullable|mimes:jpeg,jpg,png,gif,bmp|max:1024',
        ]);

      
        if($validator->fails()){
          //$this->transLogUtil->logRequestError($request);
          return response()->json([ "ResponseStatus" => "Unsuccessful", 'Detail' => $validator->errors(), "ResponseCode" => 401, "ResponseMessage" => implode(', ',$validator->messages()->all())], 401);
          //implode(', ',$validator->messages()->all())
        }
        // $attributes = json_decode($request->input('attributes'));
        // $products = json_decode($request->input('products'));
        // return response()->json(compact('attributes', 'products'),201);
        //$this->transLogUtil->logRequest($request);
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {

          $productGr = ProductGroup::create(["name" => $request->input('name'),
                "organization_id" => $user->organization_id,
                'created_by_id' => $user->id,
                'type' => $request->input('type'), 
                'returnable' => $request->input('returnable'), 
                'unit_id' => $request->input('unit_id'), 
                'brand_id' => $request->input('brand_id'), 
                'manufacturer_id' => $request->input('manufacturer_id'), 
                'tax_id' => $request->input('tax_id'), 
                
              ]);

          if (!is_null($productGr)) {
            $productGr->update(['reference' => generateProductGroupRef($productGr->id)]);
            
            if (gettype($request->input('attributes')) == 'array') {
              $attributes = $request->input('attributes');
            }else{
              $attributes = json_decode($request->input('attributes'));
            }
            if (!is_null($attributes)) {
              foreach ($attributes as $attribute) {
                //$attribute = json_decode($attribute);
                ProductGroupAttribute::create([
                  'product_group_id' => $productGr->id, 
                  'attribute_name' => $attribute->name, 
                  'attribute_value' => $attribute->option, 
                  'organization_id' => $user->organization_id
                ]);
              }
            }

            
            if (gettype($request->input('products')) == 'array') {
              $products = $request->input('products');
            }else{
              $products = json_decode($request->input('products'));
            }
            if (!is_null($products)) {
              foreach ($products as $product) {
                //$product = json_decode($product);
                $product = Product::create([
                  'product_group_id' => $productGr->id, 
                  "organization_id" => $user->organization_id,
                  'type' => $request->input('type'), 
                  'name' => $product->name, 
                  //'weight' => $request->input('weight'), 
                  'unit_id' => $request->input('unit_id'), 
                  'brand_id' => $request->input('brand_id'), 
                  'manufacturer_id' => $request->input('manufacturer_id'), 
                  'tax_id' => $request->input('tax_id'), 
                  'category_id' => $request->input('category_id'), 
                  'upc' => $product->upc, 
                  //'mpn' => $product->mpn, 
                  'ean' => $product->ean, 
                  'isbn' => $product->isbn, 
                  'sku' => $product->sku, 
                  //'currency' => $request->input('currency'), 
                  'sale_price' => $product->sale_price, 
                  //'sale_tax_percent' => $request->input('sale_tax_percent'), 
                  'cost_price' => $product->cost_price, 
                  //'cost_tax_percent' => $request->input('cost_tax_percent'), 
                  //'inventory_account_id' => $request->input('inventory_account_id'), 
                  //'opening_stock' => $request->input('opening_stock'), 
                  //'opening_stock_rate_per_unit' => $request->input('opening_stock_rate_per_unit'), 
                  'recorder_point' => $product->recorder_point, 
                  //'prefered_vendor' => $request->input('prefered_vendor') 
                ]);

                if (!is_null($product)) {
                  $product->update(['reference' => generateProductRef($product->id)]);
                }
              }
            }

            if($request->hasFile('product_image')){
                $imageArray = $this->imageUtil->saveImgArray($request->file('product_image'), '/productGroups/', $productGr->id, $request->hasFile('optional_images') ? $request->file('optional_images') : []);

                if (!is_null($imageArray)) {
                    $primaryImg = array_shift($imageArray);
                    $productGr->update(['image_link' => $primaryImg]);
                 } 
                 
            }
            
            $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Product Group Creation', '', $productGr);
            
          }
         $productGroup = new ProductGroupResource($productGr);

          return response()->json(compact('productGroup'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


     //This function modifies aproduct group
    public function editProductGroup(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'productGroupID'  => 'integer|required',
            'name'  => 'string|required',
            'type'  => 'string|required',
            'returnable'  => 'integer|nullable',
            'unit_id'  => 'integer|required',
            'brand_id'  => 'integer|required',
            'manufacturer_id'  => 'integer|required',
            'tax_id'  => 'integer|required',
            'category_id'  => 'integer|nullable',
            'products'  => 'required',
            //'products.*'  => 'integer',
            'attributes'  => 'required',
            //'attributes.*'  => 'string',
            'product_image' => 'nullable|mimes:jpeg,jpg,png,gif,bmp|max:1024',
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
          $productGr = ProductGroup::find($request->input('productGroupID'));
          $productGr_bk = $productGr;
          if (!is_null($productGr)) {
              $productGr->update(["name" => $request->filled('name') ? $request->input('name') : $productGr->name,
                'type' => $request->filled('type') ? $request->input('type') : $productGr->type, 
                'returnable' => $request->filled('returnable') ? $request->input('returnable') : $productGr->returnable, 
                'unit_id' => $request->filled('unit_id') ? $request->input('unit_id') : $productGr->unit_id, 
                'brand_id' => $request->filled('brand_id') ? $request->input('brand_id') : $productGr->brand_id, 
                'manufacturer_id' => $request->filled('manufacturer_id') ? $request->input('manufacturer_id') : $productGr->manufacturer_id, 
                'tax_id' => $request->filled('tax_id') ? $request->input('tax_id') : $productGr->tax_id, 
                
              ]);

              
              if (gettype($request->input('attributes')) == 'array') {
                $attributes = $request->input('attributes');
              }else{
                $attributes = json_decode($request->input('attributes'));
              }
              if (!is_null($attributes)) {
                ProductGroupAttribute::where('product_group_id', $productGr->id)->delete();
                foreach ($attributes as $attribute) {
                  //$attribute = json_decode($attribute);
                  ProductGroupAttribute::create([
                    'product_group_id' => $productGr->id, 
                    'attribute_name' => $attribute->name, 
                    'attribute_value' => $attribute->option, 
                    'organization_id' => $user->organization_id
                  ]);
                }
              }

              if (gettype($request->input('products')) == 'array') {
                $products = $request->input('products');
              }else{
                $products = json_decode($request->input('products'));
              }
              if (!is_null($products)) {
                Product::where('product_group_id', $productGr->id)->delete();
                foreach ($products as $product) {
                  //$product = json_decode($product);
                  $product = Product::create([
                    'product_group_id' => $productGr->id, 
                    "organization_id" => $user->organization_id,
                    'type' => $request->input('type'), 
                    'name' => $product->name, 
                    //'weight' => $request->input('weight'), 
                    'unit_id' => $request->input('unit_id'), 
                    'brand_id' => $request->input('brand_id'), 
                    'category_id' => $request->input('category_id'), 
                    'manufacturer_id' => $request->input('manufacturer_id'), 
                    'tax_id' => $request->input('tax_id'), 
                    'upc' => $product->upc, 
                    //'mpn' => $product->mpn, 
                    'ean' => $product->ean, 
                    'isbn' => $product->isbn, 
                    'sku' => $product->sku, 
                    //'currency' => $request->input('currency'), 
                    'sale_price' => $product->sale_price, 
                    //'sale_tax_percent' => $request->input('sale_tax_percent'), 
                    'cost_price' => $product->cost_price, 
                    //'cost_tax_percent' => $request->input('cost_tax_percent'), 
                    //'inventory_account_id' => $request->input('inventory_account_id'), 
                    //'opening_stock' => $request->input('opening_stock'), 
                    //'opening_stock_rate_per_unit' => $request->input('opening_stock_rate_per_unit'), 
                    'recorder_point' => $product->recorder_point, 
                    //'prefered_vendor' => $request->input('prefered_vendor') 
                  ]);
                  
                  
                }
              } 

              
              if($request->hasFile('product_image')){
                  if (!is_null($productGr->image_link)) {
                      $this->imageUtil->deleteImage($productGr->image_link);
                  }
                  $imageArray = $this->imageUtil->saveImgArray($request->file('product_image'), '/productGroups/', $productGr->id, $request->hasFile('optional_images') ? $request->file('optional_images') : []);

                  if (!is_null($imageArray)) {
                      $primaryImg = array_shift($imageArray);
                      $productGr->update(['image_link' => $primaryImg]);
                   } 
                   
              }

              $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'Product Group Modification', $productGr_bk, $productGr);

            }
            $productGroup = new ProductGroupResource($productGr);

            return response()->json(compact('productGroup'),201);
          
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns user mgt page
    public function getProductGroups(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          $user_ids[] = 0;
          //$user_ids[] = Auth::user()->id;
          $productGroups = ProductGroup::where('organization_id', $user->organization_id)->orderBy('name', 'asc')->paginate(30);
          $products = Product::where('organization_id', $user->organization_id)->orderBy('name', 'asc')->get();
          $brands = Brand::where('organization_id', $user->organization_id)->get();
          $manufacturers = Manufacturer::where('organization_id', $user->organization_id)->get();
          $units = Unit::where('organization_id', $user->organization_id)->get();
          $taxes = Tax::where('organization_id', $user->organization_id)->get();
          $productGroups = ProductGroupResource::collection($productGroups);
          $products = ProductResource::collection($products);
          
          return response()->json(compact('productGroups', 'products', 'brands', 'manufacturers', 'units', 'taxes'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   //This returns find user page
    public function findProductGroup(Request $request){
       try{
        $validator = Validator::make($request->all() , [
            'productGroupID'  => 'integer|required',
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
        
        $productGroup = ProductGroup::find($request->input('productGroupID'));
        if (!is_null($productGroup)) {
          $products = Product::where('organization_id', $user->organization_id)->orderBy('name', 'asc')->get();
          $brands = Brand::where('organization_id', $user->organization_id)->get();
          $manufacturers = Manufacturer::where('organization_id', $user->organization_id)->get();
          $units = Unit::where('organization_id', $user->organization_id)->get();
          $taxes = Tax::where('organization_id', $user->organization_id)->get();
          $productGroup = new ProductResource($productGroup);
          $products = ProductResource::collection($products);
          return response()->json(compact('productGroup', 'products', 'brands', 'manufacturers', 'units', 'taxes'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'product group not found', "ResponseMessage" => 'product group not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   
    
    //This function deletes a product group
    public function deleteProductGroup(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'productGroupID'  => 'integer|required',
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
          $productGroup = ProductGroup::find($request->input('productGroupID'));
          if (!is_null($productGroup)) {
            $productGroup->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'product group deleted successfully', "ResponseMessage" => 'product group deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'product group not found', "ResponseMessage" => 'product group not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

 //This function blocks and unblock a user
    public function blockProductGroup(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'productGroupID'  => 'integer|required',
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
          if($productGroup = ProductGroup::find($request->input('productGroupID'))){
              if($productGroup->status == 1){
                $productGroup->update(['status' => 0]);
                $message = 'Deactivated'; 
              }else{
                $productGroup->update(['status' => 1]);
                $message = 'Activated'; 
              }
             
           return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'product group successfully $message', "ResponseMessage" => "product group successfully $message", "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'product group not found', "ResponseMessage" => 'product group not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }



 
    

   


}

