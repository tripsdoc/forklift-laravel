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
Need to do
- View Home (Show task ongoing park are asign to the driver)
- Add function barcode (All data container)
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
    // -------------------------------------------------------------------------------------------------------------------------
    
    function asignContainerToPark(Request $request) {
        $temp = new TemporaryPark();

        $temp->ParkingLot = $request->park;
        $temp->Dummy = $request->dummy;
        $temp->createdBy = $request->user;
        if($temp->save()) {
            $response['status'] = TRUE;
            $response['data'] = $temp;
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
            $temppark = TemporaryPark::where('ParkingLot', $datas->id)
            ->get();

            $newdata = $this->getFullData($temppark);
            $loopData = array(
                "id" => $datas->ParkID,
                "name" => $datas->Name,
                "place" => $datas->Place,
                "type" => $datas->Type,
                "availability" => ($temppark->isEmpty())? 1 : 0,
                "temp" => $temppark
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

    function getCurrent($id) {
        date_default_timezone_set('Asia/Jakarta');
        $datanow = date("Y-m-d H:i:s");
        $park = Park::find($id);
        $temppark = TemporaryPark::where('parkId', $park->id)
        ->orderBy('parkIn', 'asc')
        ->get();

        $newdata = $this->getFullData($temppark);
        $filtered = $newdata->filter(function($newdata) use($datanow) {
            return $newdata->parkOut > $datanow || empty($newdata->parkOut);
        });
        $filtered = $filtered->flatten(1);
        $response['status'] = !$newdata->isEmpty();
        $response['data'] = $filtered->all();
        return response($response);
    }

    function getTodayPark($id) {
        date_default_timezone_set('Asia/Jakarta');
        $datanow = date("Y-m-d H:i:s");
        $tomorrow = Carbon::tomorrow('Asia/Jakarta');
        $park = Park::find($id);
        $temppark = TemporaryPark::where('parkId', $park->id)
        ->whereBetween('parkIn', [$datanow, $tomorrow])
        ->orderBy('parkIn', 'asc')
        ->get();
        $response['status'] = !$temppark->isEmpty();
        $response['data'] = $temppark;
        return response($response);
    }

    function releasePark(Request $request) {
        $dataTemp = TemporaryPark::find($request->id);
        if(!empty($dataTemp)) {
            if($this->storeHistory($dataTemp)) {
                $response['status'] = true;
                $response['data'] = $dataTemp;
                $dataTemp->delete();
                return response($response);
            }
        }
    }

    function editContainer(Request $request) {
        $temp = TemporaryPark::find($request->id);
        $container = Container::find($temp->containerId);

        $container->containerNumber = $request->number;
        $container->clientId = $request->client;
        $container->size = $request->size;
        //$temp->status = $request->status;
        $temp->parkIn = $request->parkIn;
        $temp->parkOut = $request->parkOut;

        if($temp->save()) {
            $response['status'] = true;
            $response['data'] = [$temp];
            $response['container'] = [$container];
        } else {
            $response['status'] = false;
            $response['errMsg'] = "Cannot update container data!";
        }

        return response($response);
    }

    function bookPark(Request $request) {
        $data = Park::find($request->id);
        $check = TemporaryPark::where('parkID', $request->id)->get();
        if(empty($check)) {
            return $this->createTemporary($request);
        } else {
            $datanow = date("Y-m-d");
            $countBetween = 0;
            $dateCheck = date("Y-m-d H:i:s", strtotime($request->parkIn));
            $check = TemporaryPark::where('parkID', $request->id)
            ->where('parkOut', '>', $datanow)
            ->get();
            foreach($check as $key => $datas) {
                //Check if date are between another ongoing data
                if($datas->parkIn <= $dateCheck && $datas->parkOut >= $dateCheck) {
                    $countBetween++;
                } else {
                    // Request parkin + 30 minutes <= parkIn    --- datePlus    (Request parkin must be 30 minutes before another ongoing parkin)
                    // Request parkin <= parkOut + 10 minutes   --- dateOutPlus (Request parkin must be 10 minutes after another ongoing parkout)
                    $datePlus = date("Y-m-d H:i:s", strtotime($request->parkIn . " +30 minutes"));
                    $dateOutPlus = date("Y-m-d H:i:s", strtotime($datas->parkOut . " +10 minutes"));
                    if($datas->parkIn <= $datePlus && $dateOutPlus >= $datePlus) {
                        $countBetween++;
                    }
                }
            }

            if($countBetween > 0) {
                $response['status'] = false;
                $response['errMsg'] = "The park date or time has been used. Please use another date \n(Time must be at least 30 minutes before!)";
                return response($response);
            } else {
                return $this->createTemporary($request);
            }
        }
    }

    //Create ongoing data by creating container data first
    function createTemporary($request) {
        $parkInDate = date('Y-m-d', strtotime($request->parkIn));

        $tempPark = new TemporaryPark();
        $container = new Container();
        $history = History::where('parkIn', '>', $parkInDate)
        ->where('parkId', '=', $request->id)
        ->where('status', 0) // Only get finished park, cancelled not count on checking new data
        ->get();
        //$data->availability = 0;
        $tempPark->parkId = $request->id;
        $tempPark->created_by = $request->user;
        $tempPark->parkIn = $request->parkIn;
        //MSSQL Not Used
        //$tempPark->created_at = date("Y-m-d H:i:s");

        //Check based on history, parkIn must not intercept with any history data (request->parkIn must be > parkOutHistory)
        //parkInHistory <= parkInRequest <= parkOutHistory/createdAtHistory -- Hi -- Ro -- Ho
        //parkInRequest <= parkInHistory -- Ro < Hi
        if(!empty($history)) {
            $filtered = $history->filter(function($history) use($request) {
                return $history->parkIn >= $request->parkIn || $history->parkIn <= $request->parkIn && $history->parkOut >= $request->parkIn;
            });
            $filterFlatten = $filtered->flatten(1);
        }

        if(!empty($request->parkOut)) {
            $tempPark->parkOut = $request->parkOut;
        }
        if(!empty($request->client)) {
            $container->clientId = $request->client;
        }
        if(!empty($request->number)) {
            $container->containerNumber = $request->number;
        }
        if(!empty($request->size)) {
            $container->size = $request->size;
        }
        if(!empty($request->note)) {
            $container->note = $request->note;
        }
        $container->created_by = $request->user;
        //$data->save();

        //Check if parkIn
        if(!$history->isEmpty()) {
            if(empty($filterFlatten)) {
                if($container->save()) {
                    $tempPark->containerId = $container->id;
                }
                $tempPark->save();
        
                $response['status'] = true;
                $response['data'] = [$tempPark];
            } else {
                $response['status'] = false;
                $response['errMsg'] = "Cannot add data on already finished park!";
            }
        } else {
            if($container->save()) {
                $tempPark->containerId = $container->id;
            }
            $tempPark->save();
    
            $response['status'] = true;
            $response['data'] = [$tempPark];
        }
        
        return response($response);
    }

    function checkLevel($id) {
        $datanow = date("Y-m-d");
        $fulldate = date("Y-m-d H:i:s");
        $temppark = TemporaryPark::where('parkId', $id)
        ->where('parkOut', '>', $fulldate)
        ->orderBy('parkIn', 'asc')
        ->get();
        
        $filtered = $temppark->filter(function($temppark) use($fulldate, $datanow) {
            // parkIn < fulldate < parkOut
            return ($temppark->parkIn < $fulldate && $temppark->parkOut > $fulldate);
        });
        $filterFlatten = $filtered->flatten(1);

        if(!$temppark->isEmpty()) {
            //Check if there is ongoing data
            if (!$filterFlatten->isEmpty()) {
                $response['isOnGoing'] = true;
                $availability = 0;
            } else {
                //Check if date diff is more than (loop until 12 hours)
                $availability = 0;
                for ($x = 1; $x <= 12; $x++) {
                    $hours = "+". $x . " hours";
                    $dateHours = date("Y-m-d H:i:s", strtotime($hours));
                    if ($dateHours <= $temppark[0]->parkIn ) {
                        $availability++;
                    }
                }
            }
        } else {
            $availability = 12;
        }
        
        return $availability;
    }

    function debug(Request $request) {
        /* $fulldate = date("Y-m-d H:i:s");
        $temppark = TemporaryPark::where('parkId', $request->id)
        ->where('parkOut', '>', $fulldate)
        ->orderBy('parkIn', 'asc')
        ->get();
        
        $filtered = $temppark->filter(function($temppark) use($fulldate) {
            // parkIn < fulldate < parkOut
            return ($temppark->parkIn < $fulldate && $temppark->parkOut > $fulldate);
        });
        $filterFlatten = $filtered->flatten(1);

        if(!$temppark->isEmpty()) {
            //Check if there is ongoing data
            if (!$filterFlatten->isEmpty()) {
                $response['isOnGoing'] = true;
                $availability = 0;
            } else {
                //Check if date diff is more than (loop until 12 hours)
                $availability = 0;
                for ($x = 1; $x <= 12; $x++) {
                    $hours = "+". $x . " hours";
                    $dateHours = date("Y-m-d H:i:s", strtotime($hours));
                    if ($dateHours <= $temppark[0]->parkIn ) {
                        $availability++;
                    }
                }
            }
        } else {
            $availability = 12;
        }
        $response['flatten'] = $filterFlatten;
        $response['status'] = $temppark; */
        /* ----------------------------------------------------------------------------------------------------------------------------------------------- */

        //parkInHistory <= parkInRequest <= parkOutHistory/createdAtHistory -- Hi -- Ro -- Ho
        //parkInRequest <= parkInHistory -- Ro < Hi
        $parkInDate = date('Y-m-d', strtotime($request->parkIn));
        $history = History::where('parkIn', '>', $parkInDate)
        ->where('status', 0)
        ->get();
        $filtered = $history->filter(function($history) use($request) {
            return $history->parkIn >= $request->parkIn || $history->parkIn <= $request->parkIn && $history->parkOut >= $request->parkIn;
        });
        $filterFlatten = $filtered->flatten(1);
        if (!$history->isEmpty()) {
            $response['parkInDate'] = $parkInDate;
        }
        $response['flatten'] = $filterFlatten;
        $response['history'] = $history;
        /* ----------------------------------------------------------------------------------------------------------------------------------------------- */

        /* $datacolor = ["#3291a8", "#cc1818", "#18cc21", "#f0e91a", "#f08c1a", "#ac1af0", "#f01a70", "#1af0be", "#5a1af0", "#ffdbac"];
        $selected = ($request->id % 10) - 1;
        $response['selected'] = $selected;
        $response['color'] = $datacolor[$selected]; */
        return response($response);
    }

    //Add data to history table -- On Finished Park 
    function storeHistory($data) {
        $history = new History();
        $history->created_at = date("Y-m-d H:i:s");
        $history->created_by = $data->created_by;
        $history->containerId = $data->containerId;
        $history->parkIn = $data->parkIn;
        $history->parkOut = date("Y-m-d H:i:s");
        $history->status = 0;

        if($history->save()) {
            return true;
        }
    }

    //Add data to history table -- On Cancelled Park
    function cancelPark(Request $request) {
        $temppark = TemporaryPark::find($request->id);
        $cancelled = new History();
        $cancelled->containerId = $temppark->containerId;
        $cancelled->parkIn = $temppark->parkIn;
        $cancelled->parkOut = $temppark->parkOut;
        $cancelled->status = 1;
        $cancelled->note = $request->reason;
        $cancelled->created_at = date("Y-m-d H:i:s");
        $cancelled->created_by = $request->user;

        if($cancelled->save()) {
            $temppark->delete();
            $response['status'] = true;
            $response['data'] = $cancelled;
        } else {
            $response['status'] = false;
            $response['errMsg'] = "Cannot cancelled ongoing park!";
        }
        return response($response);
    }
}
