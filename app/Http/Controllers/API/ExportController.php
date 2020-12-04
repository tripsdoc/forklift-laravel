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
        }
        else if ($getwarehouse == "full_12x") {
            $datawarehouse = ['121', '122'];
        } else {
            $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        }
        $result = DB::table('Inventory AS I')
        ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->join('HSC2012.dbo.ContainerInfo AS CI', 'IP.ExpCntrID', '=', 'CI.Dummy')
        ->join('HSC2012.dbo.JobInfo AS JI', 'CI.JobNumber', '=', 'JI.JobNumber')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.ExpCntrID', '>', 0)
        ->where('IP.isActivityForStuffing', 1);
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
        $iddata = $result->pluck('IP.InventoryID');
        $result->groupBy('IP.InventoryID', 'IP.Tag', 'IP.ExpCntrID', 'JI.POD');
        //$result->whereIn('IP.CurrentLocation', $datawarehouse)
        $result->select('IP.InventoryID', 'IP.Tag', 'IP.ExpCntrID', 'JI.POD');
        $data = $result->get();

        $dataArray = array();
        $dataQty = $this->getQuantity($iddata);
        foreach($data as $key => $datas) {
            $newdata = $this->formatDataTag($datas, $dataQty);
            array_push($dataArray, $newdata);
        }

        Storage::put('logs/export/GetAllTags.txt', $url);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $dataArray;
        return response($response);
    }

    function getAllPortActivatedforStuffing() {
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
        $result = DB::table('Inventory AS I')
        ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->join('HSC2012.dbo.ContainerInfo AS CI', 'IP.ExpCntrID', '=', 'CI.Dummy')
        ->join('HSC2012.dbo.JobInfo AS JI', 'CI.JobNumber', '=', 'JI.JobNumber')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('IP.isActivityForStuffing', 1);
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
        $result->groupBy('JI.POD')->select('JI.POD');
        Storage::put('logs/export/GetAllPort.txt', $url);
        $data = $result->get();
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['total'] = count($data);
        $response['data'] = $data;
        return response($response);
    }

    function formatDataTag($datas, $dataQty) {
        $newQty = $dataQty->where('InventoryID', $datas->InventoryID)->pluck('Qty')->first();
        $loopdata = new \stdClass();
        $loopdata->InventoryID = $datas->InventoryID;
        $loopdata->Tag = $datas->Tag;
        $loopdata->ExpCntrID = $datas->ExpCntrID;
        $loopdata->POD = $datas->POD;
        $loopdata->Qty = $newQty;
        return $loopdata;
    }

    function getQuantity($ids) {
        $result = DB::table('Inventory AS I')
        ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->where('IB.DelStatus', '=', 'N')
        ->whereIn('I.InventoryID',$ids)
        ->groupBy('I.InventoryID')
        ->select('I.InventoryID', DB::raw('SUM(IB.Quantity) AS Qty'))
        ->get();
        return $result;
    }
    
    function getActivatedTagsByPort($pod) {
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
        $result = DB::table('Inventory AS I')
        ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
        ->join('HSC2012.dbo.ContainerInfo AS CI', 'IP.ExpCntrID', '=', 'CI.Dummy')
        ->join('HSC2012.dbo.JobInfo AS JI', 'CI.JobNumber', '=', 'JI.JobNumber')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
		->where('IP.ExpCntrID', '>', 0)
        ->where('IP.isActivityForStuffing', 1)
        ->where('JI.POD', '=', $pod);
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
        $iddata = $result->pluck('IP.InventoryID');
        $result->groupBy('IP.InventoryID', 'IP.Tag', 'IP.ExpCntrID', 'JI.POD');
        //$result->whereIn('IP.CurrentLocation', $datawarehouse)
        $response['pod'] = $pod;
        $result->select('IP.InventoryID', 'IP.Tag', 'IP.ExpCntrID', 'JI.POD');
        $data = $result->get();

        $dataArray = array();
        $dataQty = $this->getQuantity($iddata);
        foreach($data as $key => $datas) {
            $newdata = $this->formatData($datas, $dataQty);
            array_push($dataArray, $newdata);
        }

        Storage::put('logs/export/GetTagsByPort.txt', $url);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $dataArray;
        return response($response);
    }

    function getMQuantity() {
        $pod = $_GET['pod'];
        $result = DB::table('Inventory AS I')
        ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
        ->join('HSC2012.dbo.ContainerInfo AS CI', 'IP.ExpCntrID', '=', 'CI.Dummy')
        ->join('HSC2012.dbo.JobInfo AS JI', 'CI.JobNumber', '=', 'JI.JobNumber')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->where('IB.DelStatus', '=', 'N')
        ->whereRaw("IP.Tag <> ''")
        ->where('JI.POD', '=', $pod);
        $iddata = $result->pluck('IP.InventoryID');
        
        $data = $result->groupBy('I.InventoryID', 'I.MQuantity')
        ->select('I.InventoryID','I.MQuantity')
        ->get();

        $dataArray = array();
        $dataQty = $this->getSequence($iddata);
        foreach($data as $key => $datas) {
            $newdata = $this->formatData($datas, $dataQty);
            array_push($dataArray, $newdata);
        }

        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $dataArray;
        return response($response);
    }

    function getSequence($ids) {
        $result = DB::table('Inventory AS I')
        ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
        ->join('HSC2012.dbo.ContainerInfo AS CI', 'IP.ExpCntrID', '=', 'CI.Dummy')
        ->join('HSC2012.dbo.JobInfo AS JI', 'CI.JobNumber', '=', 'JI.JobNumber')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->where('IB.DelStatus', '=', 'N')
        ->whereIn('I.InventoryID',$ids)
        ->select('I.InventoryID','IB.Quantity')
        ->get();
        return $result;
    }

    function formatData($datas, $dataQty) {
        $Qty = $dataQty->where('InventoryID', $datas->InventoryID)->pluck('Quantity');
        $loopdata = new \stdClass();
        $loopdata->Count = count($Qty);
        $loopdata->Sequence = $Qty;
        return $loopdata;
    }
}
