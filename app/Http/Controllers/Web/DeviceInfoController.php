<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Request;
use App\DeviceInfo;
use View;
use DataTables;
use Validator;
use Redirect;
use Session;

class DeviceInfoController extends Controller
{
    function index() {
        return view('device/index');
    }

    function jsonAll() {
        if (request()->ajax()) {
            $data = DeviceInfo::all();
            return Datatables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        $btn = '<a href="../device/'. Crypt::encrypt($row->DeviceInfoId) . '" class="edit btn btn-primary btn-sm">View</a>
                                <a href="../device/' . Crypt::encrypt($row->DeviceInfoId) . '/edit" class="edit btn btn-warning btn-sm">Edit</a>
                                <form id="form-delete-' . $row->DeviceInfoId . '" style="display: inline-block;" class="pull-left" action="../device/' . Crypt::encrypt($row->DeviceInfoId) . '" method="POST">'
                                . csrf_field() .
                                    '<input type="hidden" name="_method" value="DELETE">
                                    <button class="jquery-postback btn btn-danger btn-sm">Delete</button>
                                </form>
                        ';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
      
        return view('device/index');
    }

    function debug($id) {
        $decryptId = Crypt::decrypt($id);
        $data = DeviceInfo::where('DeviceInfoId', $decryptId)->get();
        return response($data);
    }

    function show($id) {
        $decryptId = Crypt::decrypt($id);
        $data = DeviceInfo::where('DeviceInfoId', $decryptId)->first();
        return View::make('device/show')
        ->with('data', $data);
    }

    function create() {
        return View::make('device.create');
    }

    function store() {
        $rules = array(
            'devicename' => 'required',
            'serialnumber' => 'required'
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('device/create')
            ->withErrors($validator);
        } else {
            $device = new DeviceInfo;
            $device->devicename = Request::get('devicename');
            $device->serialnumber = Request::get('serialnumber');
            $device->warehouses = Request::get('warehouses');
            $device->isactive = Request::get('isactive');
            $device->tag = Request::get('tag');
            $device->timestamp = Request::get('timestamp');
            $device->lastused = Request::get('lastused');
            $device->ipaddress = Request::get('ipaddress');
            $device->save();

            return Redirect::to('device');
        }
    }

    function edit($id) {
        $decryptId = Crypt::decrypt($id);
        $data = DeviceInfo::find($decryptId);
        $cryptData = new \stdClass();
        $cryptData->DeviceInfoId = Crypt::encrypt($data->DeviceInfoId);
        $cryptData->DeviceName = $data->DeviceName;
        $cryptData->SerialNumber = $data->SerialNumber;
        $cryptData->WareHouses = $data->WareHouses;
        $cryptData->IsActive = $data->IsActive;
        $cryptData->tag = $data->tag;
        $cryptData->timestamp = $data->timestamp;
        $cryptData->lastUsed = $data->lastUsed;
        $cryptData->ipAddress = $data->ipAddress;
        return View::make('device.edit')
        ->with('data', $cryptData);
    }

    function update($id) {
        $decryptId = Crypt::decrypt($id);
        $rules = array(
            'devicename' => 'required',
            'serialnumber' => 'required'
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('device/' . $id . '/edit')
            ->withErrors($validator);
        } else {
            $forklift = DeviceInfo::find($decryptId);
            $forklift->devicename = Request::get('devicename');
            $forklift->serialnumber = Request::get('serialnumber');
            $forklift->warehouses = Request::get('warehouses');
            $forklift->isactive = Request::get('isactive');
            $forklift->tag = Request::get('tag');
            $forklift->timestamp = Request::get('timestamp');
            $forklift->lastused = Request::get('lastused');
            $forklift->ipaddress = Request::get('ipaddress');
            $forklift->save();

            Session::flash('message', 'Successfully updated Device!');
            return Redirect::to('device');
        }
    }

    function destroy($id) {
        $decryptId = Crypt::decrypt($id);
        $data = DeviceInfo::where('DeviceInfoId', $decryptId)->first();

        // redirect
        if ($data->delete()) {
            Session::flash('message', 'Successfully deleted the device!');
            return Redirect::to('device');
        }
        return response($data);
    }
}
