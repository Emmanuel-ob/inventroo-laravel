<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DB;
use App\Models\Inventory\Bundle;
use App\Models\Inventory\Product;
use App\Models\Inventory\Purchase;
use App\Models\Inventory\Service;
use App\Models\Inventory\Vendor;

class PurchaseController extends Controller
{
    public function index()
    {
        $products   = Product::select('name', 'price', 'code', 'category_id', 'id')->with('category');    
        $products   = $products->addSelect(DB::raw("'product' as type_ref"))->addSelect(DB::raw("'1' as quantity"));
        $services   = Service::select('name', 'price', 'code', 'category_id', 'id')->with('category');
        $services   = $services->addSelect(DB::raw("'service' as type_ref"))->addSelect(DB::raw("'1' as quantity"));
        $bundles    = Bundle::select('name', 'price', 'code', 'category_id', 'id')->with('category');
        $bundles    = $bundles->addSelect(DB::raw("'bundle' as type_ref"))->addSelect(DB::raw("'1' as quantity"));

        $items      = $bundles->union($services)->union($products)->orderBy('name', 'ASC')->paginate(15);
        return response()->json([
            'products'      => $items,
            'vendors'       => Vendor::all(),
        ]);
    }

    public function search()
    {
        if (is_null(\Request::get('q'))){
            $products   = Product::select('name', 'price', 'code', 'category_id', 'id')->with('category');    
            $products   = $products->addSelect(DB::raw("'product' as type_ref"))->addSelect(DB::raw("'1' as quantity"));
            $services   = Service::select('name', 'price', 'code', 'category_id', 'id')->with('category');
            $services   = $services->addSelect(DB::raw("'service' as type_ref"))->addSelect(DB::raw("'1' as quantity"));
            $bundles    = Bundle::select('name', 'price', 'code', 'category_id', 'id')->with('category');
            $bundles    = $bundles->addSelect(DB::raw("'bundle' as type_ref"))->addSelect(DB::raw("'1' as quantity"));

            $items      = $bundles->union($services)->union($products)->orderBy('name', 'ASC')->paginate(15);
        }
        else{
            
            $search = \Request::get('q');
            
            $products   = Product::select('name', 'price', 'code', 'category_id', 'id')->where('name', 'LIKE', '%'.$search.'%');   
            $products   = $products->addSelect(DB::raw("'product' as type_ref"))->addSelect(DB::raw("'1' as quantity"));
            $services   = Service::select('name', 'price', 'code', 'category_id', 'id')->where('name', 'LIKE', '%'.$search.'%');
            $services   = $services->addSelect(DB::raw("'service' as type_ref"))->addSelect(DB::raw("'1' as quantity"));
            $bundles    = Bundle::select('name', 'price', 'code', 'category_id', 'id')->where('name', 'LIKE', '%'.$search.'%');
            $bundles    = $bundles->addSelect(DB::raw("'bundle' as type_ref"))->addSelect(DB::raw("'1' as quantity"));

            $items      = $bundles->union($services)->union($products)->orderBy('name', 'ASC')->paginate(15);
        }
        
        return response()->json([
            'products'     => $items,
            'vendors'      => Vendor::all(),
        ]);
    }

    public function store(Request $request)
    {
        if (is_null($request->input('vendor_id'))){
            $vendor = Vendor::create([
                'name'              => $request->input('vendor.name'),
                'address'           => $request->input('vendor.address'), 
                'address2'          => $request->input('vendor.address2'), 
                'city'              => $request->input('vendor.city'), 
                'area_id'           => $request->input('vendor.area_id'), 
                'state_id'          => $request->input('vendor.state_id'), 
                'phone'             => $request->input('vendor.phone'), 
                'alt_phone'         => $request->input('vendor.alt_phone'), 
                'email'             => $request->input('vendor.email'),
                'website'           => $request->input('vendor.website'), 
                'contact_person'    => $request->input('vendor.contact_person'), 
                'cp_phone'          => $request->input('vendor.cp_phone'), 
                'cp_email'          => $request->input('vendor.cp_email'), 
                'created_by'        => auth('api')->id(), 
            ]);
        }
        else{
            $vendor = Vendor::find($request->input('vendor_id'));
        }

        $purchase = Purchase::create([
            'request_date'          => $request->input('request_date') ?? date('Y-m-d H:i:s'), 
            'approved_date'         => $request->input('approval_date') ?? NULL, 
            'issued_date'           => $request->input('issued_date') ?? NULL, 
            'delivery_date'         => $request->input('delivery_date') ?? NULL, 
            'payment_date'          => $request->input('payment_date') ?? NULL, 
            'payment_status'        => $request->input('payment_status') ?? 0, 
            'purchase_status'       => $request->input('purchase_status') ?? 0, 
            'vendor_id'             => $vendor->id ?? NULL, 
            'discount'              => $request->input('discount') ?? NULL, 
            'discount_amount'       => $request->input('discount_amount') ?? NULL, 
            'requested_by'          => $request->input('request_date') ?? auth('api')->id(),
        ]);
    }


    public function show($id)
    {
        /**/
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
