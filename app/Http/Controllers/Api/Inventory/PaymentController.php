<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Inventory\Payment;
use App\Models\Inventory\PaymentOption;

class PaymentController extends Controller
{

    public function index()
    {
        return response()->json([
            'payments'        => Payment::latest()->paginate(50),
            'payment_options' => PaymentOption::all(),
        ]);
    }

    public function initials()
    {
        return response()->json([
            'payment_options'     => PaymentOption::all(),
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
