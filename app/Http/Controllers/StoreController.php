<?php

namespace App\Http\Controllers;

use Storage;
use DB;
use Response;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    function getAllTags() {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $getwarehouse = $_GET['warehouse'];
        if($getwarehouse == "fullmap") {
            $datawarehouse = ['108', '109', '110'];
        } else if ($getwarehouse = "full_12x") {
            $datawarehouse = ['121', '122'];
        } else {
            $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        }
        $result = DB::table('JobInfo AS JI')
        ->join('ContainerInfo AS CI', 'JI.JobNumber', '=', 'CI.JobNumber')
        ->join('HSC_Inventory AS I', 'CI.Dummy', '=', 'I.CntrID')
        ->join('HSC_InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->join('ForkLiftJobsFilter AS JF', 'JF.TagID', '=', 'IP.Tag')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 0)
        ->where(function($query){
            $query->where('TL.Zones', 'like', '%"name": "110"%')
            ->orWhere('TL.Zones', 'like', '%"name": "108"%')
            ->orWhere('TL.Zones', 'like', '%"name": "109"%');
        })
        /* ->whereIn('IP.Tag', ['291e744f3695', 
        '3759d0ba05a6', 
        '3dd459dac965', 
        '528534894fce', 
        '549b892f464e', 
        '574bf60c569c', 
        '66bb4b5c20e4']) */
        ->whereIn('IP.CurrentLocation', $datawarehouse)
        ->select(DB::raw("DISTINCT IP.Tag, I.POD, JI.ClientID, I.InventoryID, IP.CurrentLocation, CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'IMPORT' END TagColor"))
        ->get();
        Storage::put('filestore.txt', $url);
        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['data'] = $result;
        return response($response);
    }

    function getAllTagsByPOD(Request $request) {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $getwarehouse = $_GET['warehouse'];
        if($getwarehouse == "fullmap") {
            $datawarehouse = ['108', '109', '110'];
        } else if ($getwarehouse = "full_12x") {
            $datawarehouse = ['121', '122'];
        } else {
            $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        }
        $result = DB::table('JobInfo AS JI')
        ->join('ContainerInfo AS CI', 'JI.JobNumber', '=', 'CI.JobNumber')
        ->join('HSC_Inventory AS I', 'CI.Dummy', '=', 'I.CntrID')
        ->join('HSC_InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> '' ")
        ->where('JI.ClientID', $request->clientID)
        ->where('I.POD', $request->pod)
        ->where('IP.Tag', '<>', $request->tag)
        ->where(function($query){
            $query->where('TL.Zones', 'like', '%"name": "110"%')
            ->orWhere('TL.Zones', 'like', '%"name": "108"%')
            ->orWhere('TL.Zones', 'like', '%"name": "109"%');
        })
        ->whereIn('IP.CurrentLocation', $datawarehouse)
        ->select('IP.Tag', 'I.POD', 'JI.ClientID', DB::raw("CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'IMPORT' END TagColor"))->get();
        Storage::put('filestore.txt', $url);
        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['data'] = $result;
        return response($response);
    }
}
