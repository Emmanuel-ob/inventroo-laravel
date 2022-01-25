<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::group([
    'namespace' => 'App\Http\Controllers\AppService',
    'prefix' => 'api',
   ], function() {


    Route::post('register', 'UserController@createAccount');
    Route::post('login', 'UserController@login');
    
    Route::post('password/resetByEmail', 'UserController@resetPasswordByEmail');
    Route::post('password/resetConfirm', 'UserController@resetPasswordConfirm');
    
    Route::get('account/confirm', 'UserController@confirmAccount');
    

   Route::group([
    'middleware' => 'AuthToken',
    
   ], function() {
        Route::post('logout', 'UserController@logout');
        Route::post('refreshToken', 'UserController@refreshToken');
        Route::post('resetPassword', 'UserController@resetPassword');

     });


});
