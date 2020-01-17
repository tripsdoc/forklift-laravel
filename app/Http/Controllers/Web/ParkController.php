<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Container;
use App\History;
use App\Park;
use App\TemporaryPark;
use DataTables;
use Redirect;
use Request;
use Session;
use View;
use Validator;

/* Process 
-- Create (Done)
-- Edit (Done)
-- Delete (Need add flash message)
-- View
---- Show calendar with ongoing park (past data not included) - Onclick data return to view ongoing data (temporary.show) (Done)
*/

class ParkController extends Controller
{
    function index() {
        $data = Park::all();
        return view('park.index')->with('data', $data);
    }

    function getParkCalendar($id) {
        $datacolor = ["#3291a8", "#cc1818", "#18cc21", "#f0e91a", "#f08c1a", "#ac1af0", "#f01a70", "#1af0be", "#5a1af0", "#ffdbac"];
        $fulldate = date("Y-m-d H:i:s");
        $park = Park::find($id);
        $temppark = TemporaryPark::where('parkId', '=', $park->id)
        ->where('parkOut', '>', $fulldate)
        ->get();
        $dataPass = array();
        foreach($temppark as $key => $data) {
            $selected = ($data->id % 10) - 1;
            $loopData = new \stdClass();
            $container = Container::find($data->containerId);
            $loopData->title = $container->containerNumber . " - " . $container->clientId;
            $loopData->start = date('Y-m-d H:i:s', strtotime($data->parkIn));
            $loopData->end = date('Y-m-d H:i:s', strtotime($data->parkOut));
            $loopData->backgroundColor = $datacolor[$selected];
            $loopData->borderColor = $datacolor[$selected];
            $loopData->url = "../temporary/" . $data->id;
            array_push($dataPass, $loopData);
        }

        return $dataPass;
    }

    function getTempPark($id) {
        if(request()->ajax()) {
            $fulldate = date("Y-m-d H:i:s");

            //Show currently ongoing park limit with only 4 data
            $data = TemporaryPark::where('parkId', $id)
            ->where('parkOut', '>', $fulldate)
            ->orderBy('parkIn', 'asc')
            ->limit(4)
            ->get();

            $newdata = array();
            foreach($data as $key => $datas) {
                $container = Container::find($datas->containerId);
                $loopdata = new \stdClass();
                $loopdata->id = $datas->id;
                $loopdata->parkId = $datas->parkId;
                $loopdata->containerNumber = $container->containerNumber;
                $loopdata->clientId = $container->clientId;
                $loopdata->parkIn = $datas->parkIn;
                $loopdata->parkOut = $datas->parkOut;
                array_push($newdata, $loopdata);
            }
            return DataTables::of($newdata)
                ->addIndexColumn()
                ->addColumn('action', function($row) use($fulldate){
                    $btn = '<a href="../temporary/' . $row->id . '" class="edit btn btn-warning btn-sm">View</a>  
                    ';
                    if($row->parkIn < $fulldate) {
                        $btn = $btn . '<a href="../finish/' . $row->id . '" class="edit btn btn-success btn-sm">Finish Park</a>
                        ';
                    }
                    $btn = $btn . '<a href="../cancel/' . $row->id . '" class="edit btn btn-danger btn-sm">Cancel Park</a>
                    ';
                    return $btn;
                })
                ->make(true);
        }
        return view('park.index');
    }

    function getAllPark() {
        if(request()->ajax()) {
            $data = Park::all();
            $newdata = array();
            foreach($data as $key => $datas) {
                $loopData = new \stdClass();
                $loopData->id = $datas->ParkID;
                $loopData->name = $datas->Name;
                $loopData->place = $datas->Place;
                if ($datas->Type == 1) {
                    $type = "Warehouse";
                }
                if ($datas->Type == 2) {
                    $type = "Parking Lots";
                }
                if ($datas->Type == 3) {
                    $type = "Temporary";
                }
                $loopData->type = $type;
                array_push($newdata, $loopData);
            }
            return DataTables::of($newdata)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<a href="../park/' . $row->id . '/edit" class="edit btn btn-warning btn-sm">Edit</a>
                            <form style="display: inline-block;" class="pull-left" action="../park/' . $row->id . '" method="POST">'
                            . csrf_field() .
                                '<input type="hidden" name="_method" value="DELETE">
                                <button class="jquery-postback btn btn-danger btn-sm">Delete</button>
                            </form>
                    ';
                    return $btn;
                })
                ->make(true);
        }
        return view('park.index');
    }
    
    function create() {
        return View::make('park.create');
    }

    function store() {
        $rules = array(
            'name' => 'required',
            'type' => 'required',
            'place' => 'required'
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('park/create')
            ->withErrors($validator);
        } else {
            $device = new Park;
            $device->name = Request::get('name');
            $device->detail = Request::get('detail');
            $device->type = Request::get('type');
            $device->place = Request::get('place');
            $device->save();

            Session::flash('message', 'Successfully added new Park!');
            return Redirect::to('park');
        }
    }

    function edit($id) {
        $data = Park::find($id);
        return View::make('park.edit')->with('data', $data);
    }

    function update($id) {
        $rules = array(
            'name' => 'required'
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('park/' . $id . "/edit")
            ->withErrors($validator);
        } else {
            $device = Park::find($id);
            $device->name = Request::get('name');
            $device->detail = Request::get('detail');
            $device->save();

            Session::flash('message', "Successfully edit park data!");
            return Redirect::to('park');
        }
    }

    function show($id) {
        $dateToday = date("Y-m-d");
        $dateTomorrow = date("Y-m-d", strtotime($dateToday . " +1 days"));
        $fulldate = date("Y-m-d H:i:s");
        $park = Park::find($id);
        $temppark = TemporaryPark::where('parkId', '=', $park->id)
        ->whereBetween('parkIn', [$dateToday, $dateTomorrow])
        ->where('parkOut', '>', $fulldate)
        ->orderBy('parkIn', 'asc')
        ->get();
        $newdata = array();
        foreach($temppark as $key => $datas) {
            $loopdata = new \stdClass();
            $container = Container::find($datas->containerId);
            $loopdata->id = $datas->id;
            $loopdata->containerNumber =$container->containerNumber;
            $loopdata->clientId = $container->clientId;
            $loopdata->size = $container->size;
            $loopdata->parkIn = $datas->parkIn;
            $loopdata->parkOut = $datas->parkOut;
            array_push($newdata, $loopdata);
        }
        $data = new \stdClass();
        $data->park = $park;
        $data->temporary = $newdata;
        $data->count = count($newdata);
        return View::make('park.show')->with('data', $data);
    }

    function destroy($id) {
        $data = Park::find($id);
        $data->delete();
        
        return Redirect::to('park');
    }
}
