<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Inventory\Returnee;

class ReturnController extends Controller
{
    public function index()
    {
        //Add other checks in future
        return response()->json([
            'returnees' => Returnee::latest()->paginate(50),
        ]);
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
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
