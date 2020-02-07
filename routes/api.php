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

Route::post('login', 'LoginController@login');

//Needed on Forklift
Route::get('device', 'API\DeviceController@getDeviceTag');
Route::post('device/register', 'API\DeviceController@registerDevice');

//Forklift API
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

//Debug
Route::group(['prefix' => 'debug'], function () {
  Route::get('onee', 'API\ParkController@debug');
  Route::get('container', 'ContainerAPIController@debug');
  Route::get('dummy/{dummy}', 'API\HistoryController@checkDummyisExist');
  Route::get('summary', 'API\HistoryController@debug');
});

Route::get('user', 'LoginController@getUserData');

Route::get('version', 'TagsController@getVersion');
Route::get('patch', 'API\DeviceController@getPatch');
Route::get('newpatch', 'API\DeviceController@getUpdate');
Route::get('newdiff', 'API\DeviceController@getDiff');
Route::get('redis', 'StoreController@getRedis');

Route::get('latestapk', 'TagsController@getUpdate');

Route::post('container', 'ContainerAPIController@getAll');
Route::get('container/{id}', 'ContainerAPIController@getOverview');

//Testing Tags Position
Route::get('qpe/getTagPosition', 'TagsController@getTagPosition');
Route::get('qpe/alltags', 'TagsController@getAllTags');

//Park
//Route::post('park', 'API\ParkController@getAllPark');
//Route::post('park/type/{type}', 'API\ParkController@getAllParkSpinner');
//Route::get('park/{park}', 'API\ParkController@detailPark');
//Route::post('park/place', 'API\ParkController@getPlace');
//Route::get('park/place/{place}', 'API\ParkController@getParkByPlace');

//Search
//Route::post('search/park', 'API\ParkController@getParkSearch');

//Route::post('temppark/user', 'API\ParkController@getAllOnGoingByUser');

//Route::post('summary', 'API\HistoryController@getAllSummary');

//Route::post('search/summary', 'API\HistoryController@getSummarySearch');
//Route::post('search/container', 'ContainerAPIController@getContainerSearch');

//Shifter API
Route::group(['prefix' => 'shifter'], function () {
  Route::post('login', 'LoginController@loginShifter');
  Route::post('assign', 'API\ParkController@assignContainerToPark');
  Route::post('change', 'API\ParkController@changePark');
  Route::post('remove', 'API\ParkController@removeContainer');
});
Route::get('trailerjson', 'API\ParkController@getTrailerJson');
Route::post('parkjson', 'API\ParkController@getParkJson');
Route::get('temppark/today/{id}', 'API\ParkController@getCurrent');
Route::post('temppark/update', 'API\ParkController@editContainer');
Route::post('temppark/add', 'API\ParkController@bookPark');
Route::get('temppark/dummy', 'API\ParkController@getDummy');
Route::post('finish', 'API\ParkController@releasePark');
Route::post('cancel', 'API\ParkController@cancelPark');
Route::post('summaryjson', 'API\HistoryController@getSummaryJson');
Route::post('cache', 'API\CacheController@retrieveFile');

//Clerk API
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
  Route::post('unstuffing/uploadPhotoHBL', 'UnstuffingController@uploadPhotoHBL');
  Route::get('unstuffing/palletbreakdown', 'UnstuffingController@getPalletBreakdown');
  Route::post('unstuffing/updatebaystevedore', 'UnstuffingController@updateBaySteveDore');
  Route::get('unstuffing/copypallet', 'UnstuffingController@copyPallet');
  Route::get('unstuffing/deletepallet', 'UnstuffingController@deletePallet');
  Route::get('unstuffing/addbreakdown', 'UnstuffingController@addBreakdown');
  Route::get('unstuffing/deletebreakdown', 'UnstuffingController@deleteBreakdown');
  Route::post('unstuffing/updatebreakdown', 'UnstuffingController@updateBreakdown');
  Route::post('unstuffing/updatebreakdownLBH', 'UnstuffingController@updateBreakdownLBH');
  Route::post('unstuffing/updatepallet', 'UnstuffingController@updatePallet');
  Route::get('unstuffing/checkTag', 'UnstuffingController@checkTag');
  Route::post('unstuffing/uploadphoto', 'UnstuffingController@uploadBreakdownGallery');
  Route::get('unstuffing/deleteBreakdownPhoto', 'UnstuffingController@deleteBreakdownPhoto');

  // Locate
  Route::get('locate/containerList', 'LocateController@getContainerList');
  Route::get('locate/containerList/tag', 'LocateController@getAllTagsByCN');

  // Devices

  // ReceivingController
  Route::get('receiving/summary', 'ReceivingController@getSummary');

});
