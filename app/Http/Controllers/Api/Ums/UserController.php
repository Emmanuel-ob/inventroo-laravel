<?php

namespace App\Http\Controllers\Api\Ums;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\User\Area;
use App\Models\User\State;

class UserController extends Controller
{
    public function index()
    {
        //
    }

    public function password(Request $request)
    {
        //Validate the request
        
        //Get user
        $user = User::where('id', '=', auth('sanctum')->id())->first();
        
        //check if the old password matches the current password
        
        //if it fails return an error message
        
        //else update password and move on
        $user->password = bcrypt($request->input('password'));
        $user->save();

        return response()->json([
            'user' => User::where('id', '=', auth('sanctum')->id())->with('merchant.merchant_detail')->first(),
        ]); 
    }

    public function profile()
    {
        return response()->json([
            'areas' => Area::all(),
            'states' => State::with('areas')->get(),
            'user' => User::where('id', '=', auth('sanctum')->id())->with('merchant.merchant_detail')->with('state')->with('area')->first(),
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
