<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\History;
use App\TemporaryPark;
use App\ContainerView;
use App\ContainerInfo;
use App\ShifterUser;

class HistoryController extends Controller
{
    function getAllSummaryBak() {
        $page = (!isset($_GET['page']))? 1: $_GET['page'];
        $dataongoing = TemporaryPark::all();
        $datahistory = History::all();

        $newdata = array();
        foreach($dataongoing as $key => $dataO) {
            $loopO = $this->formatContainer($dataO, 0);
            array_push($newdata, $loopO);
        }
        foreach($datahistory as $key => $dataH) {
            $loopH = $this->formatContainer($dataH, 1);
            array_push($newdata, $loopH);
        }

        $datacollection = collect($newdata);
        $sorted = $datacollection->sortBy('setDate');
        $data = $sorted->forPage($page, 20);
        $last = sizeOf($newdata) / 20;
        $response['status'] = (!$datacollection->isEmpty());
        $response['current'] = $page;
        $response['last'] = ceil($last);
        $response['data'] = $data->flatten();
        return response($response);
    }

    function getAllSummary(Request $request) {
        $mode = (empty($request->mode))? 0: $request->mode;
        $datawarehouse = array_map('trim', explode("/", $request->warehouse));
        $result = ContainerView::
        whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', '']);
        $result->Where(function($query) use($datawarehouse, $mode)
        {
            if ($mode != 0) {
                for($i=0;$i<count($datawarehouse);$i++){
                    if($i == 0) {
                        $query->where('DeliverTo', '=', $datawarehouse[$i]);
                    } else {
                        $query->orWhere('DeliverTo', '=', $datawarehouse[$i]);
                    }
                }
            }
        });
        $result->Where(function($query) use($mode)
        {
            if ($mode == 1) {
                $query->where('Import/Export', '=', 'Import');
            }
            if ($mode == 2) {
                $query->where('Import/Export', '=', 'Export');
            }
        });
        $result->orderBy('ETA');
        $data = $result->paginate(20);
        $dataArray = array();
        foreach($data->items() as $key => $datas) {
            $newdata = $this->formatContainer($datas);
            array_push($dataArray, $newdata);
        }
        $response['shifter'] = $datawarehouse;
        $response['status'] = !$data->isEmpty();
        $response['current'] = $data->currentPage();
        $response['nextUrl'] = $data->nextPageUrl();
        $response['last'] = $data->lastPage();
        $response['data'] = $dataArray;
        return response($response);
    }

    function getSummarySearch(Request $request) {
        $mode = $request->mode;
        $search = $request->search;
        $datawarehouse = array_map('trim', explode("/", $request->warehouse));
        $result = ContainerView::
        whereNotNull('Status')
        ->whereNotIn('Status', ['COMPLETED', 'PENDING', 'CLOSED', 'CANCELLED', '']);
        $result->Where(function($query) use($datawarehouse, $mode)
        {
            if ($mode != 0) {
                for($i=0;$i<count($datawarehouse);$i++){
                    if($i == 0) {
                        $query->where('DeliverTo', '=', $datawarehouse[$i]);
                    } else {
                        $query->orWhere('DeliverTo', '=', $datawarehouse[$i]);
                    }
                }
            }
        });
        $result->Where(function($query) use($mode)
        {
            if ($mode == 1) {
                $query->where('Import/Export', '=', 'Import');
            }
            if ($mode == 2) {
                $query->where('Import/Export', '=', 'Export');
            }
        });
        $result->where('Number','LIKE',"%{$search}%")
        ->orderBy('ETA');
        $data = $result->paginate(20);
        $dataArray = array();
        foreach($data->items() as $key => $datas) {
            $newdata = $this->formatContainer($datas);
            array_push($dataArray, $newdata);
        }
        $response['status'] = !$data->isEmpty();
        $response['current'] = $data->currentPage();
        $response['nextUrl'] = $data->nextPageUrl();
        $response['last'] = $data->lastPage();
        $response['data'] = $dataArray;
        return response($response);
    }

    function formatContainer($data) {
        $loopData = new \stdClass();
        $loopData->Dummy = $data->Dummy;
        $loopData->Client = $data->Client;
        $loopData->Seal = $data->Seal;
        $loopData->Yard = $data->Yard;
        $loopData->Size = $data->Size . $data->Type;
        $loopData->Container = $data->Prefix . $data->Number;
        $loopData->Remarks = $data->Remarks;
        $loopData->YardRemarks = $data->YardRemarks;
        $loopData->Chassis = $data->Chassis;
        $loopData->IE = $data["Import/Export"];
        $ongoing = TemporaryPark::where('Dummy', '=', $data->Dummy)->first();
        if (!empty($ongoing)) {
            $loopData->ParkingLot = $ongoing->ParkingLot;
        }
        $loopData->Status = $data->Status;
        $loopData->Driver = $data->Driver;

        $loopData->parkIn = (!empty($data->ETA)) ? date('d/m H:i', strtotime($data->ETA)) : "";
        $loopData->parkOut = $data["LD/POD"];
        return $loopData;
    }

    function formatContainerBak($datas, $type) {
        $loopData = new \stdClass();
        $container = ContainerView::where('Dummy', '=', $datas->Dummy)->first();
        $dataContainer = ContainerInfo::find($datas->Dummy);
        $loopData->setDate = ($type == 0) ? $datas->updatedDt : $datas->SetDt;
        $loopData->parkIn = ($type == 0) ? date('d/m H:i', strtotime($datas->updatedDt)): date('d/m H:i', strtotime($datas->SetDt));
        if ($type == 1) {
            $loopData->parkOut = date('d/m', strtotime($datas->UnSetDt));;
        }
        $loopData->Client = $container->Client;
        $loopData->Seal = $container->Seal;
        $loopData->Yard = $dataContainer->Yard;
        $loopData->Size = $container->Size . $container->Type;
        $loopData->Container = $container->Prefix . $container->Number;
        $loopData->Remarks = $container->Remarks;
        $loopData->Chassis = $container->Chassis;

        return $loopData;
    }
}
