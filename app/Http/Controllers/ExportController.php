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
        $result = DB::table('Inventory AS I')
        ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 1)
        ->where(function($query){
            $query->where('TL.Zones', 'like', '%"name": "110"%')
            ->orWhere('TL.Zones', 'like', '%"name": "108"%')
            ->orWhere('TL.Zones', 'like', '%"name": "109"%')
            ->orWhere('TL.Zones', 'like', '%"name": "107"%')
            ->orWhere('TL.Zones', 'like', '%"name": "121"%')
            ->orWhere('TL.Zones', 'like', '%"name": "122"%');
        })
        ->whereIn('IP.CurrentLocation', $datawarehouse)
        ->select('IP.Tag', 'IP.ExpCntrID')
        ->get();
        Storage::put('logs/export/GetAllTags.txt', $url);
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
        $result = DB::table('Inventory AS I')
        ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->join('ContainerInfo AS CI', 'IP.ExpCntrID', '=', 'CI.Dummy')
        ->join('HSC2012.dbo.JobInfo AS JI', 'CI.JobNumber', '=', 'JI.JobNumber')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 1)
        ->where(function($query){
            $query->where('TL.Zones', 'like', '%"name": "110"%')
            ->orWhere('TL.Zones', 'like', '%"name": "108"%')
            ->orWhere('TL.Zones', 'like', '%"name": "109"%')
            ->orWhere('TL.Zones', 'like', '%"name": "107"%')
            ->orWhere('TL.Zones', 'like', '%"name": "121"%')
            ->orWhere('TL.Zones', 'like', '%"name": "122"%');
        })
        ->whereIn('IP.CurrentLocation', $datawarehouse)
        ->select('JI.POD')
        ->get();
        Storage::put('logs/export/GetAllPort.txt', $url);
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
        $result = DB::table('Inventory AS I')
        ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->join('ContainerInfo AS CI', 'IP.ExpCntrID', '=', 'CI.Dummy')
        ->join('HSC2012.dbo.JobInfo AS JI', 'CI.JobNumber', '=', 'JI.JobNumber')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 1)
        ->where('I.POD', '=', $pod)
        ->where(function($query){
            $query->where('TL.Zones', 'like', '%"name": "110"%')
            ->orWhere('TL.Zones', 'like', '%"name": "108"%')
            ->orWhere('TL.Zones', 'like', '%"name": "109"%')
            ->orWhere('TL.Zones', 'like', '%"name": "107"%')
            ->orWhere('TL.Zones', 'like', '%"name": "121"%')
            ->orWhere('TL.Zones', 'like', '%"name": "122"%');
        })
        ->whereIn('IP.CurrentLocation', $datawarehouse)
        ->select('IP.Tag', 'IP.ExpCntrID')
        ->get();
        Storage::put('logs/export/GetTagsByPort.txt', $url);
        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['data'] = $result;
        return response($response);
    }
}
