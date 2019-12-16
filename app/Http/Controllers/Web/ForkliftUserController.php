<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Request;
use App\ForkliftUser;
use View;
use DataTables;
use Validator;
use Redirect;
use Session;

class ForkliftUserController extends Controller
{
    function index() {
        return view('forklift/index');
    }
    function jsonAll() {
        if (request()->ajax()) {
            $data = ForkliftUser::all();
            return Datatables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        $btn = '<a href="../forklift/' . Crypt::encrypt($row->UserId) . '/edit" class="edit btn btn-warning btn-sm">Edit</a>
                                <form id="form-delete" style="display: inline-block;" class="pull-left" action="../forklift/' . Crypt::encrypt($row->UserId) . '" method="POST">'
                                    . csrf_field() .
                                        '<input type="hidden" name="_method" value="DELETE">
                                        <button class="jquery-postback btn btn-danger btn-sm">Delete</button>
                                    </form>
                        ';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('forklift/index');
    }

    function show($id) {
        $data = ForkliftUser::find($id);
        return View::make('forklift/show')
        ->with('data', $data);
    }

    function create() {
        return View::make('forklift.create');
    }

    function store() {
        $rules = array(
            'username' => 'required',
            'password' => 'required'
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('forklift/create')
            ->withErrors($validator)
            ->withInput(Request::except('password'));
        } else {
            $forklift = new ForkliftUser;
            $forklift->username = Request::get('username');
            $forklift->password = Request::get('password');
            $forklift->save();

            return Redirect::to('forklift');
        }
        /* ForkliftUser::insert(
            ['UserId' => $request->id, 'UserName' => $request->username, 'Password' => $request->password]
        ); */
    }

    function edit($id) {
        $realValue = Crypt::decrypt($id);
        $data = ForkliftUser::find($realValue);
        $cryptData = new \stdClass();
        $cryptData->UserId = Crypt::encrypt($data->UserId);
        $cryptData->UserName = $data->UserName;
        return View::make('forklift.edit')
        ->with('data', $cryptData);
    }

    function update($id) {
        $decryptId = Crypt::decrypt($id);
        $rules = array(
            'username' => 'required',
            'password' => 'required'
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('forklift/' . $id . '/edit')
            ->withErrors($validator)
            ->withInput(Request::except('password'));
        } else {
            $forklift = ForkliftUser::find($decryptId);
            $forklift->username = Request::get('username');
            $forklift->password = Request::get('password');
            $forklift->save();

            Session::flash('message', 'Successfully updated Forklift User!');
            return Redirect::to('forklift');
        }
    }

    function destroy($id) {
        $decryptId = Crypt::decrypt($id);
        ForkliftUser::where('UserId', $decryptId)->delete();

        // redirect
        Session::flash('message', 'Successfully deleted the forklift user!');
        return Redirect::to('forklift');
    }
}
