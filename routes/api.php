<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::namespace('App\Http\Controllers\Api')->name('api.auth.')->group(base_path('routes/api/auth.php'));
// Route::namespace('App\Http\Controllers\Api\Inventory')->name('api.inventory.')->group(base_path('routes/api/inventory.php'));
// Route::namespace('App\Http\Controllers\Api\Ums')->name('api.ums.')->middleware('auth:sanctum')->group(base_path('routes/api/ums.php'));

// Route::group(['namespace' =>'App\Http\Controllers\Api', 'name'=> 'api.', 'middleware'=>'auth:sanctum'] ,function () {
//     Route::apiResources([
//         '/dashboard' => 'DashboardController',
//         //'/nok' => 'NOKController',
//         //'/users' => 'UserController',
//         //'/profile' => 'ProfileController',
//     ]);
// });

Route::group([
    'namespace' => 'App\Http\Controllers\AppService',
    //'prefix' => 'api',
   ], function() {


    Route::post('register', 'UserController@createAccount');
    Route::post('login', 'UserController@login');
    
    Route::post('password/resetByEmail', 'UserController@resetPasswordByEmail');
    Route::post('password/resetConfirm', 'UserController@resetPasswordConfirm');
    
    Route::get('account/confirm', 'UserController@confirmAccount');
    

   Route::group([
    'middleware' => 'AuthToken',
    
   ], function() {
        Route::post('account/updateOrgAccount', 'UserController@updateOrgAccount');

        //Users Routes
        Route::post('user/add', 'RoleController@addUser');
        Route::post('user/modify', 'RoleController@editUser');
        Route::get('user/all', 'RoleController@getUsers');
        Route::get('user/remove', 'RoleController@deleteUser');
        Route::get('user/deactivatORactivate', 'RoleController@blockUser');
        
        //Roles Routes
        Route::post('role/add', 'RoleController@addRole');
        Route::post('role/modify', 'RoleController@editRole');
        Route::get('role/all', 'RoleController@getRoles');
        Route::get('role/remove', 'RoleController@deleteRole');
        
        Route::post('logout', 'UserController@logout');
        Route::post('refreshToken', 'UserController@refreshToken');
        Route::post('resetPassword', 'UserController@resetPassword');

     });


});