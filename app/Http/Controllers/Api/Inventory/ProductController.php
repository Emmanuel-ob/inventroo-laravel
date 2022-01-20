<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Inventory\Brand;
use App\Models\Inventory\Customer;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\PurchaseItem;
use App\Models\Inventory\SaleItem;

use App\Models\User\Area;
use App\Models\User\State;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::where('status', '!=', 'Deleted')->with('category')->orderBy('name', 'ASC')->paginate(50);
        return response()->json([
            'categories' => ProductCategory::latest()->get(),
            'products' => $products,
        ]);
    }

    public function initials()
    {
        $products = Product::where('status', '!=', 'Deleted')->with('category')->latest()->paginate(50);
        return response()->json([
            'brands'        => Brand::select('id as value', 'name as text')->get(),
            'categories'    => ProductCategory::select('id as value', 'name as text')->orderBy('name', 'ASC')->get(),
            'products'  => $products,
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

        $product = Product::create([
            'name' => $request->input('name'), 
            'code' => $request->input('code'), 
            'price' => $request->input('price'), 
            'cost' => $request->input('cost'), 
            'category_id' => $request->input('category_id'), 
            'quantity' => $request->input('quantity') ?? 0, 
            'description' => $request->input('description'), 
            'brand' => $request->input('brand'), 
            'status' => $request->input('status') ?? 'Active', 
            'created_by' => auth('api')->id() ?? 1,
        ]);
        
        $products = Product::where('status', '!=', 'Deleted')->with('category')->latest()->paginate(50);
        return response()->json([
            'categories' => ProductCategory::latest()->get(),
            'product' => $product,
            'products' => $products,
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'product'   => Product::where('id', '=', $id)->with('colours.colour')->with('sizes')->with('sizes')->first(),
            'sales'     => SaleItem::where('product_id', '=', $id)->with('sale.customer')->paginate(5),
            //'supplies'  => PurchaseItem::where('product_id', '=', $id)->with('vendor')->paginate(5),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'price'=> 'required|numeric',
            'cost' => 'required|numeric', 
        ]);

        $product = Product::find($id);

        $product->name = $request->input('name'); 
        $product->code = $request->input('code'); 
        $product->price = $request->input('price'); 
        $product->cost = $request->input('cost'); 
        $product->category_id = $request->input('category_id'); 
        $product->quantity = $request->input('quantity') ?? 0; 
        $product->description = $request->input('description'); 
        $product->brand = $request->input('brand'); 
        $product->status = $request->input('status') ?? 'Active'; 
        $product->created_by = auth('api')->id();
    
        $product->save();

        $products = Product::where('status', '!=', 'Deleted')->with('category')->latest()->paginate(50);
        return response()->json([
            'categories' => ProductCategory::latest()->get(),
            'product' => $product,
            'products' => $products,
        ]);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        $product->status = "Deleted";
        $product->save();

        $products = Product::where('status', '!=', 'Deleted')->with('category')->latest()->paginate(50);
        return response()->json([
            'categories' => ProductCategory::latest()->get(),
            'products' => $products,
        ]);        
    }
}
