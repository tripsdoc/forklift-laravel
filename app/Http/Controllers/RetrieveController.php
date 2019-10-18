<?php

namespace App\Http\Controllers;

use Storage;
use DB;
use Response;
use Illuminate\Http\Request;

class RetrieveController extends Controller
{
    function getDeliveryNotes() {
        //Get delivery notes by selected warehouse
        if(isset($_GET['warehouse'])) {
            $result = DB::table('HSC_InventoryPallet AS IP')
            ->join('HSC_InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
            ->select(DB::raw('ROW_NUMBER() OVER(ORDER BY IP.DN) AS SN, IP.DN, SUM(IB.Quantity) Qty, IP.DeliveryID, IP.CurrentLocation'))
            ->whereNotNull('IP.DN')
            ->where('IP.CurrentLocation', '=', $_GET['warehouse'])
            ->groupBy('IP.DN', 'IP.DeliveryID', 'IP.CurrentLocation')
            ->get();
        } else {
        //Get all delivery notes
            $result = DB::table('HSC_InventoryPallet AS IP')
            ->join('HSC_InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                ->from('HSC_InventoryPallet AS IP1')
                ->join('TagLocationLatest AS TL', 'IP1.Tag', '=', 'TL.Id')
                ->where('IP1.DelStatus', '=', 'N')
                ->where('IP1.DN', '>', 0)
                ->whereRaw("IP1.Tag <> ''")
                ->where(function($query){
                    $query->where('TL.Zones', 'like', '%"name": "110"%')
                    ->orWhere('TL.Zones', 'like', '%"name": "108"%')
                    ->orWhere('TL.Zones', 'like', '%"name": "109"%');
                })
                ->whereColumn('IP1.DeliveryID', 'IP.DeliveryID');
            })
            ->whereNotNull('IP.DN')
            ->where('IP.DelStatus', '=', 'N')
            ->where('IB.DelStatus', '=', 'N')
            ->groupBy('IP.DN', 'IP.DeliveryID', 'IP.CurrentLocation')
            ->select(DB::raw('ROW_NUMBER() OVER(ORDER BY IP.DN) as SN'), 'IP.DN', DB::raw('SUM(IB.Quantity) Qty'), 'IP.DeliveryID', 'IP.CurrentLocation')->get();
        }
        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['total'] = count($result);
        $response['data'] = $result;
        return response($response);
    }

    function getTags() {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        //Get tags by using delivery notes
        $getwarehouse = $_GET['warehouse'];
        $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        if(isset($_GET['dn'])) {
            $reqdndata = explode(",", $_GET['dn']);
            $result = DB::table('HSC_InventoryPallet')
            ->whereIn('DeliveryID', $reqdndata)
            ->where('DelStatus','N')
            ->whereNotNull('tag')
            ->whereIn('CurrentLocation', $datawarehouse)
            ->select('Tag', 'DN')->get();
            Storage::put('file.txt', $url);
            Storage::put('dn.txt', $_GET['dn']);
        } else {
            $result = DB::table('HSC_InventoryPallet AS IP')
            ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
            ->where('IP.DelStatus', 'N')
            ->where('IP.DN', '>', 0)
            ->whereRaw("IP.Tag <> ''")
            ->where(function($query){
                $query->where('TL.Zones', 'like', '%"name": "110"%')
                ->orWhere('TL.Zones', 'like', '%"name": "108"%')
                ->orWhere('TL.Zones', 'like', '%"name": "109"%');
            })
            ->whereIn('IP.CurrentLocation', $datawarehouse)
            ->select('IP.Tag', 'IP.DN')->get();
            Storage::put('file.txt', $url);
        }
        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['data'] = $result;
        return response($response);
    }
}
