<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\History;
use App\TemporaryPark;
use App\ContainerView;
use App\ContainerInfo;
use App\Park;
use App\ShifterUser;
use DB;

class HistoryController extends Controller
{

    function checkDummyisExist($dummy) {
        $data = ContainerView::where('Dummy', '=', $dummy)->get();
        $response['isExist'] = !$data->isEmpty();
        $response['data'] = $data;
        return response($response);
    }

    function debug() {
        /* date_default_timezone_set('Asia/Singapore');
        $result = ContainerView::
        whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', ''])
        ->WhereIn('Dummy', function($query) {
            $query->select('Dummy')
            ->from('HSC2017Test_V2.dbo.HSC_OngoingPark');
        });
        ->where('Number', '=', '9934453');
        $data = $result->paginate(20);
        $dataArray = array();
        foreach($data->items() as $key => $datas) {
            $newdata = $this->formatContainer($datas);
            array_push($dataArray, $newdata);
        }
        $datapark = TemporaryPark::all();
        $response['date'] = date('Y-m-d H:i:s');
        $response['query'] = $result->toSql();
        $response['data'] = $dataArray; */
        $datawarehouse = array_map('trim', explode("/", "108/109/110"));
        $result = ContainerView::whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', '']);
        $result->Where(function($query) use($datawarehouse)
        {
            for($i=0;$i<count($datawarehouse);$i++){
                if($i == 0) {
                    $query->where('TruckTo', '=', $datawarehouse[$i]);
                } else {
                    $query->orWhere('TruckTo', '=', $datawarehouse[$i]);
                }
                $query->orWhere('TruckTo', '=', 'HSC');
            }
        });
        $result->groupBy('Prefix', 'Number')
        ->havingRaw('COUNT(*) > ?', [1]);
        $data = $result->select('Prefix','Number', DB::raw('COUNT(*) as Duplicate'))->get();
        $response['count'] = count($data);
        $response['data'] = $data;
        return response($response);
    }

    function getSummaryJson() {
        $result = ContainerView::
        whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', '']);
        $result->orderBy('ETA')
        ->orderBy('Client')
        ->orderBy('Prefix')
        ->orderBy('Number');
        $data = $result->get();
        $dataArray = array();
        foreach($data as $key => $datas) {
            $newdata = $this->formatContainer($datas);
            array_push($dataArray, $newdata);
        }
        $response['count'] = count($dataArray);
        $response['status'] = !$data->isEmpty();
        $response['data'] = $dataArray;
        return response($response);
    }

    function formatContainer($datas) {
        $loopdata = new \stdClass();
        $loopdata->VesselID = $datas->VesselID;
        $loopdata->VesselName = $datas->VesselName;
        $loopdata->InVoy = $datas->InVoy;
        $loopdata->OutVoy = $datas->OutVoy;
        $loopdata->ETA = $datas->ETA;
        $loopdata->COD = $datas->COD;
        $loopdata->Berth = $datas->Berth;
        $loopdata->ETD = $datas->ETD;
        $loopdata->ServiceRoute = $datas->ServiceRoute;
        $loopdata->Client = $datas->Client;
        $loopdata->TruckTo = $datas->TruckTo;
        $loopdata->ImportExport = $datas["Import/Export"];
        $loopdata->LDPOD = $datas["LD/POD"];
        $loopdata->DeliverTo = $datas->DeliverTo;
        $loopdata->Prefix = $datas->Prefix;
        $loopdata->Number = $datas->Number;
        $loopdata->Seal = $datas->Seal;
        $loopdata->Size = $datas->Size;
        $loopdata->Type = $datas->Type;
        $loopdata->Remarks = $datas->Remarks;
        $loopdata->Status = $datas->Status;
        $loopdata->DateOfStuffUnStuff = $datas["DateofStuf/Unstuf"];
        $loopdata->Dummy = $datas->Dummy;
        $loopdata->Expr1 = $datas->Expr1;
        $loopdata->Expr2 = $datas->Expr2;
        $loopdata->Expr3 = $datas->Expr3;
        $loopdata->Chassis = $datas->Chassis;

        $loopdata->TT = $datas->TT;
        $loopdata->Pkg = $datas->TotalPkgs;
        $loopdata->Yard = $datas->Yard;
        $loopdata->YardRemarks = $datas->YardRemarks;
        $loopdata->IE = $datas["Import/Export"];
        $ongoing = TemporaryPark::where('Dummy', '=', $datas->Dummy)->first();
        if (!empty($ongoing)) {
            $loopdata->Park = Park::find($ongoing->ParkingLot);
            $loopdata->ParkingLot = $ongoing->ParkingLot;
        } else {
            $checkPark = $this->getParkingLot($datas->Prefix, $datas->Number);
            if (!empty($checkPark)) {
                $checkStatus = "EMPTY/CREATED/STUFFED/SHIPPED/COMPLETED/CLOSED";
                if($datas["Import/Export"] == "Export") {
                    if(strpos($datas->YardRemarks, "RE-USE") && strpos($checkStatus, $datas->Status)) {
                        $loopdata->Park = Park::find($checkPark);
                        $loopdata->ParkingLot = $checkPark;
                    }
                } else {
                    $loopdata->Park = Park::find($checkPark);
                    $loopdata->ParkingLot = $checkPark;
                }
            }
        }
        $loopdata->Driver = $datas->Driver;
        $loopdata->parkIn = (!empty($datas->ETA)) ? date('d/m H:i', strtotime($datas->ETA)) : "";
        $loopdata->parkOut = $datas["LD/POD"];
        return $loopdata;
    }

    function getParkingLot($prefix, $number) {
        $result = DB::table('HSC2012.dbo.Onee AS IP')
        ->join('HSC2017Test_V2.dbo.HSC_OngoingPark AS IB', 'IP.Dummy', '=', 'IB.Dummy')
        ->where('Prefix', '=', $prefix)
        ->where('Number', '=', $number)
        ->groupBy('IP.Prefix', 'IP.Number', 'IP.Dummy', 'IB.ParkingLot')
        ->value('IB.ParkingLot');
        return $result;
    }
}
