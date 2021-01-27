<?php

namespace App\Http\Controllers;

use DB;
use Response;
use Illuminate\Http\Request;
use App\ShifterUser;

class LoginController extends Controller
{
    function getUserData() {
        $dataUser = DB::table('HSC2017.dbo.IPS_ForkliftUser')->get();
        return response($dataUser);
    }

    function login(Request $request) {
        $check = DB::table('HSC2017.dbo.IPS_ForkliftUser')
        ->where('UserName', $request->username)
        ->where('Password', $request->password)->first();

        if(!$check) {
            $response['status'] = FALSE;
            $response['error'] = 'Username or Password not correct';
            $response['profile'] = $check;
        } else {
            $response['status'] = TRUE;
            $response['error'] = '';
            $response['profile'][0] = $check;
        }
        return response($response);
    }
    function loginClerk(Request $request)
    {
        $check = DB::connection("sqlsrv3")->table('HSC2017.dbo.IPS_IpsUser')
        ->where('UserName', $request->username)
        ->where('Password', $request->password)->first();

        if(!$check) {
            $response['status'] = FALSE;
            $response['error'] = 'Username or Password not correct';
            $response['profile'] = $check;
        }else{
          $response['status'] = TRUE;
          $response['error'] = '';
          $response['profile'][0] = $check;
          return response($response);
        }
    }
    function loginShifter(Request $request) {
        $check = ShifterUser::where('UserName', $request->username)
        ->where('Password', $request->password)->first();
        
        if(!$check) {
            $response['status'] = FALSE;
            $response['error'] = 'Username or Password not correct';
            $response['profile'] = $check;
        } else {
            $response['status'] = TRUE;
            $response['error'] = '';
            $response['profile'][0] = $check;
        }
        return response($response);
    }
}
