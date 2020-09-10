<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GlobalController extends Controller
{
      function getChecklist()
      {
        Log::debug('DEBUG QUERY -  GETING FROM CHECKLIST');
          $checklist = DB::table('HSC_IPS.dbo.Checklist')->where('Category', $_GET['type'])->get();
          $data = array(
            'status' => 'success',
            'data' => $checklist
          );
          return response($data);
      }
}
