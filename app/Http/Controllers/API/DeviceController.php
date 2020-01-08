<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DeviceInfo;

class DeviceController extends Controller
{
    function registerDevice(Request $request) {
        $data = DeviceInfo::where('SerialNumber', '=', $request->serial)->first();
        if(empty($data)) {
            $newddevice = new DeviceInfo();
            $newddevice->DeviceName = $request->name;
            $newddevice->SerialNumber = $request->serial;
            $newddevice->isActive = 0;
            if($request->tags != null && $request->tags != "") {
                $newddevice->tag = $request->tags;
            }
            if($newddevice->save()) {
                $response['status'] = TRUE;
                $response['tags'] = $newddevice;
            } else {
                $response['status'] = FALSE;
                $response['errMsg'] = "Cannot set device data";
            }
        } else {
            $response['status'] = TRUE;
            $response['tags'] = $data;
        }
        return response($response);
    }

    function getDeviceTag(Request $request) {
        $serial = $_GET['serial'];
        $result = DeviceInfo::where('SerialNumber', '=', $serial)->first();
        $response['status'] = (!empty($result));
        $response['tags'] = $result;
        return response($response);
    }
}
