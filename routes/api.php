<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/register', 'Api\UserController@register');
Route::post('/login', 'Api\UserController@login');

// accout verify
Route::get('/account/verify/{confirmation_code}', 'Api\UserController@verifyAccount');
// resend activation link
Route::post('/account/sendActivateEmail', 'Api\UserController@sendActivateEmail');
// send reset password mail
Route::post('/account/sendForgotEmail', 'Api\UserController@sendForgotEmail');
// confirm reset code
Route::post('/confirm_resetcode', 'Api\UserController@confirmResetCode');
// reset password
Route::post('/reset_password', 'Api\UserController@resetPassword');

// Market Chart Data
Route::get('/market/chart/data', 'Api\MarketController@getChartData');

Route::post('/order/getBuyOrders', 'Api\OrderController@getBuyOrders');
Route::post('/order/getSellOrders', 'Api\OrderController@getSellOrders');

Route::post('/order/getMarketTrades', 'Api\OrderController@getMarketTrades');

Route::get('/coin/getCoinPairs', 'Api\CoinController@getCoinPairs');
Route::get('/coin/coinsInfo', 'Api\CoinController@coinsInfo');

Route::group(['middleware' => ['jwt.auth']], function()
{
    Route::get('/account', 'Api\AccountController@getInfo');
    Route::post('/account/change_password', 'Api\AccountController@changePassword');
    Route::post('/account/profile', 'Api\AccountController@saveProfile');

    Route::post('/upload/file', 'Api\UploadController@upload');

    Route::post('/order/buyOrder', 'Api\OrderController@buyOrder');
    Route::post('/order/sellOrder', 'Api\OrderController@saleOrder');

    Route::post('/order/getMyTrades', 'Api\OrderController@getMyTrades');
    Route::post('/order/getMyOpenOrders', 'Api\OrderController@getMyOpenOrders');
    Route::post('/order/deleteOpenOrder', 'Api\OrderController@deleteOpenOrder');

    Route::post('/transaction/getSummary', 'Api\TransactionController@getSummary');

    // Admin Api
    Route::group(['middleware' => ['role:admin']], function () {
    });
});