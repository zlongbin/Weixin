<?php

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


Route::get('/info', function () {
   phpinfo();
});

Route::get('weixin/valid',"Weixin\WeixinController@valid");
Route::post('weixin/valid',"Weixin\WeixinController@wxEvent");


Route::get('/weixin/getAccessToken',"Weixin\WeixinController@getAccessToken");
