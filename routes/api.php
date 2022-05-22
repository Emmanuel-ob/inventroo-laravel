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
        Route::get('account/refreshToken', 'UserController@refreshToken');

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

        //Roles Routes
        Route::post('unit/add', 'ProductController@addUnit');
        Route::post('unit/modify', 'ProductController@editUnit');
        Route::get('unit/all', 'ProductController@getUnits');
        Route::get('unit/find', 'ProductController@findUnit');
        Route::get('unit/remove', 'ProductController@deleteUnit');

        //Roles Routes
        Route::post('brand/add', 'ProductController@addBrand');
        Route::post('brand/modify', 'ProductController@editBrand');
        Route::get('brand/all', 'ProductController@getBrands');
        Route::get('brand/find', 'ProductController@findBrand');
        Route::get('brand/remove', 'ProductController@deleteBrand');

        //Roles Routes
        Route::post('manufacturer/add', 'ProductController@addManufacturer');
        Route::post('manufacturer/modify', 'ProductController@editManufacturer');
        Route::get('manufacturer/all', 'ProductController@getManufacturers');
        Route::get('manufacturer/find', 'ProductController@findManufacturer');
        Route::get('manufacturer/remove', 'ProductController@deleteManufacturer');

        //Roles Routes
        Route::post('tax/add', 'ProductController@addTax');
        Route::post('tax/modify', 'ProductController@editTax');
        Route::get('tax/all', 'ProductController@getTaxes');
        Route::get('tax/find', 'ProductController@findTax');
        Route::get('tax/remove', 'ProductController@deleteTax');


        //Product Categories Routes
        Route::post('category/add', 'ProductController@addCategory');
        Route::post('category/modify', 'ProductController@editCategory');
        Route::get('category/all', 'ProductController@getCategories');
        Route::get('category/find', 'ProductController@findCategory');
        Route::get('category/remove', 'ProductController@deleteCategory');


        Route::post('product/add', 'ProductController@addProduct');
        Route::post('product/modify', 'ProductController@editProduct');
        Route::get('product/all', 'ProductController@getProducts');
        Route::get('product/find', 'ProductController@findProduct');
        Route::get('product/remove', 'ProductController@deleteProduct');
        Route::get('product/deactivatORactivate', 'ProductController@blockProduct');
        Route::get('product/productsByCategory', 'ProductController@getProductsByCategory');
        Route::get('product/searchProduct', 'ProductController@searchProduct');
        Route::get('product/scanProduct', 'ProductController@scanProduct');

        Route::post('productGroup/add', 'ProductController@addProductGroup');
        Route::post('productGroup/modify', 'ProductController@editProductGroup');
        Route::get('productGroup/all', 'ProductController@getProductGroups');
        Route::get('productGroup/find', 'ProductController@findProductGroup');
        Route::get('productGroup/remove', 'ProductController@deleteProductGroup');
        Route::get('productGroup/deactivatORactivate', 'ProductController@blockProductGroup');

        
        Route::post('inventoryAdj/add', 'InventoryController@addInventoryAdjustment');
        Route::post('inventoryAdj/modify', 'InventoryController@editInventoryAdjustment');
        Route::get('inventoryAdj/all', 'InventoryController@getInventoryAdjustments');
        Route::get('inventoryAdj/find', 'InventoryController@findInventoryAdjustment');
        Route::get('inventoryAdj/remove', 'InventoryController@deleteInventoryAdjustment');
        //Route::get('product/deactivatORactivate', 'ProductController@blockProduct');
        
        Route::post('priceList/add', 'InventoryController@addPriceList');
        Route::post('priceList/modify', 'InventoryController@editPriceList');
        Route::get('priceList/all', 'InventoryController@getPriceLists');
        Route::get('priceList/find', 'InventoryController@findPriceList');
        Route::get('priceList/remove', 'InventoryController@deletePriceList');

        //Route for customers
        Route::post('customer/add', 'CustomerController@addCustomer');
        Route::post('customer/modify', 'CustomerController@editCustomer');
        Route::get('customer/all', 'CustomerController@getCustomers');
        Route::get('customer/find', 'CustomerController@findCustomer');
        Route::get('customer/remove', 'CustomerController@deleteCustomer');

        
        //Route for salesOrder
        Route::post('salesOrder/add', 'SalesOrderController@addSalesOrder');
        Route::post('salesOrder/modify', 'SalesOrderController@editSalesOrder');
        Route::get('salesOrder/all', 'SalesOrderController@getSalesOrders');
        Route::get('salesOrder/find', 'SalesOrderController@findSalesOrder');
        Route::get('salesOrder/remove', 'SalesOrderController@deleteSalesOrder');

        Route::post('logout', 'UserController@logout');
        Route::post('refreshToken', 'UserController@refreshToken');
        Route::post('resetPassword', 'UserController@resetPassword');

     });


});