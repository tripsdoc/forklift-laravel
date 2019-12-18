<?php

namespace App\Http\Controllers;
use DB;

use Illuminate\Http\Request;

class UnstuffingController extends Controller
{
    function getDetailImportsumary()
    {
        $detail = DB::select("select ji.ClientID, ci.SealNumber, ci.ContainerPrefix, ci.ContainerNumber,ci.ContainerSize, ci.ContainerType, ji.POD, ci.Bay, ci.Stevedore from HSC2017Test_V2.dbo.JobInfo ji, HSC2017Test_V2.dbo.ContainerInfo ci where ji.JobNumber = ci.JobNumber and ci.Dummy =  '" . $_GET['dummy'] . "'");
        $joblist = DB::select("select i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) Quantity, i.MQuantity, i.MVolume, i.Status, i.MWeight, i.POD, max(ib.Remarks) Remarks from HSC2017Test_V2.dbo.HSC_Inventory i, HSC2017Test_V2.dbo.HSC_InventoryPallet ip, HSC2017Test_V2.dbo.HSC_InventoryBreakdown ib where i.InventoryID = ip.InventoryID and ip.InventoryPalletID = ib.InventoryPalletID and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and i.CntrID = '" . $_GET['dummy'] . "' group by i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, i.MQuantity, i.MVolume, i.MWeight, i.Status, i.POD");
        $check   = array();
        foreach ($joblist as $key => $value)
        {
            if ($value->Quantity == $value->MQuantity)
            {
                array_push($check, 'ok');
            }
            else if ($value->HBL == 'OVERLANDED')
            {
                array_push($check, 'ok');
            }
            else if ($value->Quantity < $value->MQuantity)
            {
                // Check other breakdown have shortlanded
                $flags = array();
                $rawPallet = $this->Global_model->global_get('HSC_InventoryPallet', array(
                    'InventoryID' => $value->InventoryID,
                    'DelStatus' => 'N'
                ));
                foreach ($rawPallet as $key => $valuePallet)
                {
                    $rawBreakdown = $this->Global_model->global_get('HSC2017Test_V2.dbo.HSC_InventoryBreakdown', array(
                        'InventoryPalletID' => $valuePallet->InventoryPalletID,
                        'DelStatus' => 'N'
                    ));
                    foreach ($rawBreakdown as $keyBreak => $break)
                    {
                        if (in_array('SHORTLANDED', explode(',', $break->Flags))) {
                            array_push($flags, $break->Flags);
                        }
                    }
                }
                if (count($flags) >= 1) {
                    array_push($check, 'ok');
                } else {
                    array_push($check, 'bad');
                }

            }
            elseif ($value->Quantity > $value->MQuantity)
            {
                array_push($check, 'bad');
            }
            else
            {
                array_push($check, 'bad ');
            }
        }
        $data = array(
          'status' => 'success',
          'container' => $detail,
          'is_completed' => in_array('bad', $check) ? 'no' : 'yes'
        );
        return response($data);
    }
    function getJobList()
    {
        $joblist = DB::select("select i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) Quantity, i.MQuantity, i.MVolume, i.Status, i.MWeight, i.POD, max(ib.Remarks) Remarks from HSC2017Test_V2.dbo.HSC_Inventory i, HSC2017Test_V2.dbo.HSC_InventoryPallet ip, HSC2017Test_V2.dbo.HSC_InventoryBreakdown ib where i.InventoryID = ip.InventoryID and ip.InventoryPalletID = ib.InventoryPalletID and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and i.CntrID = '" . $_GET['dummy'] . "' group by i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, i.MQuantity, i.MVolume, i.MWeight, i.Status, i.POD");
        $data  = array(
            'status' => "success",
            'data' => $joblist
        );
        return response($data);
    }
    function getPalletBreakdown()
    {
        $pallet    = array();
        $breakdown = array();
        $rawPallet = DB::table('HSC2017Test_V2.dbo.HSC_InventoryPallet')->where('InventoryID', $_GET['inventoryid'])->where('DelStatus', 'N')->get();
        $i         = 1;
        foreach ($rawPallet as $key => $value) {
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
                "DN" => is_null($value->DN) ? "" : $value->DN,
            );
            array_push($pallet, $loopPallet);
            $rawBreakdown = DB::table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $value->InventoryPalletID)->where('DelStatus', 'N')->get();
            $x            = 1;
            $lastFrom     = null;
            foreach ($rawBreakdown as $keyBreak => $break) {
                $images = DB::table('HSC2017Test_V2.dbo.HSC_InventoryPhoto')->where('BreakDownID', $break->BreakDownID)->get();
                $lastFrom      = $break->InventoryPalletID;

                $flag = DB::table('HSC2017Test_V2.dbo.Checklist')->where('Category', 'type')->get();
                $flagSelected = array();

                $strExplode = array_map('trim', explode(',', $break->Flags));
                if ($break->Flags) {
                  foreach ($flag as $key => $fl) {
                    if (in_array($fl->Value, $strExplode))
                    {
                      array_push($flagSelected, true);
                    }else{
                      array_push($flagSelected, false);
                    }
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
                    "FlagsSelected" => is_null($flagSelected) ? "" : $flagSelected ,
                    "Tally" => is_null($break->Tally) ? "" : $break->Tally,
                    "Weight" => is_null($break->Weight) ? "" : $break->Weight,
                    "gallery" => $images
                );
                array_push($breakdown, $loopBreakdown);
            }
        }
        $typeChecklist = DB::table('Checklist')->where('Category', 'type')->get();
        $flagsChecklist = DB::table('Checklist')->where('Category', 'flag')->get();
        $locations = DB::table('Checklist')->where('Category', 'location')->get();
        $data = array(
            'status' => "success",
            'pallet' => $pallet,
            'breakdown' => $breakdown,
            'type' => $typeChecklist,
            'flags' => $flagsChecklist,
            'locations' => $locations
        );
        return response($data);
    }
}
