<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Inventory\ServiceCategory;
use App\Models\Inventory\Customer;
use App\Models\Inventory\Service;

use App\Models\User\Area;
use App\Models\User\State;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::where('status', '!=', 'Deleted')->latest()->paginate(50);
        return response()->json([
            //'categories'    => ServiceCategory::latest()->get(),
            'services'      => $services,
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            //'code' => 'required|unique',
            'price'=> 'required|numeric',
            'cost' => 'required|numeric', 
        ]);

        $service = Service::create([
            'name' => $request->input('name'), 
            'code' => $request->input('code'), 
            'price' => $request->input('price'), 
            'cost' => $request->input('cost'), 
            'category_id' => $request->input('category_id') ?? 1, 
            'quantity' => $request->input('quantity') ?? 0, 
            'description' => $request->input('description') ?? ' ', 
            'brand' => $request->input('brand'), 
            'status' => $request->input('status') ?? 'Active', 
            'created_by' => auth('api')->id(),
        ]);
        
        $services = Service::where('status', '!=', 'Deleted')->with('category')->latest()->paginate(50);
        return response()->json([
            'categories' => ServiceCategory::latest()->get(),
            'service' => $service,
            'services' => $services,
        ]);
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'price'=> 'required|numeric',
            'cost' => 'required|numeric', 
        ]);

        $service = Service::find($id);

        $service->name = $request->input('name'); 
        $service->code = $request->input('code'); 
        $service->price = $request->input('price'); 
        $service->cost = $request->input('cost'); 
        $service->category_id = $request->input('category_id'); 
        $service->description = $request->input('description'); 
        $service->status = $request->input('status') ?? 'Active'; 
        $service->updated_by = auth('api')->id();
    
        $service->save();

        $services = Service::where('status', '!=', 'Deleted')->with('category')->latest()->paginate(50);
        return response()->json([
            'categories' => serviceCategory::latest()->get(),
            'service' => $service,
            'services' => $services,
        ]);
    }

    public function destroy($id)
    {
        $service = Service::find($id);

        $service->status = "Deleted";
        $service->save();

        $services = Service::where('status', '!=', 'Deleted')->with('category')->latest()->paginate(50);
        return response()->json([
            'categories' => ServiceCategory::latest()->get(),
            'services' => $services,
        ]);        
    }
}
