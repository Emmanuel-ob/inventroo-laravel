<?php

namespace App\Http\Controllers\Api\Ums;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\User\MerchantUser;
use App\Models\User\MerchantRole;
use App\Models\User\Role;

class MerchantController extends Controller
{
    public function index()
    {
        $id = auth('sanctum')->id();
        $user = User::where('id', '=', $id)->with('merchant')->first();

        return response()->json([
            'merchant_users' => MerchantUser::where('merchant_id', '=', $user->merchant->merchant_id)->with('user_details')->with('role_details')->paginate(10),
            //'merchant_roles' => MerchantRole::with('role_details')->get(),
            'roles' => Role::where('id', '>', 1)->get(),
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
