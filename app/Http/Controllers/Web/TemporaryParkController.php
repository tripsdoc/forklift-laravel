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
use Storage;
use Validator;
use View;

/*
-- Create (Done)
-- Update (Done)
-- View (Done) Show with dialog(iframe) ?
-- Select data with checkbox (Edit and Cancel or Finish)
    -- Show dialog with note are same
    -- Show dialog with different note
-- Finish and Cancel (Done)
*/

class TemporaryParkController extends Controller
{
    function index() {
        return view('ongoing.index');
    }

    function getAllTemporary() {
        $datenow = date('Y-m-d H:i:s');
        if(request()->ajax()) {
            $data = TemporaryPark::all();
            $newdata = array();

            //Format full data (Ongoing with container and park data)
            foreach($data as $key => $datas) {
                $loopData = new \stdClass();
                $container = Container::find($datas->CntrId);
                $park = Park::find($datas->parkId);
                $loopData->id = $datas->id;
                $loopData->parkId = $park->name;
                $loopData->clientId = $container->clientId;
                $loopData->containerNumber = $container->containerNumber;
                $loopData->parkIn = $datas->parkIn;
                $loopData->parkOut = $datas->parkOut;
                array_push($newdata, $loopData);
            }
            return DataTables::of($newdata)
                ->addIndexColumn()
                ->addColumn('action', function($row) use($datenow) {
                    $btn = '<a href="../temporary/' . $row->id . '" class="edit btn btn-primary btn-sm">View</a>
                            <a href="../temporary/' . $row->id . '/edit" class="edit btn btn-warning btn-sm">Edit</a>
                            ';
                    if($row->parkIn < $datenow) {
                        $btn = $btn . '<a href="../finish/' . $row->id . '" class="edit btn btn-success btn-sm">Finish Park</a>
                        ';
                    }
                    $btn = $btn . '<a href="../cancel/' . $row->id . '" class="edit btn btn-danger btn-sm">Cancel Park</a>';
                    return $btn;
                })
                ->make(true);
        }
        return view('ongoing.index');
    }

    function show($id) {
        $data = TemporaryPark::find($id);
        $park = Park::find($data->parkId);
        $container = Container::find($data->CntrId);
        $check = new \stdClass();
        $check->name = $park->name;
        $check->number = $container->containerNumber;
        $check->client = $container->clientId;
        $check->size = $container->size;
        $check->status = $container->note;
        $check->parkin = $data->parkIn;
        $check->parkout = $data->parkOut;
        return View::make('ongoing.show')->with('data', $check);
    }

    function create() {
        $data = Park::pluck('name', 'id');
        return View::make('ongoing.create')->with('data', $data);
    }

    function store() {
        $rules = array(
            'number' => 'required',
            'client' => 'required',
            'size' => 'required',
            'parkin' => 'required|date',
            'parkout' => 'required|date|after_or_equal:parkin',
            'timein' => 'required',
            'timeout' => 'required',
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('temporary/create')
            ->withErrors($validator);
        } else {
            $parkin = Request::get('parkin') . " " . Request::get('timein');
            $parkout = Request::get('parkout') . " " . Request::get('timeout');

            $formattedin = date('Y-m-d H:i:s', strtotime($parkin));
            $formattedout = date('Y-m-d H:i:s', strtotime($parkout));
            $device = new TemporaryPark;
            $container = new Container;

            $container->containerNumber = Request::get('number');
            $container->clientId = Request::get('client');
            $container->size = Request::get('size');
            $container->note = Request::get('note');
            $container->created_by = "admin";

            if($container->save()) {
                $device->CntrId = $container->id;
                $device->parkId = Request::get('parkid');
                $device->parkIn = $formattedin;
                $device->parkOut = $formattedout;
                //$device->created_at = date("Y-m-d H:i:s");
                $device->created_by = 'admin';
                $device->save();
            }

            Session::flash('message', 'Successfully added new Park!');
            return Redirect::to('temporary');
        }
    }

    function edit($id) {
        $park = Park::pluck('name', 'id');
        $temppark = TemporaryPark::find($id);
        $container = Container::find($temppark->CntrId);
        $data = new \stdClass();
        $data->park = $park;
        $data->temporary = $temppark;

        $data->id = $temppark->id;
        $parkin = $temppark->parkIn;
        $parkout = $temppark->parkOut;
        $splitParkIn = explode(' ', $parkin);
        $splitParkOut = explode(' ', $parkout);
        $data->parkIn = $splitParkIn[0];
        $data->timeIn = $splitParkIn[1];
        $data->parkOut = $splitParkOut[0];
        $data->timeOut = $splitParkOut[1];
        $data->number = $container->containerNumber;
        $data->client = $container->clientId;
        $data->size = $container->size;

        return View::make('ongoing.edit')->with('data', $data);
    }

    //Every updated data will save into a log files
    function update($id) {
        $rules = array(
            'number' => 'required',
            'client' => 'required',
            'size' => 'required',
            'parkin' => 'required|date',
            'parkout' => 'required|date|after_or_equal:parkin',
            'timein' => 'required',
            'timeout' => 'required',
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('temporary/'. $id . "/edit")
            ->withErrors($validator);
        } else {
            $parkin = Request::get('parkin') . " " . Request::get('timein');
            $parkout = Request::get('parkout') . " " . Request::get('timeout');

            $formattedin = date('Y-m-d H:i:s', strtotime($parkin));
            $formattedout = date('Y-m-d H:i:s', strtotime($parkout));
            $olddata = TemporaryPark::find($id);
            $device = TemporaryPark::find($id);
            if(!empty($device)) {
                $oldcontainer = Container::find($device->CntrId);
                $container = Container::find($device->CntrId);

                $device->parkId = Request::get('parkid');
                $container->containerNumber = Request::get('number');
                $container->clientId = Request::get('client');
                $container->size = Request::get('size');
                $device->parkIn = $formattedin;
                $device->parkOut = $formattedout;
    
                //$device->updated_at = date("Y-m-d H:i:s");
                $device->updated_by = 'admin';
                $device->save();
                $container->save();
    
                $filename = $device->CntrId . ".log";
                $dataToSave = date('Y-m-d H:i:s') . " : old(" . $olddata . $oldcontainer . "), new(" . $device . $container . ")";
                Storage::append($filename, $dataToSave);
    
                Session::flash('message', 'Successfully edit Ongoing park!');
            } else {
                Session::flash('message', 'There is an error!');
            }
            return Redirect::to('temporary');
        }
    }

    function finishPark($id) {
        $temppark = TemporaryPark::find($id);
        if($this->createHistory($temppark, null, 0)) {
            return Redirect::to('temporary');
        }
    }

    function cancelPark($id) {
        $temppark = TemporaryPark::find($id);
        $reason = Request::get('note');
        if($this->createHistory($temppark, $reason, 1)) {
            return Redirect::to('temporary');
        }
    }

    function createHistory($temppark, $reason, $status) {
        $datenow = date('Y-m-d H:i:s');
        $history = new History;
        $history->CntrId = $temppark->CntrId;
        $history->parkIn = $temppark->parkIn;
        $history->parkOut = $datenow;
        $history->status = $status;
        if(!empty($reason)) {
            $history->note = $reason;
        }
        $history->created_at = $datenow;
        $history->created_by = "admin";

        if($history->save()) {
            $temppark->delete();
            return true;
        }
    }
}
