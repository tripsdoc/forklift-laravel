<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\ShifterUser;
use Storage;
use DataTables;
use Redirect;
use Request;
use Session;
use View;
use Validator;

class ShifterController extends Controller
{
    function index() {
        return view('shifter.index');
    }

    function getAllShifter() {
            $data = ShifterUser::all();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $btn = '<a href="../shifter/' . $row->ShifterID . '/edit" class="edit btn btn-warning btn-sm">Edit</a>
                            <form style="display: inline-block;" class="pull-left" action="../shifter/' . $row->ShifterID . '" method="POST">'
                            . csrf_field() .
                                '<input type="hidden" name="_method" value="DELETE">
                                <button class="jquery-postback btn btn-danger btn-sm">Delete</button>
                            </form>
                    ';
                    return $btn;
                })
                ->make(true);
    }

    function create() {
        return View::make('shifter.create');
    }

    function store() {
        $rules = array(
            'name' => 'required',
            'username' => 'required|unique:sqlsrv3.ShifterUser,UserName',
            'password' => 'required'
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('shifter/create')
            ->withErrors($validator);
        } else {
            $shifter = new ShifterUser;
            $shifter->Name = Request::get('name');
            $shifter->UserName = Request::get('username');
            $shifter->Password = Request::get('password');
            $shifter->Warehouse = Request::get('warehouse');
            $shifter->save();

            Session::flash('message', 'Successfully added new Shifter!');
            return Redirect::to('shifter');
        }
    }

    function edit($id) {
        $data = ShifterUser::find($id);
        return View::make('shifter.edit')->with('data', $data);
    }

    function update($id) {
        $rules = array(
            'name' => 'required',
            'password' => 'required'
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('shifter/' . $id . "/edit")
            ->withErrors($validator);
        } else {
            $shifter = ShifterUser::find($id);
            $shifter->Name = Request::get('name');
            $shifter->Password = Request::get('password');
            $shifter->Warehouse = Request::get('warehouse');
            $shifter->save();

            Session::flash('message', "Successfully edit Shifter data!");
            return Redirect::to('shifter');
        }
    }

    function destroy($id) {
        $data = ShifterUser::find($id);
        $data->delete();
        
        return Redirect::to('shifter');
    }

    function debug() {
        $data = $_SERVER['HTTP_USER_AGENT'] . "\n";
        Storage::append('logs/device/device.txt', $data);
        return response($data);
    }
}
