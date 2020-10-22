<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\DeviceInfo;
use Storage;

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

    function getPatch() {
        $response['status'] = true;
        $response['version'] = 10101;
        $response['affected'] = [10100];
        return $response;
    }

    function clerkCheckDevice(Request $request) {
        $serial = $request->get('serial_number');
        Log::debug('DEBUG QUERY -  SERIAL: ' . $request->get('serial_number'));
        $result = DeviceInfo::where('SerialNumber', '=', $serial)->first();
        if ($request->get('lockedId')) {
            DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('lockedId'))->update(array(
                'LockedBy' => '',
                'LockedDt' => null,
                'LockedPC' => '',
            ));
        }
        $data    = array(
            'status' => $result ? true : false,
            'warehouses' => $result ? $result->WareHouses : 0,
            'isActive' => $result ? $result->IsActive == 1 ? true : false : false,
        );
        return response($data);
    }
    function getUpdate() {
        //return response()->file(public_path('latest.apk'));
        return Storage::download('out.apatch', 'out.apatch', ['Connection' => 'keep-alive']);
    }

    function getDiff() {
        //return response()->file(public_path('latest.apk'));
        return Storage::download('diff.dex', 'diff.dex', ['Connection' => 'keep-alive']);
    }
}
