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
Route::resource('park', 'Web\ParkController');
Route::resource('history', 'Web\HistoryController');
Route::resource('temporary', 'Web\TemporaryParkController');
Route::resource('forklift', 'Web\ForkliftUserController');
Route::resource('device', 'Web\DeviceInfoController');
Route::resource('ips', 'Web\IPSUserController');
Route::resource('container', 'Web\ContainerController');
Route::resource('shifter', 'Web\ShifterController');

//Web Portal
Route::get('forkliftjson', 'Web\ForkliftUserController@jsonAll')->name('DataForklift');
Route::get('delete/forklift/{id}', 'Web\ForkliftUserController@destroy');

Route::get('devicejson', 'Web\DeviceInfoController@jsonAll')->name('DataDevice');
Route::get('delete/device/{id}', 'Web\DeviceInfoController@destroy');

Route::get('ipsjson', 'Web\IPSUserController@jsonAll')->name('DataIPS');

Route::post('containerjson', 'Web\ContainerController@jsonAll')->name('containerjson');

Route::get('debug/container', 'Web\ContainerController@debug');

Route::get('debugdevice/{id}', 'Web\DeviceInfoController@debug');

//Park Function
Route::get('parkData', 'Web\ParkController@getAllPark')->name('DataPark');
Route::get('historyData', 'Web\HistoryController@getAllHistory')->name('DataHistory');
Route::get('shifterData', 'Web\ShifterController@getAllShifter')->name('DataShifter');
Route::get('temporaryParkData', 'Web\TemporaryParkController@getAllTemporary')->name('TemporaryDataPark');
Route::get('temporaryParkDataByPark/{id}', 'Web\ParkController@getTempPark')->name('TemporaryDataByPark');

Route::get('calendar/{id}', 'Web\ParkController@getParkCalendar')->name('CalendarPark');

Route::get('finish/{id}', 'Web\TemporaryParkController@finishPark');
Route::get('cancel/{id}', 'Web\TemporaryParkController@cancelPark');

Route::get('today/history', 'Web\HistoryController@getTodayHistory');
Route::post('month/history', 'Web\HistoryController@getMonthHistory');
Route::post('year/history', 'Web\HistoryController@getYearHistory');
Route::post('custom/history', 'Web\HistoryController@getCustomHistory');

Route::get('register/device', 'Web\ShifterController@debug');

Route::get('app/', 'Web\AppController@index');
Route::get('app/detail/{app}', 'Web\AppController@detail');
Route::post('app/upload', 'Web\AppController@upload');
