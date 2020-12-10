<?php
/* 
*/
namespace App\Http\Controllers;

use Storage;
use DB;
use Response;
use Illuminate\Http\Request;
use App\InventoryPallet;
use App\InventoryBreakdown;
use App\DeviceInfo;

class RetrieveController extends Controller
{
    function debugTag() {
        /* $result = DB::table('InventoryPallet AS IP')
        ->join('InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
        ->whereExists(function($query) {
            $query->select(DB::raw(1))
                ->from('InventoryPallet AS IP1')
                ->join('TagLocationLatest AS TL', 'IP1.Tag', '=', 'TL.Id')
                ->where('IP1.DelStatus', '=', 'N')
                ->where('IP1.DN', '>', 0)
                ->whereRaw("IP1.Tag <> ''")
                ->where('TL.Zones', 'like', '%"name": "' . $_GET['warehouse'] . '%')
                ->whereColumn('IP1.DeliveryID', 'IP.DeliveryID');
        })
        ->where('IP.DN', 341)->get(); */
        $result = DB::table('InventoryPallet AS IP1')
        ->join('TagLocationLatest AS TL', 'IP1.Tag', '=', 'TL.Id')
        ->where('IP1.DelStatus', '=', 'N')
        ->where('IP1.DN', '>', 0)
        ->whereRaw("IP1.Tag <> ''")
        ->where('TL.Zones', 'like', '%"name": "' . $_GET['warehouse'] . '"%')
        ->get();
        /* $result = DB::table('TagLocationLatest')
        ->where('Id', '=', '192399c60764')->get(); */
        $response['data'] = $result;
        return response($response);
    }

    function debug() {
        $warehouse = "110";
        $data = DeviceInfo::limit(10)->get();
        $result = DB::table('HSC2017.dbo.IPS_DeviceInfo AS IP')
            
            ->select(DB::raw("TOP 10 *"))
            ->get();

        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['total'] = count($result);
        $response['data'] = $data;
        return response($response);
    }

    function getDeliveryNotes() {
        $mode = "";
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        //Get delivery notes by selected warehouse
        if(isset($_GET['warehouse'])) {
            $mode = "warehouse";
            $result = DB::table('HSC2017.dbo.HSC_InventoryPallet AS IP')
            ->join('HSC2017.dbo.HSC_InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
            ->join('HSC2017.dbo.HSC_DeliveryInfo AS DI', 'IP.DeliveryID', '=', 'DI.DeliveryID')
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                ->from('HSC2017.dbo.HSC_InventoryPallet AS IP1')
                ->join('HSC2017.dbo.HSC_DeliveryInfo AS DI1', 'IP1.DeliveryID', '=', 'DI1.DeliveryID')
                ->join('TagLocationLatest AS TL', 'IP1.Tag', '=', 'TL.Id')
                ->where('IP1.DelStatus', '=', 'N')
                ->where('DI1.DN', '>', 0)
                ->whereRaw("IP1.Tag <> ''")
                ->where('TL.Zones','like', '%"name": "' . $_GET['warehouse'] . '%')
                ->whereColumn('IP1.DeliveryID', 'IP.DeliveryID');
            })
            ->whereNotNull('DI.DN')
            ->where('IP.DelStatus', '=', 'N')
            ->where('IB.DelStatus', '=', 'N');
            $iddata = $result->pluck('IP.InventoryID');
            $data = $result->groupBy('DI.DN', 'IP.DeliveryID')
            ->select(DB::raw('ROW_NUMBER() OVER(ORDER BY DI.DN) AS SN, DI.DN, SUM(IB.Quantity) Qty, IP.DeliveryID'))
            ->get();
        } else {
        //Get all delivery notes
            $mode = "all";
            $result = DB::table('HSC2017.dbo.HSC_InventoryPallet AS IP')
            ->join('HSC2017.dbo.HSC_InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
            ->join('HSC2017.dbo.HSC_DeliveryInfo AS DI', 'IP.DeliveryID', '=', 'DI.DeliveryID')
            ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                ->from('HSC2017.dbo.HSC_InventoryPallet AS IP1')
                ->join('HSC2017.dbo.HSC_DeliveryInfo AS DI1', 'IP1.DeliveryID', '=', 'DI1.DeliveryID')
                ->join('TagLocationLatest AS TL1', 'IP1.Tag', '=', 'TL1.Id')
                ->where('IP1.DelStatus', '=', 'N')
                ->where('DI1.DN', '>', 0)
                ->whereRaw("IP1.Tag <> ''")
                ->where(function($query){
                    $query->where('TL1.CoordinateSystemName', 'like', '%108%')
                    ->orWhere('TL1.CoordinateSystemName', 'like', '%109%')
                    ->orWhere('TL1.CoordinateSystemName', 'like', '%110%')
                    ->orWhere('TL1.CoordinateSystemName', 'like', '%121%')
                    ->orWhere('TL1.CoordinateSystemName', 'like', '%122%')
                    ->orWhere('TL1.CoordinateSystemName', 'like', '%107%');
                })
                /* ->where(function($query){
                    $query->where('TL1.Zones', 'like', '%"name": "110%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "108%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "109%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "107%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "121%')
                    ->orWhere('TL1.Zones', 'like', '%"name": "122%');
                }) */
                ->whereColumn('IP1.DeliveryID', 'IP.DeliveryID');
            })
            ->whereNotNull('DI.DN')
            ->where('IP.DelStatus', '=', 'N')
            ->where('IB.DelStatus', '=', 'N');
            $iddata = $result->pluck('IP.InventoryID');
            $data = $result->groupBy('DI.DN', 'IP.DeliveryID', 'IP.CurrentLocation', 'TL.CoordinateSystemName', 'TL.Zones')
            ->select(DB::raw('ROW_NUMBER() OVER(ORDER BY DI.DN) as SN'), 'DI.DN', DB::raw('SUM(IB.Quantity) Qty'), 'IP.DeliveryID', 'IP.CurrentLocation', 'TL.CoordinateSystemName', 'TL.Zones')->get();
        }
        Storage::put('logs/retrieve/GetDeliveryNotes.txt', $url);
        
        $dataArray = array();
        $dataQty = $this->getSequence($iddata);
        foreach($data as $key => $datas) {
            $newdata = $this->formatData($datas, $dataQty, $mode);
            array_push($dataArray, $newdata);
        }
        
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['total'] = count($data);
        $response['data'] = $dataArray;
        return response($response);
    }

    function getTags() {
        $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        //Get tags by using delivery notes
        $getwarehouse = $_GET['warehouse'];
        $datawarehouse = array_map('trim', explode(",", $getwarehouse));
        if(isset($_GET['dn']) && $_GET['dn'] != "") {
            $reqdndata = array_map('trim', explode(",", $_GET['dn']));
            $result = DB::table('HSC2017.dbo.HSC_InventoryPallet AS IP')
            ->join('HSC2017.dbo.HSC_Inventory AS I', 'IP.InventoryID', '=', 'I.InventoryID')
            ->join('HSC2017.dbo.HSC_DeliveryInfo AS DI', 'DI.DeliveryID', '=', 'IP.DeliveryID')
            ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
            ->whereIn('DI.DN', $reqdndata)
            ->where('IP.DelStatus','N')
            ->whereNotNull('tag')
            ->select('Tag', 'DN', 
            DB::raw("CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'LOCAL' END TagColor"));
            Storage::put('logs/retrieve/GetTags(DeliveryID).txt', $_GET['dn']);
        } else {
            $result = DB::table('HSC2017.dbo.HSC_InventoryPallet AS IP')
            ->join('HSC2017.dbo.HSC_Inventory AS I', 'IP.InventoryID', '=', 'I.InventoryID')
            ->join('HSC2017.dbo.HSC_DeliveryInfo AS DI', 'IP.DeliveryID', '=', 'DI.DeliveryID')
            ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
            ->where('IP.DelStatus', 'N')
            ->where('DI.DN', '>', 0)
            ->whereRaw("IP.Tag <> ''");
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
            $result->select('IP.Tag', 'DI.DN', 
            DB::raw("CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'LOCAL' END TagColor"));
        }
        $data = $result->get();
        Storage::put('logs/retrieve/GetTags(URL).txt', $url);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }

    function getSequence($ids) {
        $result = DB::table('HSC2017.dbo.HSC_Inventory AS I')
        ->join('HSC2017.dbo.HSC_InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
        ->join('HSC2017.dbo.HSC_InventoryBreakdown AS IB', 'IP.InventoryPalletID', '=', 'IB.InventoryPalletID')
        ->join('HSC2017.dbo.HSC_DeliveryInfo AS DI', 'IP.DeliveryID', '=', 'DI.DeliveryID')
        ->where('I.DelStatus', '=', 'N')
        ->where('IP.DelStatus', '=', 'N')
        ->where('IB.DelStatus', '=', 'N')
        ->whereIn('I.InventoryID',$ids)
        ->select('DI.DN','IB.Quantity')
        ->get();
        return $result;
    }

    function formatData($datas, $dataQty, $mode) {
        $Qty = $dataQty->where('DN', $datas->DN)->flatten();
        $loopdata = new \stdClass();
        $loopdata->DN = $datas->DN;
        $loopdata->DeliveryID = $datas->DeliveryID;
        $loopdata->Qty = $datas->Qty;
        if($mode == "all") {
            $loopdata->CurrentLocation = $datas->CurrentLocation;
            $loopdata->CoordinateSystemName = $datas->CoordinateSystemName;
            $loopdata->Zones = $datas->Zones;
        }
        $loopdata->Sequence = $Qty;
        return $loopdata;
    }
}
