<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Request;
use App\IPSUser;
use View;
use DataTables;
use Validator;
use Redirect;
use Session;

class IPSUserController extends Controller
{
    function index() {
        return view('ips/index');
    }

    function jsonAll() {
        if (request()->ajax()) {
            $data = IPSUser::all();
            return Datatables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        $btn = '<a href="../ips/' . Crypt::encrypt($row->UserId) . '/edit" class="edit btn btn-warning btn-sm">Edit</a>
                                <form id="form-delete" style="display: inline-block;" class="pull-left" action="../ips/' . Crypt::encrypt($row->UserId) . '" method="POST">'
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
      
        return view('ips/index');
    }

    function show($id) {
        $decryptId = Crypt::decrypt($id);
        $data = IPSUser::find($decryptId);
        return View::make('ips/show')
        ->with('data', $data);
    }

    function create() {
        return View::make('ips.create');
    }

    function store() {
        $rules = array(
            'username' => 'required',
            'password' => 'required'
        );
        $validator = Validator::make(Request::all(), $rules);

        if($validator->fails()) {
            return Redirect::to('ips/create')
            ->withErrors($validator)
            ->withInput(Request::except('password'));
        } else {
            $forklift = new IPSUser;
            $forklift->username = Request::get('username');
            $forklift->password = Request::get('password');
            $forklift->save();

            return Redirect::to('ips');
        }
    }

    function edit($id) {
        $decryptId = Crypt::decrypt($id);
        $data = IPSUser::find($decryptId);
        $cryptData = new \stdClass();
        $cryptData->UserId = Crypt::encrypt($data->UserId);
        $cryptData->UserName = $data->UserName;
        return View::make('ips.edit')
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
            return Redirect::to('ips/' . $id . '/edit')
            ->withErrors($validator)
            ->withInput(Request::except('password'));
        } else {
            $forklift = IPSUser::find($decryptId);
            $forklift->username = Request::get('username');
            $forklift->password = Request::get('password');
            $forklift->save();

            Session::flash('message', 'Successfully updated IPS User!');
            return Redirect::to('ips');
        }
    }

    function destroy($id){
        $decryptId = Crypt::decrypt($id);
        $data = IPSUser::find($decryptId);
        $data->delete();
        Session::flash('message', 'Successfully deleted the IPS User!');
        return Redirect::to('ips');
    }
}
