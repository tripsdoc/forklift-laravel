<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;

class GlobalController extends Controller
{
      function getChecklist()
      {
          $checklist = DB::table('HSC2017Test_V2.dbo.Checklist')->where('Category', $_GET['type'])->get();
          $data = array(
            'status' => 'success',
            'data' => $checklist
          );
          return response($data);
      }
}
