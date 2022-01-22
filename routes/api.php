<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::namespace('App\Http\Controllers\Api')->name('api.auth.')->group(base_path('routes/api/auth.php'));
Route::namespace('App\Http\Controllers\Api\Inventory')->name('api.inventory.')->group(base_path('routes/api/inventory.php'));
Route::namespace('App\Http\Controllers\Api\Ums')->name('api.ums.')->middleware('auth:sanctum')->group(base_path('routes/api/ums.php'));

Route::group(['namespace' =>'App\Http\Controllers\Api', 'name'=> 'api.', 'middleware'=>'auth:sanctum'] ,function () {
    Route::apiResources([
        '/dashboard' => 'DashboardController',
        //'/nok' => 'NOKController',
        //'/users' => 'UserController',
        //'/profile' => 'ProfileController',
    ]);
});