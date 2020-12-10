<?php

/*
Note :
    -JobInfo table is from HSC2012 Database
    -The rest tables from .env configuration database
*/

namespace App\Http\Controllers;

use Storage;
use DB;
use Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class StoreController extends Controller
{
    function getAllTags() {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $getwarehouse = $_GET['warehouse'];
        if($getwarehouse == "fullmap") {
            $datawarehouse = ['108', '109', '110'];
        }
        else if ($getwarehouse == "full_12x") {
            $datawarehouse = ['121', '122'];
        } else {
            $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        }
        //Get data from 2 databases, JobInfo Table is from HSC2012 Database
        $result = DB::table('HSC2012.dbo.JobInfo AS JI')
        ->join('HSC2012.dbo.ContainerInfo AS CI', 'JI.JobNumber', '=', 'CI.JobNumber')
        ->join('HSC2017.dbo.HSC_Inventory AS I', 'CI.Dummy', '=', 'I.CntrID')
        ->join('HSC2017.dbo.HSC_InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->join('ForkLiftJobsFilter AS JF', 'JF.TagID', '=', 'IP.Tag')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''");
        //->where('IP.isActivityForStuffing', 0);
        $result->Where(function($query) use($datawarehouse)
        {
            for($i=0;$i<count($datawarehouse);$i++){
                if($i == 0) {
                    $query->where('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '%');
                } else {
                    $query->orWhere('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '%');
                }
            }
        });
        //$result->whereIn('IP.CurrentLocation', $datawarehouse)
        $result->select(DB::raw("DISTINCT IP.Tag, I.POD, JI.ClientID, I.InventoryID, IP.CurrentLocation, CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'IMPORT' END TagColor"));
        $data = $result->get();
        Storage::put('logs/store/GetAllTags.txt', $datawarehouse);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }

    function getAllTagsByPOD(Request $request) {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $getwarehouse = $_GET['warehouse'];
        if($getwarehouse == "fullmap") {
            $datawarehouse = ['108', '109', '110'];
        }
        else if ($getwarehouse == "full_12x") {
            $datawarehouse = ['121', '122'];
        } else {
            $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        }
        $result = DB::table('HSC2012.dbo.JobInfo AS JI')
        ->join('HSC2012.dbo.ContainerInfo AS CI', 'JI.JobNumber', '=', 'CI.JobNumber')
        ->join('HSC2017.dbo.HSC_Inventory AS I', 'CI.Dummy', '=', 'I.CntrID')
        ->join('HSC2017.dbo.HSC_InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> '' ")
        ->where('JI.ClientID', $request->clientID)
        ->where('I.POD', $request->pod);
        //->where('IP.Tag', '<>', $request->tag);
        $result->Where(function($query) use($datawarehouse)
        {
            for($i=0;$i<count($datawarehouse);$i++){
                if($i == 0) {
                    $query->where('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '%');
                } else {
                    $query->orWhere('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '%');
                }
            }
        });
        //$result->whereIn('IP.CurrentLocation', $datawarehouse)
        $result->select('IP.Tag', 'I.POD', 'JI.ClientID', DB::raw("CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'IMPORT' END TagColor"));
        $data = $result->get();
        Storage::put('logs/store/GetAllTagsByPOD.txt', $url);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }

    function testRedis() {
        $rawdata = Redis::command('KEYS', ['*1389d75bf1ed*']);
        $data = end($rawdata);
        $datas = Redis::get($data);
        $response['status'] = ($datas != null || $datas != "");
        $response['data'] = json_decode($datas);
        return response($response);
    }

    function connectRedis() {
        try {
            $redis = new Redis();
            $redis->connection('192.168.14.88', 6379);
            $allKeys = $redis->keys('*');
            print_r($allKeys);
        } catch(\Predis\Connection\ConnectionException $e) {
            return response('Error connect to redis');
        }
    }
    
}
