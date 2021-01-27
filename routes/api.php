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
  Route::get('retrieve/debug', 'RetrieveController@debugTag');
  Route::get('retrieve/deliverynotes', 'RetrieveController@getDeliveryNotes');
  Route::get('retrieve', 'RetrieveController@getTags');
  //Store Route
  Route::get('store/tag', 'StoreController@getAllTags');
  Route::post('store/tag', 'StoreController@getAllTagsByPOD');
  //Export Route
  Route::get('export/', 'ExportController@getAllTagsActivatedforStuffing');
  Route::get('export/pod/', 'ExportController@getAllPortActivatedforStuffing');
  Route::get('export/pod/{pod}', 'ExportController@getActivatedTagsByPort');
  Route::get('export/mqty', 'ExportController@getMQuantity');
});

//Debug
Route::group(['prefix' => 'debug'], function () {
  Route::get('retrieve', 'RetrieveController@debug');
  Route::get('onee', 'API\ParkController@debug');
  Route::get('container', 'ContainerAPIController@debug');
  Route::get('dummy/{dummy}', 'API\HistoryController@checkDummyisExist');
  Route::get('summary', 'API\HistoryController@debug');
  Route::get('cache', 'API\CacheController@debug');
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
Route::group(['prefix' => 'check'], function () {
  /* Route::post('container', 'API\ParkController@getDialogContainer');
  Route::post('dummy', 'API\ParkController@getDialogDummy'); */
  Route::post('number', 'API\ParkController@getLikeContainer');
});
Route::group(['prefix' => 'json'], function() {
  Route::get('container', 'API\ParkController@getContainerJson');
  Route::get('trailer', 'API\ParkController@getTrailerJson');
  Route::post('park', 'API\ParkController@getParkJson');
  Route::get('summary', 'API\HistoryController@getSummaryJson');
});

Route::get('temppark/today/{id}', 'API\ParkController@getCurrent');
Route::post('temppark/update', 'API\ParkController@editContainer');
Route::post('temppark/add', 'API\ParkController@bookPark');
Route::get('temppark/dummy', 'API\ParkController@getDummy');
Route::post('finish', 'API\ParkController@releasePark');
Route::post('cancel', 'API\ParkController@cancelPark');

Route::post('cache', 'API\CacheController@retrieveFile');

//Clerk API
Route::group(['prefix' => 'clerk'], function () {
  // Authentication
  Route::post('login', 'LoginController@loginClerk');
  Route::get('checkDevice', 'API\DeviceController@clerkCheckDevice');

  Route::get('user', 'LoginController@getUserData');
  // Global Data
  Route::get('global/checklist', 'GlobalController@getChecklist');

  //Unstuffing
  Route::get('unstuffing/currentContainer', 'UnstuffingController@getCurrentContainer');
  Route::get('unstuffing/detailimportsummary', 'UnstuffingController@getDetailImportsumary');
  Route::get('unstuffing/listimportsummary', 'UnstuffingController@detailimportsummary');
  Route::post('unstuffing/startjob', 'UnstuffingController@startJob');
  Route::post('unstuffing/revertJob', 'UnstuffingController@revertJob');
  Route::post('unstuffing/finishjob', 'UnstuffingController@finishJob');
  Route::get('unstuffing/joblist', 'UnstuffingController@getJobList');
  Route::get('unstuffing/checkLockedInventory', 'UnstuffingController@checkLockedInventory');
  
  Route::post('unstuffing/updateLockedInventory', 'UnstuffingController@updateLockedInventory');
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
  Route::get('unstuffing/getPhotoHBL', 'UnstuffingController@getPhotoHBL');
  Route::get('unstuffing/deleteHBLPhoto', 'UnstuffingController@deleteHBLPhoto');
  Route::get('unstuffing/checkInventory', 'UnstuffingController@checkInventory');
  Route::get('unstuffing/testSmb', 'UnstuffingController@testSmb');

  // Locate
  Route::get('locate/containerList', 'LocateController@getContainerList');
  Route::get('locate/containerList/tag', 'LocateController@getAllTagsByCN');
  Route::post('locate/containerList/update', 'LocateController@updateStuffing');
  Route::get('locate/debug', 'LocateController@debug');
  Route::get('locate/debugdummy', 'LocateController@getByDummy');

  // Devices

  // ReceivingController
  Route::get('receiving/checkLockedInventory', 'ReceivingController@checkLockedInventory');
  Route::post('receiving/updateLockedInventory', 'ReceivingController@updateLockedInventory');
  Route::get('receiving/summary', 'ReceivingController@getSummary');
  Route::get('receiving/list', 'ReceivingController@getReceivingList');
  Route::get('receiving/palletbreakdown', 'ReceivingController@getPalletBreakdown');
  Route::get('receiving/copypallet', 'ReceivingController@copyPallet');
  Route::get('receiving/deletepallet', 'ReceivingController@deletePallet');
  Route::get('receiving/addbreakdown', 'ReceivingController@addBreakdown');
  Route::get('receiving/deletebreakdown', 'ReceivingController@deleteBreakdown');
  Route::post('receiving/updatebreakdown', 'ReceivingController@updateBreakdown');
  Route::post('receiving/updatebreakdownLBH', 'ReceivingController@updateBreakdownLBH');
  Route::post('receiving/updatepallet', 'ReceivingController@updatePallet');
  Route::get('receiving/checkTag', 'ReceivingController@checkTag');
  Route::post('receiving/uploadphoto', 'ReceivingController@uploadBreakdownGallery');
  Route::get('receiving/deleteBreakdownPhoto', 'ReceivingController@deleteBreakdownPhoto');
  Route::get('receiving/getPhotoHBL', 'ReceivingController@getPhotoHBL');
  Route::get('receiving/deleteHBLPhoto', 'ReceivingController@deleteHBLPhoto');
  Route::get('receiving/checkInventory', 'ReceivingController@checkInventory');
  Route::get('receiving/patchStatus', 'ReceivingController@patchStatus');
  
  // Release
  Route::get('release/getClient', 'ReleaseController@getClient');
  Route::get('release/checkHBL', 'ReleaseController@checkHBL');
  Route::get('release/searchhbl', 'ReleaseController@searchHBL');
  Route::post('release/uploadphoto', 'ReleaseController@uploadBreakdownGallery');
  Route::get('release/unTick', 'ReleaseController@unTick');

  //Stuffing
  Route::get('stuffing/exportSummary', 'StuffingController@exportSummary');
  Route::get('stuffing/listexportsummary', 'StuffingController@detailExportSummary');
  
  Route::get('stuffing/detailExport', 'StuffingController@detailExport');
  Route::get('stuffing/currentContainer', 'StuffingController@getCurrentContainer');
  Route::post('stuffing/updateContainerInfo', 'StuffingController@updateContainerInfo');
  Route::post('stuffing/startjob', 'StuffingController@startJob');
  Route::post('stuffing/finishJob', 'StuffingController@finishJob');
  
  Route::get('stuffing/inventorylist', 'StuffingController@InventoryList');
  Route::post('stuffing/updateShutout', 'StuffingController@updateShutout');
  Route::get('stuffing/palletbreakdown', 'StuffingController@getPalletBreakdown');
  Route::get('stuffing/infoExport', 'StuffingController@infoExport');
  Route::get('stuffing/checkInventory', 'StuffingController@checkInventory');
  Route::get('stuffing/checkHBL', 'StuffingController@checkHBL');
  Route::get('stuffing/searchhbl', 'StuffingController@searchHBL');
  Route::get('stuffing/unTick', 'StuffingController@unTick');
  Route::post('stuffing/uploadphoto', 'StuffingController@uploadBreakdownGallery');
  Route::get('stuffing/deleteBreakdownPhoto', 'StuffingController@deleteBreakdownPhoto');
  Route::post('stuffing/updatebreakdown', 'StuffingController@updateBreakdown');
  Route::post('stuffing/updatebreakdownLBH', 'StuffingController@updateBreakdownLBH');
});
