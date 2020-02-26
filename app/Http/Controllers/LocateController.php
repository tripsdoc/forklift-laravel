<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;
use App\InventoryPallet;

class LocateController extends Controller
{
    function getContainerList() {
      $container =  DB::select("SELECT CI.[Dummy], JI.[ClientID], JI.[POD], CI.[ContainerPrefix], CI.[ContainerNumber], CI.[ContainerSize], CI.[ContainerType], CI.[Status], VI.[ETA], CI.[DeliverTo], CI.[TT] FROM HSC2012.dbo.VesselInfo VI, HSC2012.dbo.JobInfo JI, HSC2012.dbo.ContainerInfo CI WHERE VI.VesselID = JI.VesselID AND JI.JobNumber = CI.JobNumber AND JI.[Import/Export] = 'Export' AND CI.[DateofStuf/Unstuf] IS NULL AND CI.StartTime IS NULL AND CI.DeliverTo IN ('110','108','109') AND EXISTS (SELECT 1 FROM InventoryPallet IP WHERE IP.ExpCntrID = CI.[Dummy] AND IP.DelStatus = 'N') ORDER BY VI.ETA");
      /* $data = array(
        'status' => 'success',
        'container' => $container
      ); */
      $newdata = array();
      foreach($container as $key => $datas) {
        $isActivityForStuffing = InventoryPallet::where('ExpCntrID', $datas->Dummy)->pluck('isActivityForStuffing')->first();
        $loopData = array(
          "isActivityForStuffing" => $isActivityForStuffing,
          "Dummy" => $datas->Dummy,
          "ClientID" => $datas->ClientID,
          "POD" => $datas->POD,
          "ContainerPrefix" => $datas->ContainerPrefix,
          "ContainerNumber" => ($datas->ContainerNumber != null) ? $datas->ContainerNumber : "-",
          "ContainerSize" => $datas->ContainerSize,
          "ContainerType" => $datas->ContainerType,
          "Status" => $datas->Status,
          "ETA" => $datas->ETA,
          "DeliverTo" => $datas->DeliverTo,
          "TT" => $datas->TT
        );
        array_push($newdata, $loopData);
      }
      $response['status'] = (count($container) > 0)? TRUE : FALSE;
      $response['container'] = $newdata;
      return response($response);
    }

    function updateStuffing(Request $request) {
      try {
        $inventory = InventoryPallet::where('ExpCntrID', $request->dummy)
        ->update([
          'isActivityForStuffing' => $request->data
        ]);
        $response['status'] = TRUE;
        $response['data'] = $inventory;
        return response($response);
      } catch (\Illuminate\Database\QueryException $ex) {
        $response['status'] = TRUE;
        $response['errmsg'] = "Cannot update data!";
        return response($response);
      }
    }

    function getAllTagsByCN(Request $request) {
      $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
      $getwarehouse = $_GET['warehouse'];
      if($getwarehouse == "fullmap") {
          $datawarehouse = ['108', '109', '110'];
      }
      else if ($getwarehouse == "full_12x") {
          $datawarehouse = ['121', '122'];
      } else {
          $datawarehouse = array_map('trim', explode(",", $getwarehouse));
      }
      $result = DB::table('HSC2012.dbo.JobInfo AS JI')
      ->join('HSC2012.dbo.ContainerInfo AS CI', 'JI.JobNumber', '=', 'CI.JobNumber')
      ->join('Inventory AS I', 'CI.Dummy', '=', 'I.CntrID')
      ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
      ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
      ->where('I.DelStatus', '=', 'N')
      ->where('IP.DelStatus', '=', 'N')
      ->whereRaw("IP.Tag <> '' ")
      ->where('JI.ClientID', $_GET['clientID'])
      ->where('CI.ContainerNumber', $_GET['containerNumber']);
      $result->Where(function($query) use($datawarehouse)
      {
          for($i=0;$i<count($datawarehouse);$i++){
              if($i == 0) {
                  $query->where('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '"%');
              } else {
                  $query->orWhere('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '"%');
              }
          }
      });
      $result->select('IP.Tag', 'CI.ContainerNumber', 'JI.ClientID', DB::raw("CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'IMPORT' END TagColor"));
      $data = $result->get();
      $response['status'] = (count($data) > 0)? TRUE : FALSE;
      $response['data'] = $data;
      return response($response);
  }

  function debug() {
    /* $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $getwarehouse = $_GET['warehouse'];
    if($getwarehouse == "fullmap") {
        $datawarehouse = ['108', '109', '110'];
    }
    else if ($getwarehouse == "full_12x") {
        $datawarehouse = ['121', '122'];
    } else {
        $datawarehouse = array_map('trim', explode(",", $getwarehouse));
    } */
    $result = DB::table('HSC2012.dbo.JobInfo AS JI')
    ->join('HSC2012.dbo.ContainerInfo AS CI', 'JI.JobNumber', '=', 'CI.JobNumber')
    ->join('Inventory AS I', 'CI.Dummy', '=', 'I.CntrID')
    ->join('InventoryPallet AS IP', 'I.InventoryID', '=', 'IP.InventoryID')
    ->join('TagLocationLatest AS TL', 'IP.Tag', '=', 'TL.Id')
    ->where('I.DelStatus', '=', 'N')
    ->where('IP.DelStatus', '=', 'N')
    ->whereRaw("IP.Tag <> '' ")
    ->where('CI.ContainerNumber', $_GET['containerNumber']);;
    /* $result->Where(function($query) use($datawarehouse)
    {
        for($i=0;$i<count($datawarehouse);$i++){
            if($i == 0) {
                $query->where('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '"%');
            } else {
                $query->orWhere('TL.Zones', 'like', '%"name": "' . $datawarehouse[$i] . '"%');
            }
        }
    }); */
    $result->select('IP.Tag', 'CI.ContainerNumber', 'JI.ClientID', 'TL.Zones', DB::raw("CASE WHEN I.StorageDate IS NOT NULL THEN 'EXPORT' WHEN ISNULL(I.POD,'') <> '' THEN 'TRANSHIPMENT' ELSE 'IMPORT' END TagColor"));
    $data = $result->get();
    $response['status'] = (count($data) > 0)? TRUE : FALSE;
    $response['data'] = $data;
    return response($response);
  }
}
