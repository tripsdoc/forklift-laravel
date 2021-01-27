<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class SupervisorController extends Controller
{
    function getProc($mode, $warehouse) {
        $searchData = ($mode == 1) ? "''" : "'Export'";
        $expProc = ($mode == 1) ? "HSC2017.dbo.CntrPhoto_GetContainerList" : "HSC2017.dbo.CntrPhoto_GetExpContainerList";
        $data = DB::connection("sqlsrv2")->select("exec " .  $selectedProc . " '', '', 'D', " . $warehouse . ", " + $searchData);
        return $data;
    }

    function getImport(Request $request) {
        $data = $this->getProc(1, $request->warehouse);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }

    function getExport(Request $request) {
        $data = $this->getProc(2, $request->warehouse);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }

    function getAll(Request $request) {
        $import = getProc(1, $request->warehouse);
        $export = getProc(2, $request->warehouse);
        $dataImport = array();
        foreach($import as $key => $datas) {
            $newdata = $this->formatAll($datas, 'Import');
            array_push($dataImport, $newdata);
        }
        $dataExport = array();
        foreach($export as $key => $datas) {
            $newdata = $this->formatAll($datas, 'Export');
            array_push($dataExport, $newdata);
        }
        $dataArray = array_merge($dataImport, $dataExport);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }

    function formatAll($datas, $format) {
        $loopdata = new \stdClass();
        $loopdata->ETA = $datas->ETA;
        $loopdata->ClientID = $datas->ClientID;
        $loopdata->YardRemarks = $datas->YardRemarks;
        $loopdata->ContainerPrefix = $datas->ContainerPrefix;
        $loopdata->ContainerNumber = $datas->ContainerNumber;
        $loopdata->ContainerSize = $datas->ContainerSize;
        $loopdata->Bay = $datas->Bay;
        $loopdata->Driver = $datas->Driver;
        $loopdata->Status = $datas->Status;
        $loopdata->TT = $datas->TT;
        $loopdata->Remarks = $datas->Remarks;

        if($format == "Import") {
            $loopdata->Connect = $datas->ImpConnExp;
        } else {
            $loopdata->Connect = "";
        }

        //Import
        $loopdata->Pkgs = $datas->Pkgs;
        
        //Export
        $loopdata->POD = $datas->POD;
        $loopdata->SealNumber = $datas->SealNumber;
        return $loopdata;
        
    }
}
