<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\ContainerInfo;
use App\ContainerView;
use App\History;
use App\Park;
use DataTables;
use Redirect;
use Request;
use Session;
use Storage;
use Validator;
use View;

/*
- Report Function
- Show All History
*/

class HistoryController extends Controller
{
    function index() {
        return view('history.index');
    }

    function getAllHistory() {
        if(request()->ajax()) {
            $data = History::all();

            $newdata = array();
            foreach($data as $key => $datas) {
                $loopData = new \stdClass();
                $loopData->id = $datas->HistoryID;
                $dataOnee = ContainerView::where('Dummy', '=', $datas->Dummy)->first();
                $dataContainer = ContainerInfo::find($datas->Dummy);
                $loopData->containerNumber = (!empty($dataOnee)) ? $dataOnee->Prefix . $dataOnee->Number : "";
                $loopData->size = (!empty($dataOnee)) ? ((!empty($dataOnee->Type)) ? $dataOnee->Size . $dataOnee->Type : $dataOnee->Size) : "";
                $loopData->seal = (!empty($dataOnee)) ? $dataOnee->Seal : "";
                $loopData->clientId = (!empty($dataOnee)) ? $dataOnee->Client : "";
                $loopData->yard = (!empty($dataContainer)) ? $dataContainer->Yard : "";
                $loopData->estwt = (!empty($dataContainer->EstWt))? $dataContainer->EstWt : "";
                $loopData->parkIn = date('d/m H:i', strtotime($datas->SetDt));
                $loopData->parkOut = date('d/m', strtotime($datas->UnSetDt));
                $loopData->trailer = (!empty($datas->trailer)) ? $datas->trailer : "";
                array_push($newdata, $loopData);
            }
            return DataTables::of($newdata)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<a href="../history/' . $row->id . '" class="edit btn btn-primary btn-sm">View</a>
                            <form id="form-delete-' . $row->id . '" style="display: inline-block;" class="pull-left" action="../history/' . $row->id . '" method="POST">'
                            . csrf_field() .
                                '<input type="hidden" name="_method" value="DELETE">
                                <button class="jquery-postback btn btn-danger btn-sm">Delete</button>
                            </form>
                    ';
                    return $btn;
                })
                ->make(true);
        }
        return view('history.index');
    }

    function show($id) {
        $data = History::find($id);
        $park = Park::find($data->ParkingLot);
        $container = ContainerView::find($data->Dummy);
        $check = new \stdClass();
        $check->number = $container->Number;
        $check->client = $container->Client;
        $check->size = $container->Size;
        $check->parkname = $park->Name;
        $check->parkplace = $park->Place;
        $check->parkin = date('d/m H:i', strtotime($data->SetDt));
        $check->parkout = $data->UnSetDt;

        switch($park->Type) {
            case 1:
                $check->type = "Warehouse";
                break;
            case 2:
                $check->type = "Parking Lots";
                break;
            case 3:
                $check->type = "Temporary";
                break;
            default:
                $check->type = "Warehouse";
                break;
        }
        return View::make('history.show')->with('data', $check);
    }

    function getTodayHistory() {
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 days'));
        $data = History::whereBetween('created_at', [$today, $tomorrow])->get();

        $response['status'] = (!$data->isEmpty());
        $response['today'] = date('Y-m-d H:i:s', strtotime($today));
        $response['tomorrow'] = date('Y-m-d H:i:s', strtotime($tomorrow));
        if (!$data->isEmpty()) {
            $response['data'] = $data;
        }
        return response($response);
    }

    function getMonthHistory() {
        $monthstart = Request::get('year') . '-' . Request::get('month') . '-01';
        $datemonthstart = date('Y-m-d', strtotime($monthstart));
        $datemonthend = date('Y-m-d', strtotime($monthstart . ' +1 months'));

        $data = History::whereBetween('created_at', [$datemonthstart, $datemonthend])->get();
        $response['status'] = (!$data->isEmpty());
        $response['start'] = $datemonthstart;
        $response['end'] = $datemonthend;
        if (!$data->isEmpty()) {
            $response['data'] = $data;
        }
        return response($response);
    }

    function getYearHistory() {
        $year = Request::get('year') . '-01-01';
        $dateyearstart = date('Y-m-d', strtotime($year));
        $dateyearend = date('Y-m-d', strtotime($year . ' +1 years'));
        $data = History::whereBetween('created_at', [$dateyearstart, $dateyearend])->get();
        $response['status'] = (!$data->isEmpty());
        $response['start'] = $dateyearstart;
        $response['end'] = $dateyearend;
        if (!$data->isEmpty()) {
            $response['data'] = $data;
        }
        return response($response);
    }

    function getCustomHistory() {
        $datastart = date('Y-m-d', strtotime(Request::get('datestart')));
        $dataend = date('Y-m-d', strtotime(Request::get('dateend')));
        $data = History::whereBetween('created_at', [$datastart, $dataend])->get();
        $response['status'] = (!$data->isEmpty());
        $response['start'] = $datastart;
        $response['end'] = $dataend;
        if (!$data->isEmpty()) {
            $response['data'] = $data;
        }
        return response($response);
    }

    /* function edit($id) {
        $data = History::find($id);
        return View::make('history.edit')->with('data', $data);
    } */
}
