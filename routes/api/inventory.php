<?php 
use Illuminate\Support\Facades\Route;

Route::group(['prefix'=>'inventory'], function () {
    Route::get('/customers/initials',   'CustomerController@initials')->name('customers.initials');
    Route::get('/customers/search',     'CustomerController@search')->name('customers.search');
    Route::get('/payments/initials',    'PaymentController@initials')->name('payments.initials');
    Route::get('/products/initials',    'ProductController@initials')->name('products.initials');
    Route::get('/purchases/search',     'PurchaseController@search')->name('purchases.search');
    Route::get('/sales/code',           'SalesController@code')->name('sales.code');
    Route::get('/sales/initials',       'SalesController@initials')->name('sales.initials');
    Route::get('/vendors/search',       'VendorController@search')->name('vendors.search');

    Route::apiResources([
        'customers'     => 'CustomerController',
        'payments'      => 'PaymentController',
        'products'      => 'ProductController',
        'purchases'     => 'PurchaseController',
        'returns'       => 'ReturnController',
        'sales'         => 'SalesController',
        'services'      => 'ServiceController',
        'vendors'       => 'VendorController',
    ]);
});