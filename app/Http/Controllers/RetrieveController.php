<?php

namespace App\Http\Controllers;

use Storage;
use DB;
use Response;
use Illuminate\Http\Request;

class RetrieveController extends Controller
{
    function getDeliveryNotes() {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        //Get delivery notes by selected warehouse
        if(isset($_GET['warehouse'])) {
            $result = DB::table('HSC_InventoryPallet AS IP')
            ->join('HSC_InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
            ->select(DB::raw('ROW_NUMBER() OVER(ORDER BY IP.DN) AS SN, IP.DN, SUM(IB.Quantity) Qty, IP.DeliveryID'))
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                ->from('HSC_InventoryPallet AS IP1')
                ->join('TagLocationLatest AS TL', 'IP1.Tag', '=', 'TL.Id')
                ->where('IP1.DelStatus', '=', 'N')
                ->where('IP1.DN', '>', 0)
                ->whereRaw("IP1.Tag <> ''")
                ->where('TL.Zones', 'like', '%"name": "' . $_GET['warehouse'] . '"%')
                ->whereColumn('IP1.DeliveryID', 'IP.DeliveryID');
            })
            ->whereNotNull('IP.DN')
            ->where('IP.DelStatus', '=', 'N')
            ->where('IB.DelStatus', '=', 'N')
            //->where('IP.CurrentLocation', '=', $_GET['warehouse'])
            ->groupBy('IP.DN', 'IP.DeliveryID')
            ->get();
        } else {
        //Get all delivery notes
            $result = DB::table('HSC_InventoryPallet AS IP')
            ->join('HSC_InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
            ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                ->from('HSC_InventoryPallet AS IP1')
                ->join('TagLocationLatest AS TL1', 'IP1.Tag', '=', 'TL1.Id')
                ->where('IP1.DelStatus', '=', 'N')
                ->where('IP1.DN', '>', 0)
                ->whereRaw("IP1.Tag <> ''")
                ->where(function($query){
                    $query->where('TL1.Zones', 'like', '%"name": "110"%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "108"%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "109"%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "107"%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "121"%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "122"%');
                })
                ->whereColumn('IP1.DeliveryID', 'IP.DeliveryID');
            })
            ->whereNotNull('IP.DN')
            ->where('IP.DelStatus', '=', 'N')
            ->where('IB.DelStatus', '=', 'N')
            ->groupBy('IP.DN', 'IP.DeliveryID', 'IP.CurrentLocation', 'TL.Zones')
            ->select(DB::raw('ROW_NUMBER() OVER(ORDER BY IP.DN) as SN'), 'IP.DN', DB::raw('SUM(IB.Quantity) Qty'), 'IP.DeliveryID', 'IP.CurrentLocation', 'TL.Zones')->get();
        }
        Storage::put('retrieve.txt', $url);
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
            $result = DB::table('HSC_InventoryPallet AS IP')
            ->join('HSC_Inventory AS I', 'IP.InventoryID', '=', 'I.InventoryID')
            ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
            ->whereIn('IP.DeliveryID', $reqdndata)
            ->where('IP.DelStatus','N')
            ->whereNotNull('tag')
            //->whereIn('CurrentLocation', $datawarehouse)
            ->select('Tag', 'DN',
            DB::raw("CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'LOCAL' END TagColor"));
            Storage::put('Retrieve_GetTags(DeliveryID).txt', $_GET['dn']);
        } else {
            $result = DB::table('HSC_InventoryPallet AS IP')
            ->join('HSC_Inventory AS I', 'IP.InventoryID', '=', 'I.InventoryID')
            ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
            ->where('IP.DelStatus', 'N')
            ->where('IP.DN', '>', 0)
            ->whereRaw("IP.Tag <> ''");
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
            $result->select('IP.Tag', 'IP.DN', 
            DB::raw("CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'LOCAL' END TagColor"));
        }
        $data = $result->get();
        Storage::put('Retrieve_GetTags(URL).txt', $url);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }
}
