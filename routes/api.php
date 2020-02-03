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
Route::get('oneedebug', 'API\ParkController@debug');
Route::get('user', 'LoginController@getUserData');
Route::post('login', 'LoginController@login');

Route::get('device', 'API\DeviceController@getDeviceTag');
Route::post('device/register', 'API\DeviceController@registerDevice');
Route::get('version', 'TagsController@getVersion');
Route::get('patch', 'API\DeviceController@getPatch');
Route::get('newpatch', 'API\DeviceController@getUpdate');
Route::get('newdiff', 'API\DeviceController@getDiff');
Route::get('redis', 'StoreController@getRedis');

Route::get('latestapk', 'TagsController@getUpdate');

Route::post('container', 'ContainerAPIController@getAll');
Route::get('container/{id}', 'ContainerAPIController@getOverview');
Route::get('debug/container', 'ContainerAPIController@debug');

//Testing Tags Position
Route::get('qpe/getTagPosition', 'TagsController@getTagPosition');
ROute::get('qpe/alltags', 'TagsController@getAllTags');

Route::group(['prefix' => 'forklift'], function () {
  //Retrieve Route
  Route::get('retrieve/deliverynotes', 'RetrieveController@getDeliveryNotes');
  Route::get('retrieve', 'RetrieveController@getTags');
  //Store Route
  Route::get('store/tag', 'StoreController@getAllTags');
  Route::post('store/tag', 'StoreController@getAllTagsByPOD');
  //Export Route
  Route::get('export/', 'ExportController@getAllTagsActivatedforStuffing');
  Route::get('export/pod/', 'ExportController@getAllPortActivatedforStuffing');
  Route::get('export/pod/{pod}', 'ExportController@getActivatedTagsByPort');
});

//Park
Route::post('park', 'API\ParkController@getAllPark');
Route::post('parkjson', 'API\ParkController@getParkJson');
Route::post('park/type/{type}', 'API\ParkController@getAllParkSpinner');
Route::get('park/{park}', 'API\ParkController@detailPark');
Route::post('park/place', 'API\ParkController@getPlace');
Route::get('park/place/{place}', 'API\ParkController@getParkByPlace');

//Search
Route::post('search/park', 'API\ParkController@getParkSearch');
Route::post('search/container', 'ContainerAPIController@getContainerSearch');

Route::get('temppark/today/{id}', 'API\ParkController@getCurrent');
Route::post('temppark/update', 'API\ParkController@editContainer');
Route::post('temppark/add', 'API\ParkController@bookPark');

Route::post('temppark/user', 'API\ParkController@getAllOnGoingByUser');
Route::get('temppark/dummy', 'API\ParkController@getDummy');

Route::post('finish', 'API\ParkController@releasePark');
ROute::post('cancel', 'API\ParkController@cancelPark');

Route::get('debug/summary', 'API\HistoryController@debug');
Route::post('summary', 'API\HistoryController@getAllSummary');
Route::post('summaryjson', 'API\HistoryController@getSummaryJson');
Route::post('search/summary', 'API\HistoryController@getSummarySearch');

Route::post('cache', 'API\CacheController@retrieveFile');

Route::group(['prefix' => 'shifter'], function () {
  Route::post('login', 'LoginController@loginShifter');
  Route::post('assign', 'API\ParkController@assignContainerToPark');
  Route::post('change', 'API\ParkController@changePark');
  Route::post('remove', 'API\ParkController@removeContainer');
});

Route::group(['prefix' => 'clerk'], function () {
  // Authentication
  Route::post('login', 'LoginController@loginClerk');

  Route::get('user', 'LoginController@getUserData');
  // Global Data
  Route::get('global/checklist', 'GlobalController@getChecklist');

  //Unstuffing
  Route::get('unstuffing/currentContainer', 'UnstuffingController@getCurrentContainer');
  Route::get('unstuffing/detailimportsummary', 'UnstuffingController@getDetailImportsumary');
  Route::get('unstuffing/listimportsummary', 'UnstuffingController@detailimportsummary');
  Route::post('unstuffing/startjob', 'UnstuffingController@startJob');
  Route::post('unstuffing/finishjob', 'UnstuffingController@finishJob');
  Route::get('unstuffing/joblist', 'UnstuffingController@getJobList');
  Route::post('unstuffing/addoverlanded', 'UnstuffingController@addOverlanded');
  Route::get('unstuffing/palletbreakdown', 'UnstuffingController@getPalletBreakdown');
  Route::post('unstuffing/updatebaystevedore', 'UnstuffingController@updateBaySteveDore');
  Route::get('unstuffing/copypallet', 'UnstuffingController@copyPallet');
  Route::get('unstuffing/deletepallet', 'UnstuffingController@deletePallet');
  Route::get('unstuffing/addbreakdown', 'UnstuffingController@addBreakdown');
  Route::get('unstuffing/deletebreakdown', 'UnstuffingController@deleteBreakdown');
  Route::post('unstuffing/updatebreakdown', 'UnstuffingController@updateBreakdown');
  Route::post('unstuffing/updatebreakdownLBH', 'UnstuffingController@updateBreakdownLBH');
  Route::post('unstuffing/updatepallet', 'UnstuffingController@updatePallet');
  Route::post('unstuffing/checkTag', 'UnstuffingController@checkTag');
  Route::post('unstuffing/uploadphoto', 'UnstuffingController@uploadBreakdownGallery');
  Route::get('unstuffing/deleteBreakdownPhoto', 'UnstuffingController@deleteBreakdownPhoto');

  // Locate
  Route::get('locate/containerList', 'LocateController@getContainerList');
  Route::get('locate/containerList/tag', 'LocateController@getAllTagsByCN');

  // Devices

  // ReceivingController
  Route::get('receiving/summary', 'ReceivingController@getSummary');

});
