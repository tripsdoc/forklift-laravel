<?php

namespace App\Http\Controllers;

use DB;
use Response;
use Storage;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    function getAllTagsActivatedforStuffing() {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $getwarehouse = $_GET['warehouse'];
        if($getwarehouse == "fullmap") {
            $datawarehouse = ['108', '109', '110'];
        } else {
            $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        }
        $result = DB::table('HSC_Inventory AS I')
        ->join('HSC_InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 1)
        ->where(function($query){
            $query->where('TL.Zones', 'like', '%"name": "110"%')
            ->orWhere('TL.Zones', 'like', '%"name": "108"%')
            ->orWhere('TL.Zones', 'like', '%"name": "109"%');
        })
        /* ->whereIn('IP.Tag', [
            'd10d9b1b0b23',
            '200eaf299dcb',
            'e144cbc223dc'
        ]) */
        ->whereIn('IP.CurrentLocation', $datawarehouse)
        ->select('IP.Tag', 'IP.ExpCntrID')
        ->get();
        Storage::put('fileexport.txt', $url);
        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['data'] = $result;
        return response($response);
    }

    function getAllPortActivatedforStuffing() {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $getwarehouse = $_GET['warehouse'];
        if($getwarehouse == "fullmap") {
            $datawarehouse = ['108', '109', '110'];
        } else {
            $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        }
        $result = DB::table('HSC_Inventory AS I')
        ->join('HSC_InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 1)
        ->where(function($query){
            $query->where('TL.Zones', 'like', '%"name": "110"%')
            ->orWhere('TL.Zones', 'like', '%"name": "108"%')
            ->orWhere('TL.Zones', 'like', '%"name": "109"%');
        })
        ->whereIn('IP.CurrentLocation', $datawarehouse)
        ->select('I.POD')
        ->get();
        Storage::put('fileexport.txt', $url);
        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['total'] = count($result);
        $response['data'] = $result;
        return response($response);
    }
    
    function getActivatedTagsByPort($pod) {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $getwarehouse = $_GET['warehouse'];
        if($getwarehouse == "fullmap") {
            $datawarehouse = ['108', '109', '110'];
        } else {
            $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        }
        $result = DB::table('HSC_Inventory AS I')
        ->join('HSC_InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 1)
        ->where('I.POD', '=', $pod)
        ->where(function($query){
            $query->where('TL.Zones', 'like', '%"name": "110"%')
            ->orWhere('TL.Zones', 'like', '%"name": "108"%')
            ->orWhere('TL.Zones', 'like', '%"name": "109"%');
        })
        ->whereIn('IP.CurrentLocation', $datawarehouse)
        ->select('IP.Tag', 'IP.ExpCntrID')
        ->get();
        Storage::put('fileexport.txt', $url);
        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['data'] = $result;
        return response($response);
    }
}
