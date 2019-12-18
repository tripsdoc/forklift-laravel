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

Route::get('user', 'LoginController@getUserData');
Route::post('login', 'LoginController@login');

Route::get('device', 'TagsController@getDeviceTag');
Route::get('version', 'TagsController@getVersion');
Route::get('redis', 'StoreController@getRedis');

Route::get('latestapk', 'TagsController@getUpdate');

//Testing Tags Position
Route::get('qpe/getTagPosition', 'TagsController@getTagPosition');
ROute::get('qpe/alltags', 'TagsController@getAllTags');

//Retrieve Route
Route::get('forklift/retrieve/deliverynotes', 'RetrieveController@getDeliveryNotes');
Route::get('forklift/retrieve', 'RetrieveController@getTags');

//Store Route
Route::get('forklift/store/tag', 'StoreController@getAllTags');
Route::post('forklift/store/tag', 'StoreController@getAllTagsByPOD');

//Export Route
Route::get('forklift/export/', 'ExportController@getAllTagsActivatedforStuffing');
Route::get('forklift/export/pod/', 'ExportController@getAllPortActivatedforStuffing');
Route::get('forklift/export/pod/{pod}', 'ExportController@getActivatedTagsByPort');

Route::group(['prefix' => 'clerk'], function () {
  // Authentication
  Route::post('login', 'LoginController@loginClerk');

  Route::get('user', 'LoginController@getUserData');
  // Global Data
  Route::get('global/checklist', 'GlobalController@getChecklist');

  //Unstuffing
  Route::get('unstuffing/detailimportsummary', 'UnstuffingController@getDetailImportsumary');
  Route::get('unstuffing/joblist', 'UnstuffingController@getJobList');
  Route::get('unstuffing/palletbreakdown', 'UnstuffingController@getPalletBreakdown');
  Route::post('unstuffing/updatebaystevedore', 'UnstuffingController@updateBaySteveDore');
  Route::get('unstuffing/copypallet', 'UnstuffingController@copyPallet');
  Route::get('unstuffing/deletepallet', 'UnstuffingController@deletePallet');
  Route::get('unstuffing/addbreakdown', 'UnstuffingController@addBreakdown');
  Route::get('unstuffing/deletebreakdown', 'UnstuffingController@deleteBreakdown');
  Route::post('unstuffing/updatebreakdown', 'UnstuffingController@updateBreakdown');
  Route::post('unstuffing/updatebreakdownLBH', 'UnstuffingController@updateBreakdownLBH');
  Route::post('unstuffing/updatepallet', 'UnstuffingController@updatePallet');
  Route::post('unstuffing/uploadphoto', 'UnstuffingController@uploadBreakdownGallery');

  // Locate
  Route::get('locate/containerList', 'LocateController@getContainerList');
  Route::get('locate/containerList/tag', 'LocateController@getAllTagsByCN');

  // Devices

});