<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Models\User;
use App\Models\User\Merchant;
use App\Models\User\MerchantUser;

class AuthController extends Controller
{
    public function user_details(){
        $user = User::where('id', '=', auth('sanctum')->id)->first();

        if (!$user){
            return response()->json(['message' => 'Invalid Username/Password'], 401);    
        }
        else{
            return response()->json([ 'user'  => $user,], 201);
        }
    }

    public function login(Request $request){
        $fields = $request->validate([
            'email'         => 'required|string',
            'password'      => 'required|string',
        ]);

        $user = User::where('email', $fields['email'])->with('merchant.merchant_detail')->first();

        if (!$user || !Hash::check($fields['password'], $user->password)){
            return response()->json(['message' => 'Invalid Username/Password'], 401);    
        }
        else{
            $token = $user->createToken('myAppToken')->plainTextToken;

            return response()->json(['token' => $token, 'user'  => $user,], 201);
        }
    }

    public function logout(Request $request){
        auth('sanctum')->user()->tokens()->delete();

        return response()->json(['message' => 'Logged Out'], 200); 
        
    }

    public function register(Request $request){
        $fields = $request->validate([
            'first_name'    => 'required|string',
            'middle_name'   => 'nullable|string',
            'last_name'     => 'required|string',
            'business_name' => 'required|string',
            'email'         => 'required|string|unique:users,email',
            'password'      => 'required|string|confirmed',
        ]);

        $user = User::create([
            'first_name'    => $fields['first_name'],
            'middle_name'   => $fields['middle_name'],
            'last_name'     => $fields['last_name'],
            'email'         => $fields['email'],
            'password'      => bcrypt($fields['password']),
        ]);

        $token = $user->createToken('myAppToken')->plainTextToken;

        $merchant = Merchant::create([
            'name' => $fields['business_name'],
            'created_by' => $user->id,
        ]);

        $merchant_user = MerchantUser::create([
            'merchant_id' => $merchant->id,
            'user_id'     => $user->id,
            'role_id'     => 2,
            'created_by'  => $user->id,
        ]);

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }
}