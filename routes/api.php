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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'recipes'], function () {
    Route::get('/', 'RecipesController@filter');
    // Route::post('/', 'RecipesController@create');

    // Route::get('/import', function () {
    //     $list = \DB::table('rec_urls')->where('imported', false)->get();
    //     return ['data' => $list];
    // });
});
