<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Inventory\Vendor;

use App\Models\User\Area;
use App\Models\User\State;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::paginate(20);
        return response()->json([
            'areas' => Area::all(),
            'states' => State::with('areas')->get(), 
            'vendors' => $vendors,
        ]);        
    }

    public function search()
    {
        if ($search = \Request::get('q')){
            $vendors = Vendor::orderBy('name', 'ASC')->where(function($query) use ($search){
                $query->where('cp_first_name', 'LIKE', "%$search%")
                ->orWhere('cp_other_name', 'LIKE', "%$search%")
                ->orWhere('cp_last_name', 'LIKE', "%$search%")
                ->orWhere('name', 'LIKE', "%$search%")
                ->orWhere('company_name', 'LIKE', "%$search%")
                ->orWhere('email', 'LIKE', "%$search%")
                ->orWhere('phone', 'LIKE', "%$search%");
                })->paginate(20);
            }
        else{
            $vendors = Vendor::orderBy('name', 'ASC')->paginate(20);
        }
        
        return response()->json(['vendors' => $vendors,]);
    }

    public function store(Request $request)
    {
        $vendor = Vendor::create([
            'name' => $request->input('name'), 
            'address' => $request->input('street'), 
            'address2' => $request->input('street2'), 
            'city' => $request->input('city'), 
            'area_id' => $request->input('area_id'), 
            'state_id' => $request->input('state_id'), 
            'phone' => $request->input('phone'), 
            'alt_phone' => $request->input('alt_phone'), 
            'email' => $request->input('email'), 
            'website' => $request->input('website'), 
            'contact_person' => $request->input('contact_person'),
            'cp_phone' => $request->input('cp_phone'), 
            'cp_email' => $request->input('cp_email'),
            'cp_first_name' => $request->input('cp_first_name'),
            'cp_other_name' => $request->input('cp_other_name'),
            'cp_last_name' => $request->input('cp_last_name'),
            'created_by' => auth('api')->id(),
        ]);

        return response()->json([
            'vendors' => Vendor::all(), 
            'vendor' => $vendor,  
            'message' => 'Working',    
        ]);
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $vendor = Vendor::find($id);
        $vendor->name = $request->input('name'); 
        $vendor->address = $request->input('address'); 
        $vendor->address2 = $request->input('address2'); 
        $vendor->city = $request->input('city'); 
        $vendor->area_id = $request->input('area_id'); 
        $vendor->state_id = $request->input('state_id'); 
        $vendor->phone = $request->input('phone'); 
        $vendor->alt_phone = $request->input('alt_phone'); 
        $vendor->email = $request->input('email'); 
        $vendor->website = $request->input('website'); 
        $vendor->company_name = $request->input('company_name');
        $vendor->cp_first_name = $request->input('cp_first_name');
        $vendor->cp_other_name = $request->input('cp_other_name');
        $vendor->cp_last_name = $request->input('cp_last_name');
        $vendor->cp_phone = $request->input('cp_phone'); 
        $vendor->cp_email = $request->input('cp_email');
        $vendor->created_by = auth('api')->id();
        $vendor->save();
    }

    public function destroy($id)
    {
        //
    }
}
