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
// phpinfo
Route::get('/info', function () {
   phpinfo();
});

// 微信服务器事件推送
Route::get('weixin/valid',"Weixin\WeixinController@valid");
Route::post('weixin/valid',"Weixin\WeixinController@wxEvent");
// 获取AccessToken
Route::get('weixin/atoken',"Weixin\WeixinController@atoken");
Route::get('/weixin/getAccessToken',"Weixin\WeixinController@getAccessToken");
// 创建自定义菜单
Route::get('/weixin/createMenu',"Weixin\WeixinController@createMenu");
// 群发消息
Route::get('/weixin/send',"Weixin\WeixinController@send");
// 微信支付
Route::get('/weixin/payTest',"Weixin\WeixinPayController@payTest");
Route::post('/weixin/payNotify',"Weixin\WeixinPayController@payNotify");