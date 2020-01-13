<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\History;
use App\TemporaryPark;
use App\ContainerView;
use App\ContainerInfo;

class HistoryController extends Controller
{
    function getAllSummary() {
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

    function formatContainer($datas, $type) {
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
