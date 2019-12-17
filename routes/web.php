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
    return view('forklift/index');
});
Route::resource('forklift', 'Web\ForkliftUserController');
Route::get('forkliftjson', 'Web\ForkliftUserController@jsonAll')->name('DataForklift');

Route::resource('device', 'Web\DeviceInfoController');
Route::get('devicejson', 'Web\DeviceInfoController@jsonAll')->name('DataDevice');

Route::resource('ips', 'Web\IPSUserController');
Route::get('ipsjson', 'Web\IPSUserController@jsonAll')->name('DataIPS');

Route::get('debugdevice/{id}', 'Web\DeviceInfoController@debug');