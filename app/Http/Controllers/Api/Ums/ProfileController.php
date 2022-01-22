<?php

namespace App\Http\Controllers\Api\Ums;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\User\Area;
use App\Models\User\State;

class ProfileController extends Controller
{
    public function index()
    {
        return response()->json([
            'areas' => Area::all(),
            'states' => State::with('areas')->get(),
            'user' => User::where('id', '=', auth('sanctum')->id())->with('merchant.merchant_detail')->with('state')->with('area')->first(),
        ]); 
    }

    public function store(Request $request)
    {
        return response()->json([]);
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
