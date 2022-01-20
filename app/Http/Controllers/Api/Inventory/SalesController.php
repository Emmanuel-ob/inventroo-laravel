<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\Customer;
use App\Models\Inventory\Product;
use App\Models\Inventory\Sale;
use App\Models\Inventory\SaleItem;

use App\Models\User\Area;
use App\Models\User\State;

class SalesController extends Controller
{
    public function code()
    {
        $code = $_GET['code'];
        $sale = Sale::where('code', '=', $code)->with('sales_items')->first();

        return response()->json([
            'message' => 'Sale was found',
            'status' => 'success', 
            'sale' => $sale,
        ]);
    }

    public function index()
    {
        $q = $_GET['q'];

        if ($q == 'pending'){
            $sales = Sale::where('payment_status', '!=', 2);
        }
        else if ($q == 'completed'){
            $sales = Sale::where('payment_status', '=', 2);
        }
    }

    public function initials()
    {
        
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            /*'first_name' => 'required',
            'last_name' => 'required',
            'street' => 'sometimes',
            'street2' => 'sometimes',
            'city' => 'required',
            'state_id' => 'numeric',
            'area_id' => 'numeric',
            'phone' => 'numeric',
            'alt_phone' => 'nullable|numeric',
            'branch_id' => 'required|numeric',
            'sex' => 'required|string',
            'dob' => 'required|date',*/
        ]);

        $amount = 0.00;
        
        foreach ($request->input('items') as $item){ $amount += ($item['quantity'] * $item['price']);}
        
        $sales = Sale::create([
            'customer_id'       => $request->input('customer_id') ?? NULL, 
            'purchase_date'     => $request->input('purchase_date') ?? date('Y-m-d'), 
            'delivery_date'     => $request->input('delivery_date') ?? NULL, 
            'payment_status'    => $request->input('payment_status') ?? 1, 
            'requested_by'      => auth('api')->id(), 
            'amount'            => $amount, 
            'discount'          => $request->input('discount') ?? NULL, 
        ]);
        
        foreach ($request->input('items') as $item){
            $sales_item  = SaleItem::create([
                'sale_id'       => $sales->id,
                'price'         => $item['price'], 
                'quantity'      => $item['quantity'], 
                'item_ref_id'   => $item['id'], 
                'item_type'     => $item['type_ref']
            ]);
        }

        return response()->json([
            
            //This is the based on the service requested
            'message' => 'Your password has been changed successfully',
            'status' => 'success', 
            'sales' => $sales,
        ]);
    }

    public function show($id)
    {
        
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
