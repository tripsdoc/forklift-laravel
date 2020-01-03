<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Container;
use App\ContainerView;
use App\History;
use App\Park;
use App\TemporaryPark;
use Carbon\Carbon;
use DataTables;
use Date;
use View;
use Illuminate\Http\Request;

/*

*/

class ParkController extends Controller
{
    // -----------------------------------------  Park List Function -----------------------------------------------------------
    function getAllPark(Request $request) {
        date_default_timezone_set('Asia/Jakarta');
        $data = Park::paginate(10);

        $dataUser = $request->user;
        $dataArray = $this->convertData($data->items(), $dataUser);

        $response['status'] = !$data->isEmpty();
        $response['current'] = $data->currentPage();
        $response['nextUrl'] = $data->nextPageUrl();
        $response['last'] = $data->lastPage();
        $response['data'] = $dataArray;
        return response($response);
    }
    function getAllParkSpinner(Request $request, $type) {
        $dataUser = $request->user;
        $data = Park::where('type', '=', $type)->paginate(10);

        $dataArray = $this->convertData($data->items(), $dataUser);

        $response['status'] = !$data->isEmpty();
        $response['current'] = $data->currentPage();
        $response['nextUrl'] = $data->nextPageUrl();
        $response['last'] = $data->lastPage();
        $response['data'] = $dataArray;
        return response($response);
    }

    function getParkSearch(Request $request) {
        $dataUser = $request->user;
        $search = $request->search;
        $data = Park::where('name','LIKE',"%{$search}%")
        ->orWhere('place', 'LIKE',"%{$search}%")
        ->get();

        $dataArray = $this->convertData($data, $dataUser);

        $response['status'] = !$data->isEmpty();
        $response['data'] = $dataArray;
        return response($response);
    }

    function getParkByPlace($place) {
        $data = Park::where('Place', '=', $place)->get();
        $dataArray = $this->convertData($data, "");
        $response['status'] = !$data->isEmpty();
        $response['data'] = $dataArray;
        return response($response);
    }

    function getPlace(Request $request) {
        $data = Park::where('Type', '=', $request->type)->groupBy('place')->pluck('place');
        $response['status'] = !$data->isEmpty();
        $response['data'] = $data->toArray();
        return response($response);
    }
    // -------------------------------------------------------------------------------------------------------------------------
    
    function assignContainerToPark(Request $request) {
        date_default_timezone_set('Asia/Jakarta');
        $check = TemporaryPark::where('ParkingLot', '=', $request->park)->first();
        if(empty($check)) {
            $temp = new TemporaryPark();

            $temp->ParkingLot = $request->park;
            $temp->Dummy = $request->dummy;
            $temp->createdBy = $request->user;
            $temp->updatedDt = date('Y-m-d H:i:s');
            if($temp->save()) {
                $response['status'] = TRUE;
                $response['data'] = $temp;
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
            $history->createdBy = $request->user;

            if($history->save()){
                $check->Dummy = $request->dummy;
                $check->updatedBy = $request->user;
                $check->updatedDt = date('Y-m-d H:i:s');

                $check->save();
                $response['status'] = TRUE;
                $response['data'] = $check;
                return response($response);
            } else {
                $response['status'] = FALSE;
                $response['errMsg'] = "Server error, cannot asign data!";
                return response($response);
            }
        }
    }

    function removeContainer(Request $request) {
        date_default_timezone_set('Asia/Jakarta');
        $check = TemporaryPark::where('ParkingLot', '=', $request->park)->first();

        $history = new History();
        $history->SetDt = $check->updatedDt;
        $history->UnSetDt = date('Y-m-d H:i:s');
        $history->ParkingLot = $check->ParkingLot;
        $history->Dummy = $check->Dummy;
        $history->createdBy = $request->user;

        if($history->save()){
            $check->delete();
            $response['status'] = TRUE;
            $response['data'] = $history;
            return response($response);
        } else {
            $response['status'] = FALSE;
            $response['errMsg'] = "Server error, cannot asign data!";
            return response($response);
        }
    }

    //Get Container Data from TemporaryPark and merge it
    function getFullData($data) {
        $forfilter = array();
        foreach($data as $key => $dataFilter) {
            $loopData = new \stdClass();
            //$container = Container::find($dataFilter->containerId);
            $container = ContainerView::where('Dummy', '=', $dataFilter->Dummy)->first();
            $loopData->VesselID = $container->VesselID;
            $loopData->VesselName = $container->VesselName;
            $loopData->InVoy = $container->InVoy;
            $loopData->OutVoy = $container->OutVoy;
            $loopData->ETA = $container->ETA;
            $loopData->COD = $container->COD;
            $loopData->Berth = $container->Berth;
            $loopData->ETD = $container->ETD;
            $loopData->ServiceRoute = $container->ServiceRoute;
            $loopData->Client = $container->Client;
            $loopData->TruckTo = $container->TruckTo;
            $loopData->ImportExport = $container->ImportExport;
            $loopData->IE = $container->IE;
            $loopData->LDPOD = $container->LDPOD;
            $loopData->DeliverTo = $container->DeliverTo;
            $loopData->Prefix = $container->Prefix;
            $loopData->Number = $container->Number;
            $loopData->Seal = $container->Seal;
            $loopData->Size = $container->Size;
            $loopData->Type = $container->Type;
            $loopData->Remarks = $container->Remarks;
            $loopData->Status = $container->Status;
            $loopData->DateofStufUnstuf = $container->DateofStufUnstuf;
            $loopData->Dummy = $container->Dummy;
            $loopData->Expr1 = $container->Expr1;
            $loopData->Expr2 = $container->Expr2;
            $loopData->Expr3 = $container->Expr3;
            $loopData->Chassis = $container->Chassis;
        }
        return collect($forfilter);
    }

    function getAllOnGoingByUser(Request $request) {
        $fulldate = date("Y-m-d H:i:s");
        $data = TemporaryPark::where('created_by', '=', $request->user)
        ->where('parkOut', '>', $fulldate)
        ->orderBy('parkIn', 'asc')
        ->get();
        $newdata = $this->getFullData($data);
        $response['status'] = !$data->isEmpty();
        $response['data'] = $newdata;
        return response($response);
    }

    function convertData($data, $dataUser) {
        $fulldate = date("Y-m-d H:i:s");
        $dataArray = array();
        foreach ($data as $key => $datas) {
            //Get ongoing park
            $temppark = TemporaryPark::where('ParkingLot', $datas->ParkID)
            ->get();

            $datatemparray = array();
            foreach($temppark as $key => $temp) {
                $container = ContainerView::where('Dummy', '=', $temp->Dummy)->first();
                $ndt = new \stdClass();
                $ndt->ParkingLot = $temp->ParkingLot;
                $ndt->Dummy = $temp->Dummy;
                $ndt->createdBy = $temp->createdBy;
                $ndt->createdDt = $temp->createdDt;
                $ndt->updatedBy = $temp->updatedBy;
                $ndt->updatedDt = $temp->updatedDt;
                $ndt->container = $this->formatData($container);

                array_push($datatemparray, $ndt);
            }

            $newdata = $this->getFullData($temppark);
            $loopData = array(
                "id" => $datas->ParkID,
                "name" => $datas->Name,
                "place" => $datas->Place,
                "type" => $datas->Type,
                "availability" => ($temppark->isEmpty())? 1 : 0,
                "temp" => $datatemparray
            );
            array_push($dataArray, $loopData);
        }

        return $dataArray;
    }

    function detailPark($id) {
        $data = Park::find($id);
        $response['status'] = !$data->isEmpty();
        $response['data'] = $data;
        return response($response);
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
        return $loopdata;
    }
}
