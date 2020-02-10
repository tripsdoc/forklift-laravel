<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\ContainerInfo;
use App\ContainerView;
use App\History;
use App\Park;
use App\TemporaryPark;
use App\Trailer;
use Carbon\Carbon;
use DataTables;
use Date;
use DB;
use View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/*

*/

class ParkController extends Controller
{

    function debug() {
        $data = ContainerView::
        whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', ''])
        ->paginate(20);
        return response($data);
    }

    function getDummy() {
        $parkid = $_GET['park'];
        $data = TemporaryPark::where('ParkingLot', '=', $parkid)->first();
        return (!empty($data)) ? $data->Dummy : null;
    }

    // -----------------------------------------  Picker Function -----------------------------------------------------------

    function getLikeContainer(Request $request) {
        $data = ContainerView::whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', ''])
        ->where('Number', 'like', '%' . $request->number . '%')->orderBy('ETA')->get();
        $dataArray = array();
        foreach($data as $key => $datas) {
            $newdata = $this->formatContainer($datas);
            array_push($dataArray, $newdata);
        }
        $response['status'] = !$data->isEmpty();
        $response['data'] = $dataArray;
        return response($response);
    }

    function getLikeTrailer(Request $request) {
        $data = Trailer::where('DelStatus', '=', 'N')
        ->where('TRTrailers', 'like', '%' . $request->trailer . '%')->get();
        $response['status'] = !$data->isEmpty();
        $response['data'] = $data;
        return response($response);
    }

    // -----------------------------------------  Park List Function -----------------------------------------------------------
    function getParkJson(Request $request) {
        $data = Park::all();
        $dataUser = $request->user;
        $dataArray = $this->convertData($data, $dataUser);
        $type = Park::groupBy('Type')->pluck('Type');
        $dataPlace = array();
        foreach ($type as $key => $datas) {
            $park = Park::where('Type', '=', $datas)->groupBy('place')->pluck('place');
            array_push($dataPlace, $park);
        }

        $response['status'] = !$data->isEmpty();
        $response['place'] = $dataPlace;
        $response['data'] = $dataArray;
        return response($response);
    }

    function getTrailerJson(Request $request) {
        $data = Trailer::all();
        $response['status'] = !$data->isEmpty();
        $response['data'] =  $data;
        return response($response);
    }

    function getContainerJson() {
        $data = ContainerView::
        whereNotNull('Status')
        ->whereNotIn('Status', ['NEW', 'NOMINATED', 'PROCESSED', ''])->get();
        $dataArray = array();
        foreach($data as $key => $datas) {
            $newdata = $this->formatContainer($datas);
            array_push($dataArray, $newdata);
        }
        $response['status'] = !$data->isEmpty();
        $response['data'] = $dataArray;
        return response($response);
    }

    // -------------------------------------------------------------------------------------------------------------------------
    
    function removeOldDummyFromOngoing($dummy) {
        $data = ContainerView::where('Dummy', '=', $dummy)->first();
        $check = ContainerView::where('Prefix', '=', $data->Prefix)->where('Number', '=', $data->Number)->get();
        foreach($check as $key => $datas) {
            $deletedata = TemporaryPark::where('Dummy', '=', $datas->Dummy)->delete();
        }
    }

    function assignContainerToPark(Request $request) {
        date_default_timezone_set('Asia/Singapore');
        $check = TemporaryPark::where('ParkingLot', '=', $request->park)->first();
        if($request->trailer != null) {
            $checkTrailer = TemporaryPark::where('trailer', '=', $request->trailer)->delete();
        }
        if($request->dummy != 0) {
            $DummyToAssign = $this->checkReUSE($request->dummy);
            $this->removeOldDummyFromOngoing($DummyToAssign);
        } else {
            $DummyToAssign = 0;
        }
        if(empty($check)) {
            $temp = new TemporaryPark();

            $temp->ParkingLot = $request->park;
            $temp->Dummy = $DummyToAssign;
            $temp->createdBy = $request->user;
            $temp->updatedDt = date('Y-m-d H:i:s');
            $temp->trailer = $request->trailer;
            if($temp->save()) {
                $response['status'] = TRUE;
                $response['data'] = $temp;
                $dataRedis = "1," . $request->park . "," .  $temp->Dummy;
                $this->broadcastRedis($dataRedis);
                return response($response);
            } else {
                $response['status'] = FALSE;
                $response['errMsg'] = "Server error, cannot asign data!";
                return response($response);
            }
        } else {
            $history = new History();
            $history->SetDt = $check->updatedDt;
            $history->UnSetDt = date('Y-m-d H:i:s');
            $history->ParkingLot = $check->ParkingLot;
            $history->Dummy = $check->Dummy;
            $history->trailer = $check->trailer;
            $history->createdBy = $request->user;

            if($history->save()){
                $check->Dummy = $DummyToAssign;
                $check->trailer = $request->trailer;
                $check->updatedBy = $request->user;
                $check->updatedDt = date('Y-m-d H:i:s');

                $check->save();
                $response['status'] = TRUE;
                $response['data'] = $check;
                $dataRedis = "1," . $request->park . "," .  $check->Dummy;
                $this->broadcastRedis($dataRedis);
                return response($response);
            } else {
                $response['status'] = FALSE;
                $response['errMsg'] = "Server error, cannot asign data!";
                return response($response);
            }
        }
    }

    function changePark(Request $request) {
        date_default_timezone_set('Asia/Singapore');
        $DummyToAssign = $this->checkReUSE($request->dummy);
        $DummyOngoing = $this->getOngoingDummy($request->dummy);
        $oldpark = TemporaryPark::where('Dummy', '=', $DummyOngoing)->first();
        $isParkAssign = TemporaryPark::where('ParkingLot', '=', $request->park)->first();
        if (!empty($oldpark)) {
            $oldlot = $oldpark->ParkingLot;
            $oldpark->delete();
            if (!empty($isParkAssign)) {
                $isParkAssign->delete();
                $history = new History();
                $history->SetDt = $isParkAssign->updatedDt;
                $history->UnSetDt = date('Y-m-d H:i:s');
                $history->ParkingLot = $isParkAssign->ParkingLot;
                $history->Dummy = $isParkAssign->Dummy;
                $history->trailer = $isParkAssign->trailer;
                $history->createdBy = $request->user;
                $history->save();
            }
            $newpark = new TemporaryPark();
            $newpark->ParkingLot = $request->park;
            $newpark->Dummy = $DummyToAssign;
            $newpark->createdBy = $request->user;
            $newpark->updatedDt = date('Y-m-d H:i:s');
            if($newpark->save()) {
                $response['status'] = TRUE;
                $response['data'] = $newpark;
                $dataOld = "0," . $oldlot . ",0";
                $dataRedis = "1," . $request->park . "," .  $newpark->Dummy;
                $this->broadcastRedis($dataRedis);
                $this->broadcastRedis($dataOld);
                return response($response);
            } else {
                $response['status'] = FALSE;
                $response['errMsg'] = "Server error, cannot asign data!";
                return response($response);
            }
        }
    }

    function removeContainer(Request $request) {
        date_default_timezone_set('Asia/Singapore');
        $check = TemporaryPark::where('ParkingLot', '=', $request->park)->first();

        $history = new History();
        $history->SetDt = $check->updatedDt;
        $history->UnSetDt = date('Y-m-d H:i:s');
        $history->ParkingLot = $check->ParkingLot;
        $history->Dummy = $check->Dummy;
        $history->trailer = $check->trailer;
        $history->createdBy = $request->user;

        if($history->save()){
            $check->delete();
            $response['status'] = TRUE;
            $response['data'] = $history;
            $dataRedis = "0," . $request->park . ",0";
            $this->broadcastRedis($dataRedis);
            return response($response);
        } else {
            $response['status'] = FALSE;
            $response['errMsg'] = "Server error, cannot asign data!";
            return response($response);
        }
    }

    function convertData($data, $dataUser) {
        $fulldate = date("Y-m-d H:i:s");
        $dataArray = array();
        foreach ($data as $key => $datas) {
            //Get ongoing park
            $temppark = TemporaryPark::where('ParkingLot', $datas->ParkID)
            ->get();
            $datatemparray = array();
            if(!$temppark->isEmpty()) {
                foreach($temppark as $key => $temp) {
                    if ($temp->Dummy != 0) {
                        $container = ContainerView::where('Dummy', '=', $temp->Dummy)->first();
                        $ndt = new \stdClass();
                        $ndt->ParkingLot = $temp->ParkingLot;
                        $ndt->Dummy = $temp->Dummy;
                        $ndt->createdBy = $temp->createdBy;
                        $ndt->createdDt = $temp->createdDt;
                        $ndt->updatedBy = $temp->updatedBy;
                        $ndt->updatedDt = $temp->updatedDt;
                        $ndt->updatedFormatDt = (!empty($temp->updatedDt)) ? date('d/m H:i', strtotime($temp->updatedDt)) : "";
                        if(!empty($container)) {
                            $ndt->container = $this->formatData($container);
                        }
        
                        array_push($datatemparray, $ndt);
                    }
                }
            }

            $loopData = array(
                "id" => $datas->ParkID,
                "name" => $datas->Name,
                "place" => $datas->Place,
                "type" => $datas->Type,
                "availability" => ($temppark->isEmpty())? 1 : 0,
                "temp" => $datatemparray,
                "trailer" => (!$temppark->isEmpty())? $temppark[0]->trailer : null
            );
            array_push($dataArray, $loopData);
        }

        return $dataArray;
    }

    function formatData($datas) {
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
        $loopdata->IE = $datas["I/E"];
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
        $loopdata->Driver = $datas->Driver;
        $loopdata->YardRemarks = $datas->YardRemarks;
        return $loopdata;
    }

    function checkReUSE($dummy) {
        $checkDummy = ContainerView::where('Dummy', '=', $dummy)->first();
        $newOnee = ContainerView::where('Prefix', '=', $checkDummy->Prefix)
        ->where('Number', '=', $checkDummy->Number)
        ->where('Import/Export', '=', 'Export')
        ->where('YardRemarks', 'like', '%RE-USE%')
        ->whereIn('Status', ['EMPTY', 'CREATED', 'STUFFED', 'SHIPPED', 'COMPLETED', 'CLOSED'])
        ->first();
        $DummyToAssign = (!empty($newOnee) && $newOnee != $dummy) ? $newOnee->Dummy : $dummy;
        return $DummyToAssign;
    }

    function getOngoingDummy($dummy) {
        $reqdummy = ContainerView::where('Dummy', '=', $dummy)->first();
        $data = DB::table('HSC2012.dbo.Onee AS IP')
        ->join('HSC2017Test_V2.dbo.HSC_OngoingPark AS IB', 'IP.Dummy', '=', 'IB.Dummy')
        ->where('Prefix', '=', $reqdummy->Prefix)
        ->where('Number', '=', $reqdummy->Number)
        ->first();
        return $data->Dummy;
    }

    function broadcastRedis($data) {
        $redis = Redis::connection();
        $redis->publish("update-park", $data);
        return;
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
        $loopdata->Pkg = $datas->Pkg;
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
