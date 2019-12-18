<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Container;
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
                $container = Container::find($datas->containerId);
                $loopData = new \stdClass();
                $loopData->id = $datas->id;
                $loopData->containerNumber = $container->containerNumber;
                $loopData->clientId = $container->clientId;
                $loopData->parkIn = $datas->parkIn;
                $loopData->parkOut = $datas->parkOut;
                array_push($newdata, $loopData);
            }
            return DataTables::of($newdata)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<a href="../history/' . $row->id . '" class="edit btn btn-primary btn-sm">View</a>
                            <a href="../history/' . $row->id . '/edit" class="edit btn btn-warning btn-sm">Edit</a>
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
        $container = Container::find($data->containerId);
        $check = new \stdClass();
        $check->number = $container->containerNumber;
        $check->client = $container->clientId;
        $check->size = $container->size;
        $check->note = $data->note;
        $check->parkin = $data->parkIn;
        $check->parkout = $data->parkOut;
        $check->status = ($data->status == 0)? "Finished" : "Cancelled";
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
