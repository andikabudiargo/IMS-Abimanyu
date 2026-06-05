<?php

use Illuminate\Support\Facades\Route;

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

// Endpoint Publik (Dapat diakses tanpa JWT, namun tetap dilindungi oleh pembatasan IP)

Route::group([
    'prefix' => 'auth',
    'middleware' => ['api.access'] 
], function () {
    Route::post('register', 'Auth\AuthController@register');
    Route::post('login', 'Auth\AuthController@login');
    /*
        Login body
        {
            "username":"oki",
            "password":"91278"
        }
    */
});

// Endpoint Terproteksi (Wajib JWT Token AND Lolos validasi IP & Status Whitelist User)
Route::group([
    'prefix' => 'auth',
    'middleware' => ['auth:api', 'api.access']
], function () {
    Route::post('logout', 'Auth\AuthController@logout');
    Route::post('refresh', 'Auth\AuthController@refresh');
    Route::post('me', 'Auth\AuthController@profile');
});

Route::group([
    'prefix' => 'v1',
    // 'middleware' => 'api',
    'middleware' => ['auth:api', 'api.access']
], function () {
    Route::get('listInvoice/{startDate}/{endDate}', 'ApiController@getReportByPeriod');
    // api/v1/listInvoice/2026-01-01/2026-01-01
    Route::get('users', 'ApiController@getAllUser'); 
});


// Route::group([
//     'middleware' => 'api',
//     'prefix' => 'auth'
// ], function ($router) {
//     Route::post('register', 'Auth\AuthController@register');
//     Route::post('login', 'Auth\AuthController@login');
//     Route::post('logout', 'Auth\AuthController@logout');
//     Route::post('refresh', 'Auth\AuthController@refresh');
//     Route::get('profile', 'Auth\AuthController@profile');

//     Route::get('users', 'Auth\AuthController@index'); 
// });
	

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::post('register', 'UserController@register');
// Route::post('login', 'UserController@login');

// Route::get('uoms',['as'=>'uom.list','uses'=>'UomController@list']);
// Route::get('users', 'UserController@detlistuser')->middleware('jwt.verify');
// Route::get('user', 'UserController@getAuthenticatedUser')->middleware('jwt.verify');
