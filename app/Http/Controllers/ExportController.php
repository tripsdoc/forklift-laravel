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
        ->where('IP.isActivityForStuffing', 1);
        $result->Where(function($query) use($datawarehouse)
        {
            for($i=0;$i<count($datawarehouse);$i++){
                if($i == 0) {
                    $query->where('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '"%');
                } else {
                    $query->orWhere('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '"%');
                }
            }
        });
        //$result->whereIn('IP.CurrentLocation', $datawarehouse)
        $result->select('IP.Tag', 'IP.ExpCntrID', 'I.POD');
        $data = $result->get();
        Storage::put('logs/export/GetAllTags.txt', $url);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
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
        ->join('ContainerInfo AS CI', 'IP.ExpCntrID', '=', 'CI.Dummy')
        ->join('JobInfo AS JI', 'CI.JobNumber', '=', 'JI.JobNumber')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 1);
        $result->Where(function($query) use($datawarehouse)
        {
            for($i=0;$i<count($datawarehouse);$i++){
                if($i == 0) {
                    $query->where('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '"%');
                } else {
                    $query->orWhere('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '"%');
                }
            }
        });
        //$result->whereIn('IP.CurrentLocation', $datawarehouse)
        $result->select('JI.POD');
        Storage::put('logs/export/GetAllPort.txt', $url);
        $data = $result->get();
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['total'] = count($data);
        $response['data'] = $data;
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
        ->join('ContainerInfo AS CI', 'IP.ExpCntrID', '=', 'CI.Dummy')
        ->join('JobInfo AS JI', 'CI.JobNumber', '=', 'JI.JobNumber')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 1)
        ->where('I.POD', '=', $pod);
        foreach($datawarehouse as $warehousedata ) {
            $result->where('TL.Zones', 'like', '%"name": "' . $warehousedata . '"%');
        }
        //$result->whereIn('IP.CurrentLocation', $datawarehouse)
        $result->select('IP.Tag', 'IP.ExpCntrID');
        $data = $result->get();
        Storage::put('logs/export/GetTagsByPort.txt', $url);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }
}
