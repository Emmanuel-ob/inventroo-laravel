<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Inventory\Customer;
use App\Models\Inventory\CustomerContactPerson;
use App\Models\User;
use App\Models\User\Area;
use App\Models\User\MerchantCurrency;
use App\Models\User\MerchantPaymentTerm;
use App\Models\User\MerchantTaxRate;
use App\Models\User\State;


class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('display_name', 'ASC')->with('area')->with('state')->paginate(20);
        return response()->json([
            'areas' => Area::all(),
            'customers' => $customers,
            'states' => State::with('areas')->get(), 
        ]);
    }

    public function initials()
    {
        $auth_id = auth('sanctum')->id() ?? 1;

        return response()->json([
            'currencies'    => MerchantCurrency::where('merchant_id', '=', $auth_id)->with('currency')->get(),
            'areas'         => Area::all(),
            'payment_terms' => MerchantPaymentTerm::where('merchant_id', '=', $auth_id)->with('currency')->get(),
            'states'        => State::with('areas')->get(),
            'tax_rates'     => MerchantTaxRate::where('merchant_id', '=', $auth_id)
        ]);
    }

    public function store(Request $request)
    {
        $auth_id = auth('sanctum')->id() ?? 1;
        $user = User::where('id', '=', $auth_id)->with('merchant')->first();

        $customer = Customer::create([
            'merchant_id'   => $user->merchant->id ?? 1, 
            'company_name'  => $request->input('company_name'), 
            'last_name'     => $request->input('last_name'), 
            'first_name'    => $request->input('first_name'), 
            'display_name'  => $request->input('display_name'), 
            'customer_type' => $request->input('customer_type'), 
            'image'         => $request->input('image'), 
            'work_phone'    => $request->input('phone'), 
            'email'         => $request->input('email'), 
            'website'       => $request->input('website'),
            'facebook'      => $request->input('facebook'),
            'twitter'       => $request->input('twitter'),
            'created_by'    => $user->id, 
            'updated_by'    => $user->id,
            
        ]);

        $new_customer = Customer::where('id', '=', $customer->id)->with('contact_persons')->first();
        $customers = Customer::latest()->paginate(20);
        return response()->json([
            'areas'     => Area::all(),
            'customer'  => $new_customer,
            'customers' => $customers,
            'states'    => State::with('areas')->get(), 
        ]);
    }

    public function search()
    {
        if ($search = \Request::get('q')){
            $customers = Customer::orderBy('display_name', 'ASC')->where(function($query) use ($search){
                $query->where('cp_first_name', 'LIKE', "%$search%")
                ->orWhere('cp_other_name', 'LIKE', "%$search%")
                ->orWhere('cp_last_name', 'LIKE', "%$search%")
                ->orWhere('display_name', 'LIKE', "%$search%")
                ->orWhere('company_name', 'LIKE', "%$search%")
                ->orWhere('email', 'LIKE', "%$search%")
                ->orWhere('phone', 'LIKE', "%$search%");
                })->paginate(20);
            }
        else{
            $customers = Customer::orderBy('display_name', 'ASC')->paginate(20);
        }
        
        return response()->json(['customers' => $customers,]);
    }

    public function show($id)
    {

        return response()->json([
            'areas'     => Area::all(),
            'customer'  => Customer::where('id', '=', $id)->with('state')->with('contact_persons')->first(),
            'states'    => State::with('areas')->get(), 
        ]);
    }

    public function update(Request $request, $id)
    {
        $area = Area::find($request->input('area_id'));
        $states = State::all(); 

        $state_id = $area ? $area->state_id :$states[$request->input('state_id')]->id; 

        $customer = Customer::find($id);
         
        $customer->customer_type = $request->input('customer_type'); 
        $customer->company_name  = $request->input('company_name'); 
        $customer->display_name  = $request->input('display_name');  
        $customer->first_name    = $request->input('first_name'); 
        $customer->last_name     = $request->input('last_name'); 
        $customer->image         = $request->input('image'); 
        $customer->phone         = $request->input('phone'); 
        $customer->dob           = $request->input('dob'); 
        $customer->email         = $request->input('email'); 
        $customer->website       = $request->input('website'); 
        $customer->created_by    = auth('sanctum')->id(); 
        $customer->updated_by    = $request->input('updated_by'); 

        $customer->zip_code      = $request->input('zip_code'); 
            
        $customer->cp_title      = $request->input('cp_title'); 
        $customer->cp_phone      = $request->input('cp_phone'); 
        $customer->cp_email      = $request->input('cp_email'); 

        
        $customer->save();
        

        return response()->json([
            'areas'     => Area::all(),
            'customer'  => Customer::where('id', '=', $id)->with('state')->with('contact_persons')->first(),
            'states'    => State::with('areas')->get(), 
        ]);
    }

    public function destroy($id)
    {
        //
    }
}
