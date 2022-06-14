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
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Payment;
use App\Models\PaymentItem;
use GuzzleHttp\Client;
use App\Http\Resources\SalesOrderResource;
use App\Http\Resources\PaymentResource;
use App\Repositories\InventrooMailUtils;
use App\Repositories\TransactionLogUtils;
use App\Repositories\ImageUtils;


class SalesOrderController extends Controller
{   

  //The constructor function
    public function __construct(InventrooMailUtils $invUtil, TransactionLogUtils $transLogUtil, ImageUtils $imageUtil){
        $this->invUtil = $invUtil;
        $this->transLogUtil = $transLogUtil;
        $this->imageUtil = $imageUtil;
        $this->perPage = 30;
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

    

    
    //This Adds a Sales Order
    public function addSalesOrder(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'customer_id'  => 'integer|required',
            'customer_name'  => 'string|required',
            'sales_order'  => 'string|required',
            'reference'  => 'string|required',
            'sales_date'  => 'date|required',
            'expected_shipment_date'  => 'date|required',
            'payment_term'  => 'string|nullable',
            'delivery_method'  => 'string|nullable',
            'customer_note'  => 'string|nullable',
            'sales_person'  => 'string|required',
            'items'  => 'required',
        
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
          if ($request->filled('sales_date')) {
            $sales_date = Carbon::createFromFormat('Y-m-d', $request->input('sales_date'));
          }else{
            $sales_date = $request->input('sales_date');
          }

          if ($request->filled('expected_shipment_date')) {
            $expected_shipment_date = Carbon::createFromFormat('Y-m-d', $request->input('expected_shipment_date'));
          }else{
            $expected_shipment_date = $request->input('expected_shipment_date');
          }

          $salesOrder = SalesOrder::create([
                "customer_id" => $request->input('customer_id'),
                "customer_name" => $request->input('customer_name'),
                'sales_order' => $request->input('sales_order'), 
                'reference' => $request->input('reference'), 
                'sales_date' => $sales_date, 
                'expected_shipment_date' => $expected_shipment_date, 
                'payment_term' => $request->input('payment_term'), 
                'delivery_method' => $request->input('delivery_method'), 
                'customer_note' => $request->input('customer_note'), 
                'sales_person' => $request->input('sales_person'), 
                'organization_id' => $user->organization_id,
              ]);
         

          if (!is_null($salesOrder) && $request->filled('items')) {
            $itemType = gettype($request->input('items'));
            if ($itemType == 'array') {
              $items = $request->input('items');
            }else{
              $items = json_decode($request->input('items'));
            }
            $total = 0;
            foreach ($items as $item) {
              if ($itemType == 'array') {
                $item = json_decode($item);
              }
              SalesOrderItem::create([
                 'sales_order_id' => $salesOrder->id, 
                 'product_id' => $item->product_id, 
                 'product_name' => $item->product_name, 
                 'quantity' => $item->quantity, 
                 'rate' => $item->rate, 
                 'tax' => $item->tax, 
                 'amount' => $item->amount, 
              ]);
              $total = $total + $item->amount;

            }
            $salesOrder->update(['total' => $total]);
          }

          
         $salesOrder = new SalesOrderResource($salesOrder);

          return response()->json(compact('salesOrder'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


     //This function Modifies a Sales Order
    public function editSalesOrder(Request $request){

      try{

        $validator = Validator::make($request->all() , [
            'salesOrderID'  => 'integer|required',
            'customer_name'  => 'string|nullable',
            'sales_order'  => 'string|nullable',
            'reference'  => 'string|nullable',
            'sales_date'  => 'date|nullable',
            'expected_shipment_date'  => 'date|nullable',
            'payment_term'  => 'string|nullable',
            'delivery_method'  => 'string|nullable',
            'customer_note'  => 'string|nullable',
            'sales_person'  => 'string|nullable',
            'items'  => 'nullable',
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
          $salesOrder = SalesOrder::find($request->input('salesOrderID'));
          $salesOrder_bk = $salesOrder;
          if (!is_null($salesOrder)) {
            if ($request->filled('sales_date')) {
              $sales_date = Carbon::createFromFormat('Y-m-d', $request->input('sales_date'));
            }

            if ($request->filled('expected_shipment_date')) {
              $expected_shipment_date = Carbon::createFromFormat('Y-m-d', $request->input('expected_shipment_date'));
            }
              $salesOrder->update(["customer_id" => $request->filled('customer_id') ? $request->input('customer_id') : $customer->customer_id,
                'customer_name' => $request->filled('customer_name') ? $request->input('customer_name') : $customer->customer_name, 
                'sales_order' => $request->filled('sales_order') ? $request->input('sales_order') : $customer->sales_order, 
                'reference' => $request->filled('reference') ? $request->input('reference') : $customer->reference, 
                'sales_date' => $request->filled('sales_date') ? $sales_date : $customer->sales_date, 
                'expected_shipment_date' => $request->filled('expected_shipment_date') ? $expected_shipment_date : $customer->expected_shipment_date, 
                'payment_term' => $request->filled('payment_term') ? $request->input('payment_term') : $customer->payment_term, 
                'delivery_method' => $request->filled('delivery_method') ? $request->input('delivery_method') : $customer->delivery_method, 
                'customer_note' => $request->filled('customer_note') ? $request->input('customer_note') : $customer->customer_note, 
                'sales_person' => $request->filled('sales_person') ? $request->input('sales_person') : $customer->sales_person, 
              ]);


              if ($request->filled('items')) {
                SalesOrderItem::where('sales_order_id', $salesOrder->id)->delete();
                $itemType = gettype($request->input('items'));
                if ($itemType == 'array') {
                  $items = $request->input('items');
                }else{
                  $items = json_decode($request->input('items'));
                }
                $total = 0;
                foreach ($items as $item) {
                  if ($itemType == 'array') {
                    $item = json_decode($item);
                  }
                  SalesOrderItem::create([
                     'sales_order_id' => $salesOrder->id, 
                     'product_id' => $item->product_id, 
                     'product_name' => $item->product_name, 
                     'quantity' => $item->quantity, 
                     'rate' => $item->rate, 
                     'tax' => $item->tax, 
                     'amount' => $item->amount, 
                  ]);
                  $total = $total + $item->amount;

                }
                $salesOrder->update(['total' => $total]);
              }

              
              $this->transLogUtil->logAuditTrail($user->id, $request->ip(), 'salesOrder Modification', $salesOrder_bk, $salesOrder);

            }
            $salesOrder = new SalesOrderResource($salesOrder);

            return response()->json(compact('salesOrder'),201);
          
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }
    
    //This returns an org's Sales Orders
    public function getSalesOrders(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          
          $salesOrders = SalesOrder::where('organization_id', $user->organization_id)->orderBy('id', 'desc')->paginate(30);
          
          $salesOrders = SalesOrderResource::collection($salesOrders);
          
          return response()->json(compact('salesOrders'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   //This returns a Sales Order
    public function findSalesOrder(Request $request){
       try{
        $validator = Validator::make($request->all() , [
            'salesOrderID'  => 'integer|required',
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
        
        $salesOrder = SalesOrder::find($request->input('salesOrderID'));
        if (!is_null($salesOrder)) {
          
          $salesOrder = new SalesOrderResource($salesOrder);
          return response()->json(compact('salesOrder'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'salesOrder not found', "ResponseMessage" => 'salesOrder not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   
    
    //This function deletes a salesOrder
    public function deleteSalesOrder(Request $request){
      try{
        $validator = Validator::make($request->all() , [
            'salesOrderID'  => 'integer|required',
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
          $salesOrder = SalesOrder::find($request->input('salesOrderID'));
          if (!is_null($salesOrder)) {
            $salesOrder->delete();
            return response()->json(["ResponseStatus" => "Successful", 'Detail' => 'salesOrder deleted successfully', "ResponseMessage" => 'salesOrder deleted successfully', "ResponseCode" => 201],201);
          }
          return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'salesOrder not found', "ResponseMessage" => 'salesOrder not found', "ResponseCode" => 401],401);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This Adds a Payment
    public function addPayment(Request $request){
      try{

        $validator = Validator::make($request->all() , [
            'credit'  => 'numeric|nullable',
            'discount'  => 'numeric|nullable',
            'tips'  => 'numeric|nullable',
            'tax'  => 'numeric|nullable',
            'sub_total'  => 'numeric|nullable',
            'total'  => 'numeric|nullable',
            'currency'  => 'string|required',
            'payment_mode'  => 'string|required',
            'items'  => 'required',
        
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
         
          $payment = Payment::create([
                "credit" => $request->input('credit'),
                "discount" => $request->input('discount'),
                'tips' => $request->input('tips'), 
                'tax' => $request->input('tax'), 
                'sub_total' => $request->input('sub_total'), 
                'total' => $request->input('total'), 
                'currency' => $request->input('currency'), 
                'payment_mode' => $request->input('payment_mode'), 
                'created_by_id' => $user->id, 
                'organization_id' => $user->organization_id,
              ]);
         

          if (!is_null($payment) && $request->filled('items')) {
            $itemType = gettype($request->input('items'));
            if ($itemType == 'array') {
              $items = $request->input('items');
            }else{
              $items = json_decode($request->input('items'));
            }
            
            foreach ($items as $item) {
              if ($itemType == 'array') {
                $item = json_decode($item);
              }
             
              PaymentItem::create([
                 'payment_id' => $payment->id, 
                 'product_id' => $item->product_id, 
                 'product_name' => $item->product_name, 
                 'quantity' => $item->quantity, 
                 'rate' => $item->rate, 
                 'total_cost' => $item->total_cost, 
                 'currency' => $item->currency, 
              ]);
              

            }
            
          }

          
         $payment = new PaymentResource($payment);

          return response()->json(compact('payment'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }


    //This returns a Payment
    public function findPayment(Request $request){
       try{
        $validator = Validator::make($request->all() , [
            'paymentID'  => 'integer|required',
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
        
        $payment = Payment::find($request->input('paymentID'));
        if (!is_null($payment)) {
          
          $payment = new PaymentResource($payment);
          return response()->json(compact('payment'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'payment not found', "ResponseMessage" => 'payment not found', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

   


     //This returns an org's Payments
    public function getPayments(Request $request){
      try{
        $user = $this->getAuthUser($request);
        if (!$user) {
           return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'User not found.', "ResponseMessage" => "User not found.", "ResponseCode" => 401], 401);
        }
        if ($user->account_type != '') {
          
          $payments = Payment::where('organization_id', $user->organization_id)->orderBy('id', 'desc')->paginate(30);
          
          $payments = PaymentResource::collection($payments);
          
          return response()->json(compact('payments'),201);
        }
        return response()->json(["ResponseStatus" => "Unsuccessful", 'Detail' => 'You are not authorized to perform this function.', "ResponseMessage" => 'You are not authorized to perform this function.', "ResponseCode" => 401],401);
        
      }catch(Exception $e) {
        return response()->json(["ResponseStatus" => "Unsuccessful", "ResponseCode" => 500, 'Detail' => $e->getMessage(), "ResponseMessage" => 'Something went wrong.'],500);
      }
    }

 


}

