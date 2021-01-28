<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class SupervisorController extends Controller
{
    function getProc($mode, $warehouse) {
        $searchData = ($mode == 1) ? "''" : "'Export'";
        $selectedProc = ($mode == 1) ? "HSC2017.dbo.CntrPhoto_GetContainerList" : "HSC2017.dbo.CntrPhoto_GetExpContainerList";
        $data = DB::connection("sqlsrv2")->select("exec " .  $selectedProc . " '', '', 'D', '" . $warehouse . "', " . $searchData);
        return $data;
    }

    function getImport(Request $request) {
        $data = $this->getProc(1, $request->warehouse);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }

    function debugImport() {
        $data = $this->getProc(1, "#02-109");
        $dataImport = array();
        foreach($data as $key => $datas) {
            $newdata = $this->formatImport($datas, 'Import');
            array_push($dataImport, $newdata);
        }
        $response['status'] = (count($dataImport) > 0)? TRUE : FALSE;
        $response['data'] = $dataImport;
        return response($response);
    }

    function formatImport($datas) {
        $loopdata = new \stdClass();
        $loopdata->Client = $datas->ClientID;
        $loopdata->ImpConnExp = $datas->ImpConnExp;
        $loopdata->Status = $datas->Status;
        $loopdata->ETA = $datas->ETA;
        return $loopdata;
    }

    function getExport(Request $request) {
        $data = $this->getProc(2, $request->warehouse);
        $response['status'] = (count($data) > 0)? TRUE : FALSE;
        $response['data'] = $data;
        return response($response);
    }

    function getAll(Request $request) {
        $import = $this->getProc(1, $request->warehouse);
        $export = $this->getProc(2, $request->warehouse);
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
        $response['status'] = (count($dataArray) > 0)? TRUE : FALSE;
        $response['data'] = $dataArray;
        return response($response);
    }

    function getConnection(Request $request) {
        $data = DB::connection("sqlsrv2")->select("exec HSC2017.dbo.CntrConnImpExp '', '" . $request->dateStart . "', '" . $request->dateEnd . "', '" . $request->warehouse . "', 'ImpConnExp'");
        $dataArray = array();
        foreach($data as $key => $datas) {
            $newdata = $this->formatConnection($datas);
            array_push($dataArray, $newdata);
        }
        $response['status'] = (count($dataArray) > 0)? TRUE : FALSE;
        $response['data'] = $dataArray;
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
        $loopdata->DeliverTo = $datas->DeliverTo;

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

    function formatConnection($datas) {
        $loopdata = new \stdClass();
        $loopdata->ClientID = $datas->ClientID;
        $loopdata->DeliverTo = $datas->DeliveryToExp;
        $loopdata->Status = $datas->StatusImp;
        $loopdata->ContainerPrefix = $datas->CntrPrefix;
        $loopdata->ContainerNumber = $datas->CntrNumber;
        $loopdata->ETA = $datas->ETA;
        $loopdata->POD = $datas->POD;
        $loopdata->ETA1 = $datas->ETA1;
        $loopdata->Vol = $datas->Vol;
        $loopdata->DG = $datas->DG;
        return $loopdata;
    }
}
