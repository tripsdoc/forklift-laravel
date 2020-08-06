<?php

namespace App\Http\Controllers;

use DB;
use Response;
use Illuminate\Http\Request;

class StuffingController extends Controller
{
    public function exportSummary(Request $request)
    {
        $query = DB::connection("sqlsrv3")->select("SELECT CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, CI.Dummy, CI.NTunstuffingstatus, CI.TallyBy, JI.ClientID, JI.POD, SUM(IB.Quantity) Qty FROM HSC2017.dbo.HSC_InventoryPallet IP, HSC2012.dbo.ContainerInfo CI, HSC2012.dbo.JobInfo JI, HSC2017.dbo.HSC_InventoryBreakdown IB WHERE IP.ExpCntrID = CI.Dummy AND CI.JobNumber = JI.JobNumber AND IP.InventoryPalletID = IB.InventoryPalletID AND IP.DelStatus = 'N' AND IB.DelStatus = 'N' AND CI.Status <> 'CANCELLED' AND CI.[DateofStuf/Unstuf] IS NULL GROUP BY CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, CI.NTunstuffingstatus, CI.TallyBy, JI.ClientID, JI.POD, CI.Dummy");
        $data  = array(
            'data' => $query
        );
        // dd($data);
        return response($data);
    }
    public function detailExport(Request $request)
    {
        $query = DB::connection("sqlsrv3")->select("select ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, ci.SealNumber, ci.BkRef, ci.YardRemarks, sum(ib.Quantity) TotalQty, sum(ib.Volume) TotalVolume, count(distinct case when ip.Tag = '' then null else ip.Tag end) TotalTag, count(distinct ip.InventoryPalletID) TotalPallet, ci.Bay, ci.Stevedore, HSC2017.dbo.fn_GetDGClass(ip.ExpCntrID, '', 'TEMP_EXP_PLANNING') DgClass, rtrim(ltrim(max(ExpRemarks.Remarks))) ExportRemarks, ci.SevenPoints from HSC2017.dbo.HSC_Inventory I inner join HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join HSC2012.dbo.ContainerInfo CI on CI.Dummy = IP.ExpCntrID inner join HSC2012.dbo.JobInfo JI on ji.JobNumber = ci.JobNumber inner join HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID left join HSC2017.dbo.HSC_TempExpPlanRemarks ExpRemarks on ip.ExpCntrID = ExpRemarks.CntrIDExp and ExpRemarks.DelStatus = 'N' where ip.ExpCntrID = '" . $request->get("CntrID") . "' and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' group by ip.ExpCntrID, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, ci.SealNumber, ci.BkRef, ci.YardRemarks, ci.Bay, ci.Stevedore, ci.SevenPoints");
        $checklistBay = DB::table('HSC2017Test_V2.dbo.Checklist')->where('Category', 'Bay')->get();
        $checklistStevedore = DB::table('HSC2017Test_V2.dbo.Checklist')->where('Category', 'Stevedore')->get();
        $checklist7P = DB::table('HSC2017Test_V2.dbo.Checklist')->where('Category', '7p')->get();
        $InventoryList = DB::connection("sqlsrv3")->select("select ci.Dummy, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, i.InventoryID, i.SequenceNo, i.Status, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) TotalQty, i.MQuantity, i.MWeight, i.MVolume, count(distinct case when ip.Tag = '' then null else ip.Tag end) TotalTag, TagList = STUFF((
            SELECT ', '+ ISNULL(IP1.Tag,'') AS [text()]
            FROM HSC2017.dbo.HSC_InventoryPallet IP1
            WHERE IP1.ExpCntrID = IP.ExpCntrID
              AND Tag <> ''
            FOR XML PATH('')
          ), 1, 2,'') from HSC2017.dbo.HSC_Inventory I inner join HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join HSC2012.dbo.ContainerInfo CI on CI.Dummy = IP.ExpCntrID inner join HSC2012.dbo.JobInfo JI on ji.JobNumber = ci.JobNumber inner join HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID where ip.ExpCntrID = '" . $request->get("CntrID") . "' and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' group by ip.ExpCntrID, ci.Dummy, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, i.InventoryID, i.Status, i.SequenceNo, i.SequencePrefix, i.HBL, i.MWeight, i.MVolume, i.MQuantity order by i.SequenceNo, i.SequencePrefix");
        $checkIsCompleted = array();
        if ($InventoryList) {
            foreach ($InventoryList as $key => $valueCheckTag) {
                if ($valueCheckTag->TotalTag == 0) {
                    array_push($checkIsCompleted, true);
                } else {
                    array_push($checkIsCompleted, false);
                }
            }
        }
        $seventPointSelected = array();
        if ($query) {
            // $flagShow = array();
                    // dd($flag);
            $strExplode = array_map('trim', explode(',', $query[0]->SevenPoints));
            if ($query[0]->SevenPoints)
            {
                foreach ($checklist7P as $key => $fl)
                {
                    if (in_array($fl->Value, $strExplode))
                    {
                        array_push($seventPointSelected, true);
                    }
                    else
                    {
                        array_push($seventPointSelected, false);
                    }
                }
            }
            else
            {
                foreach ($checklist7P as $key => $fl)
                {
                    array_push($seventPointSelected, false);
                }
            }
        }
        $data  = array(
            'data' => $query ? $query : null,
            'bay' => $checklistBay,
            'stevedore' => $checklistStevedore,
            'sevenPoints' => $checklist7P,
            'sevenPointsSelected' => $seventPointSelected,
            'isCompleted' => in_array("true", $checkIsCompleted) ? true : false
        );
        // dd($data);
        return response($data);
    }
    function updateContainerInfo(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2012.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
            $request->get('type') => $request->get('data')
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function getCurrentContainer(Request $request)
    {
        $counter = DB::connection("sqlsrv3")->table('HSC2012.dbo.ContainerInfo')->where('TallyBy', $request->get('username'))->where('NTunstuffingstatus', 'PROCESSING_STUFFING')->first();
        $count   = DB::connection("sqlsrv3")->table('HSC2012.dbo.ContainerInfo')->where('TallyBy', $request->get('username'))->where('NTunstuffingstatus', 'PROCESSING_STUFFING')->count();
        $data    = array(
            'dummy' => $counter ? $counter->Dummy : "",
            'counter' => $count
        );
        return response($data);
    }
    public function startJob(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2012.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
            'TallyBy' => $request->get('TallyBy'),
            'StartTime' => date("Y-m-d H:i:s"),
            'NTunstuffingstatus' => "PROCESSING_STUFFING",
        ));
        // $dir = '../../../photos/';

        // if (is_dir($dir)) {
        //     if ($dh = opendir($dir)) {
        //         $uold     = umask(0);
        //         mkdir($dir . $request->get('dummy'), 0775);
        //         umask($uold);
        //     }
        // }
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function finishJob(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2012.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
            'TallyBy' => $request->get('TallyBy'),
            'Status' => 'EMPTY',
            'EndTime' => date("Y-m-d H:i:s"),
            'DateofStuf/Unstuf' => date("Y-m-d H:i:s"),
            'NTunstuffingstatus' => "COMPLETED",
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    public function InventoryList(Request $request)
    {
        $query = DB::connection("sqlsrv3")->select("select ci.Dummy, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, i.InventoryID, i.SequenceNo, i.Status, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) TotalQty, i.MQuantity, i.MWeight, i.MVolume, count(distinct case when ip.Tag = '' then null else ip.Tag end) TotalTag, TagList = STUFF((
            SELECT ', '+ ISNULL(IP1.Tag,'') AS [text()]
            FROM HSC2017.dbo.HSC_InventoryPallet IP1
            WHERE IP1.ExpCntrID = IP.ExpCntrID
              AND Tag <> ''
            FOR XML PATH('')
          ), 1, 2,'') from HSC2017.dbo.HSC_Inventory I inner join HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join HSC2012.dbo.ContainerInfo CI on CI.Dummy = IP.ExpCntrID inner join HSC2012.dbo.JobInfo JI on ji.JobNumber = ci.JobNumber inner join HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID where ip.ExpCntrID = '" . $request->get("CntrID") . "' and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' group by ip.ExpCntrID, ci.Dummy, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, i.InventoryID, i.Status, i.SequenceNo, i.SequencePrefix, i.HBL, i.MWeight, i.MVolume, i.MQuantity order by i.SequenceNo, i.SequencePrefix");
        $data    = array(
            'data' => $query
        );
        // dd($query);
        return response($data);
    }
    public function updateShutout(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_ImpExpConnect')->where('InventoryIDImp', $request->get('InventoryID'))->update(array(
            'DelStatus' => 'Y'
        ));
        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryID', $request->get('InventoryID'))->update(array(
            'ExpCntrID' => '',
            'ClearedDate' => null
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function getPalletBreakdown(Request $request)
    {
        $pallet    = array();
        $breakdown = array();
        $rawPallet = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryID', $request->get('InventoryID'))->where('DelStatus', 'N')->orderBy('InventoryPalletID', 'ASC')->get();
        $i         = 1;
        $DeliveryID = array();
        // dd($rawPallet);
        foreach ($rawPallet as $key => $value)
        {
            $loopPallet = array(
                "number" => $i++,
                "InventoryPalletID" => is_null($value->InventoryPalletID) ? "" : $value->InventoryPalletID,
                "InventoryID" => is_null($value->InventoryID) ? "" : $value->InventoryID,
                "SequenceNo" => is_null($value->SequenceNo) ? "" : $value->SequenceNo,
                "ExpCntrID" => is_null($value->ExpCntrID) ? "" : $value->ExpCntrID,
                "Reserved" => is_null($value->Reserved) ? "" : $value->Reserved,
                "ReservedBy" => is_null($value->ReservedBy) ? "" : $value->ReservedBy,
                "ReservedDt" => is_null($value->ReservedDt) ? "" : $value->ReservedDt,
                "ClearedDate" => is_null($value->ClearedDate) ? "" : $value->ClearedDate,
                "DeliveryID" => is_null($value->DeliveryID) ? "" : $value->DeliveryID,
                "CreatedBy" => is_null($value->CreatedBy) ? "" : $value->CreatedBy,
                "CreatedDt" => is_null($value->CreatedDt) ? "" : $value->CreatedDt,
                "UpdatedBy" => is_null($value->UpdatedBy) ? "" : $value->UpdatedBy,
                "UpdatedDt" => is_null($value->UpdatedDt) ? "" : $value->UpdatedDt,
                "DelStatus" => is_null($value->DelStatus) ? "" : $value->DelStatus,
                "InterWhseFlag" => is_null($value->InterWhseFlag) ? "" : $value->InterWhseFlag,
                "CurrentLocation" => is_null($value->CurrentLocation) ? "" : $value->CurrentLocation,
                "InterWhseTo" => is_null($value->InterWhseTo) ? "" : $value->InterWhseTo,
                "Tag" => is_null($value->Tag) ? "" : $value->Tag,
                "Location" => is_null($value->Location) ? "" : $value->Location,
            );
            array_push($DeliveryID, is_null($value->DeliveryID) ? 0 : 1);
            array_push($pallet, $loopPallet);
            $rawBreakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown AS IB')->join('HSC2017.dbo.HSC_TempExpPlan AS ExpPlan', 'ExpPlan.BreakDownIDImp', '=', 'IB.BreakDownID')->where('IB.InventoryPalletID', $value->InventoryPalletID)->where('IB.DelStatus', 'N')->where('ExpPlan.DelStatus', 'N')->select('IB.*', DB::raw("rtrim(ltrim(case when isnull(ExpPlan.DNS, 0) = 1 then '*Do Not Stack' else '' end + ' ' +
            case when isnull(ExpPlan.TakePhoto, 0) = 1 then '*Take Photo' else '' end + ' ' + ExpPlan.Others)) SpecialInstruction"))->orderBy('IB.BreakDownID', 'ASC')->get();
            // $rawBreakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $value->InventoryPalletID)->where('DelStatus', 'N')->orderBy('BreakDownID', 'ASC')->get();
            // dd($rawBreakdown);
            $x            = 1;
            $lastFrom     = null;
            foreach ($rawBreakdown as $keyBreak => $break)
            {
                $galleries = array();
                $images   = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->where('BreakDownID', $break->BreakDownID)->where('DelStatus', 'N')->get();
                foreach($images as $gallery)
                {
                    $loadImage = str_replace("//server-db/Files/Photo","http://192.168.14.70:9030/",$gallery->PhotoNameSystem);
                    $loadPath = str_replace("//server-db/Files/Photo","",$gallery->PhotoNameSystem);
                    $file = '../../../photos' . $loadPath;
                    if (file_exists($file)) {
                        list($width, $height) = getimagesize($loadImage);
                        $imageGallery = array(
                            'width' => $width,
                            'InventoryPhotoID' => $gallery->InventoryPhotoID,
                            'PhotoName' => $gallery->PhotoName,
                            'PhotoExt' => $gallery->PhotoExt,
                            'PhotoNameSystem' => $loadImage
                        );
                        array_push($galleries, $imageGallery);
                    }
                }
                $lastFrom = $break->InventoryPalletID;

                $flag         = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'flag')->get();
                $flagSelected = array();
                $flagShow = array();
                // dd($flag);
                $strExplode = array_map('trim', explode(',', $break->Flags));
                if ($break->Flags)
                {
                    foreach ($flag as $key => $fl)
                    {
                        if (in_array($fl->Value, $strExplode))
                        {
                            array_push($flagSelected, true);
                            array_push($flagShow, $fl->Value);
                        }
                        else
                        {
                            array_push($flagSelected, false);
                        }
                    }
                }else{
                  foreach ($flag as $key => $fl)
                  {
                      array_push($flagSelected, false);
                  }
                }
                $loopBreakdown = array(
                    'num_order' => $x++,
                    "InventoryPalletID" => is_null($break->InventoryPalletID) ? "" : $break->InventoryPalletID,
                    "BreakDownID" => is_null($break->BreakDownID) ? "" : $break->BreakDownID,
                    "Markings" => is_null($break->Markings) ? "" : $break->Markings,
                    "Quantity" => is_null($break->Quantity) ? "" : $break->Quantity,
                    "Type" => is_null($break->Type) ? "" : $break->Type,
                    "Length" => is_null($break->Length) ? "" : $break->Length,
                    "Breadth" => is_null($break->Breadth) ? "" : $break->Breadth,
                    "Height" => is_null($break->Height) ? "" : $break->Height,
                    "Volume" => is_null($break->Volume) ? "" : $break->Volume,
                    "Remarks" => is_null($break->Remarks) ? "" : $break->Remarks,
                    "CreatedBy" => is_null($break->CreatedBy) ? "" : $break->CreatedBy,
                    "CreatedDt" => is_null($break->CreatedDt) ? "" : $break->CreatedDt,
                    "UpdatedBy" => is_null($break->UpdatedBy) ? "" : $break->UpdatedBy,
                    "UpdatedDt" => is_null($break->UpdatedDt) ? "" : $break->UpdatedDt,
                    "DelStatus" => is_null($break->DelStatus) ? "" : $break->DelStatus,
                    "Flags" => is_null($break->Flags) ? "" : $break->Flags,
                    "Flags_show" => is_null($flagShow) ? "" : implode (", ", $flagShow),
                    "FlagsSelected" => is_null($flagSelected) ? "" : $flagSelected,
                    "Tally" => is_null($break->Tally) ? "" : $break->Tally,
                    "Weight" => is_null($break->Weight) ? "" : $break->Weight,
                    "gallery" => $galleries,
                    "SpecialInstruction" => ""
                    // "SpecialInstruction" => is_null($break->SpecialInstruction) ? "" : $break->SpecialInstruction,
                );
                array_push($breakdown, $loopBreakdown);
            }
        }
        $typeChecklist  = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'type')->get();
        $flagsChecklist = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'flag')->get();
        $locations      = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'location')->get();
        // dd($DeliveryID);
        $data           = array(
            'status' => "success",
            'pallet' => $pallet,
            'breakdown' => $breakdown,
            'type' => $typeChecklist,
            'flags' => $flagsChecklist,
            'locations' => $locations,
            'DeliveryID' => in_array(1, $DeliveryID)
        );
        // dd($data);
        return response($data);
    }
}
