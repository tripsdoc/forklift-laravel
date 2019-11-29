<?php

namespace App\Http\Controllers;
use DB;

use Illuminate\Http\Request;

class UnstuffingController extends Controller
{
    function getDetailImportsumary()
    {
        $detail = DB::select("select ji.ClientID, ci.SealNumber, ci.ContainerPrefix, ci.ContainerNumber,ci.ContainerSize, ci.ContainerType, ji.POD, ci.Bay, ci.Stevedore from JobInfo ji, ContainerInfo ci where ji.JobNumber = ci.JobNumber and ci.Dummy =  '" . $_GET['dummy'] . "'");
        $joblist = DB::select("select i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) Quantity, i.MQuantity, i.MVolume, i.Status, i.MWeight, i.POD, max(ib.Remarks) Remarks from HSC_Inventory i, HSC_InventoryPallet ip, HSC_InventoryBreakdown ib where i.InventoryID = ip.InventoryID and ip.InventoryPalletID = ib.InventoryPalletID and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and i.CntrID = '" . $_GET['dummy'] . "' group by i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, i.MQuantity, i.MVolume, i.MWeight, i.Status, i.POD");
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
                    $rawBreakdown = $this->Global_model->global_get('HSC_InventoryBreakdown', array(
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
        $joblist = DB::select("select i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) Quantity, i.MQuantity, i.MVolume, i.Status, i.MWeight, i.POD, max(ib.Remarks) Remarks from HSC_Inventory i, HSC_InventoryPallet ip, HSC_InventoryBreakdown ib where i.InventoryID = ip.InventoryID and ip.InventoryPalletID = ib.InventoryPalletID and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and i.CntrID = '" . $_GET['dummy'] . "' group by i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, i.MQuantity, i.MVolume, i.MWeight, i.Status, i.POD");
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
        $rawPallet = DB::table('HSC_InventoryPallet')->where('InventoryID', $_GET['inventoryid'])->where('DelStatus', 'N')->get();
        $i         = 1;
        foreach ($rawPallet as $key => $value) {
            $loopPallet = array(
                "number" => $i++,
                "InventoryPalletID" => $value->InventoryPalletID,
                "InventoryID" => $value->InventoryID,
                "SequenceNo" => $value->SequenceNo,
                "ExpCntrID" => $value->ExpCntrID,
                "Reserved" => $value->Reserved,
                "ReservedBy" => $value->ReservedBy,
                "ReservedDt" => $value->ReservedDt,
                "ClearedDate" => $value->ClearedDate,
                "DeliveryID" => $value->DeliveryID,
                "CreatedBy" => $value->CreatedBy,
                "CreatedDt" => $value->CreatedDt,
                "UpdatedBy" => $value->UpdatedBy,
                "UpdatedDt" => $value->UpdatedDt,
                "DelStatus" => $value->DelStatus,
                "InterWhseFlag" => $value->InterWhseFlag,
                "CurrentLocation" => $value->CurrentLocation,
                "InterWhseTo" => $value->InterWhseTo,
                "Tag" => $value->Tag,
                "Location" => $value->Location,
                "DN" => $value->DN
            );
            array_push($pallet, $loopPallet);
            $rawBreakdown = DB::table('HSC_InventoryBreakdown')->where('InventoryPalletID', $value->InventoryPalletID)->where('DelStatus', 'N')->get();
            $x            = 1;
            $lastFrom     = null;
            foreach ($rawBreakdown as $keyBreak => $break) {
                $images = DB::table('HSC_InventoryPhoto')->where('BreakDownID', $break->BreakDownID)->get();
                $lastFrom      = $break->InventoryPalletID;
                $loopBreakdown = array(
                    'num_order' => $x++,
                    "InventoryPalletID" => $break->InventoryPalletID,
                    "BreakDownID" => $break->BreakDownID,
                    "Markings" => $break->Markings,
                    "Quantity" => $break->Quantity,
                    "Type" => $break->Type,
                    "Length" => $break->Length,
                    "Breadth" => $break->Breadth,
                    "Height" => $break->Height,
                    "Volume" => $break->Volume,
                    "Remarks" => $break->Remarks,
                    "CreatedBy" => $break->CreatedBy,
                    "CreatedDt" => $break->CreatedDt,
                    "UpdatedBy" => $break->UpdatedBy,
                    "UpdatedDt" => $break->UpdatedDt,
                    "DelStatus" => $break->DelStatus,
                    "Flags" => is_null($break->Flags) ? "" : $break->Flags,
                    "Tally" => $break->Tally,
                    "Weight" => $break->Weight,
                    "gallery" => $images
                );
                array_push($breakdown, $loopBreakdown);
            }
        }
        $checklist = DB::table('Checklist')->where('Category', 'type')->get();
        $data = array(
            'status' => "success",
            'pallet' => $pallet,
            'breakdown' => $breakdown,
            'type' => $checklist
        );
        return response($data);
    }
}
