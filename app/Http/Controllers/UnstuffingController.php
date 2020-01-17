<?php
namespace App\Http\Controllers;

date_default_timezone_set('Asia/Singapore');
use DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class UnstuffingController extends Controller
{
    function getCurrentContainer(Request $request)
    {
        $counter = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.ContainerInfo')->where('TallyBy', $request->get('username'))->where('NTunstuffingstatus', 'PROCESSING')->first();
        $count   = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.ContainerInfo')->where('TallyBy', $request->get('username'))->where('NTunstuffingstatus', 'PROCESSING')->count();
        $data    = array(
            'dummy' => $counter ? $counter->Dummy : "",
            'counter' => $count
        );
        return response($data);
    }
    function getDetailImportsumary()
    {
        $detail  = DB::connection("sqlsrv3")->select("select ji.ClientID, ci.SealNumber, ci.ContainerPrefix, ci.ContainerNumber,ci.ContainerSize, ci.ContainerType, ji.POD, ci.Bay, ci.Stevedore from HSC2017Test_V2.dbo.JobInfo ji, HSC2017Test_V2.dbo.ContainerInfo ci where ji.JobNumber = ci.JobNumber and ci.Dummy =  '" . $_GET['dummy'] . "'");
        $joblist = DB::connection("sqlsrv3")->select("select i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) Quantity, i.MQuantity, i.MVolume, i.Status, i.MWeight, i.POD, max(ib.Remarks) Remarks from HSC2017Test_V2.dbo.HSC_Inventory i, HSC2017Test_V2.dbo.HSC_InventoryPallet ip, HSC2017Test_V2.dbo.HSC_InventoryBreakdown ib where i.InventoryID = ip.InventoryID and ip.InventoryPalletID = ib.InventoryPalletID and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and i.CntrID = '" . $_GET['dummy'] . "' group by i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, i.MQuantity, i.MVolume, i.MWeight, i.Status, i.POD");
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
                $flags     = array();
                $rawPallet = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryPallet')->where('InventoryID', $value->InventoryID)->where('DelStatus', 'N')->get();
                // $rawPallet = $this->Global_model->global_get('HSC2017Test_V2.dbo.HSC_InventoryPallet', array(
                //     'InventoryID' => $value->InventoryID,
                //     'DelStatus' => 'N'
                // ));
                foreach ($rawPallet as $key => $valuePallet)
                {
                    // $rawBreakdown = $this->Global_model->global_get('HSC2017Test_V2.dbo.HSC_InventoryBreakdown', array(
                    //     'InventoryPalletID' => $valuePallet->InventoryPalletID,
                    //     'DelStatus' => 'N'
                    // ));
                    $rawBreakdown = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $valuePallet->InventoryPalletID)->where('DelStatus', 'N')->get();
                    foreach ($rawBreakdown as $keyBreak => $break)
                    {
                        // dd($break);
                        if (in_array('SHORTLANDED', explode(',', $break->Flags)))
                        {
                            array_push($flags, $break->Flags);
                        }
                    }
                }
                if (count($flags) >= 1)
                {
                    array_push($check, 'ok');
                }
                else
                {
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
    public function detailimportsummary()
    {
        $query = DB::connection("sqlsrv3")->select("select SequenceNo, SequencePrefix, HBL, POD, Note, MQuantity, Status from HSC2017Test_V2.dbo.HSC_Inventory where CntrID ='" . $_GET['dummy'] . "' and Delstatus = 'N'");
        $data  = array(
            'data' => $query
        );
        return response($data);
    }
    function updateBaySteveDore(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
            $request->get('type') => $request->get('data')
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function startJob(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
            'TallyBy' => $request->get('TallyBy'),
            'StartTime' => date("Y-m-d H:i:s"),
            'NTunstuffingstatus' => "PROCESSING",
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function finishJob(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
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
    function getJobList(Request $request)
    {
        $joblist = DB::connection("sqlsrv3")->select("select i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) Quantity, i.MQuantity, i.MVolume, i.Status, i.MWeight, i.POD, max(ib.Remarks) Remarks from HSC2017Test_V2.dbo.HSC_Inventory i, HSC2017Test_V2.dbo.HSC_InventoryPallet ip, HSC2017Test_V2.dbo.HSC_InventoryBreakdown ib where i.InventoryID = ip.InventoryID and ip.InventoryPalletID = ib.InventoryPalletID and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and i.CntrID = '" . $_GET['dummy'] . "' group by i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, i.MQuantity, i.MVolume, i.MWeight, i.Status, i.POD");
        $container = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->first();
        $data    = array(
            'ount' => count($joblist),
            'status' => "success",
            'data' => $joblist,
            'floorboard' => $container->Floorboard
        );
        return response($data);
    }
    function addOverlanded(Request $request)
    {
        $joblist = DB::connection("sqlsrv3")->select("select i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) Quantity, i.MQuantity, i.MVolume, i.Status, i.MWeight, i.POD, max(ib.Remarks) Remarks from HSC2017Test_V2.dbo.HSC_Inventory i, HSC2017Test_V2.dbo.HSC_InventoryPallet ip, HSC2017Test_V2.dbo.HSC_InventoryBreakdown ib where i.InventoryID = ip.InventoryID and ip.InventoryPalletID = ib.InventoryPalletID and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and i.CntrID = '" . $request->get('Dummy') . "' group by i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, i.MQuantity, i.MVolume, i.MWeight, i.Status, i.POD");
        // dd($request->get('Dummy'))
        $inventory         = array(
            'CntrID' => $request->get('Dummy'),
            'SequenceNo' => count($joblist) + 1,
            'HBL' => 'OVERLANDED',
            'DelStatus' => 'N'
        );
        $InventoryID   = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_Inventory')->insertGetId($inventory);
        $pallet            = array(
            'InventoryID' => $InventoryID,
            'SequenceNo' => 1,
            'DelStatus' => 'N'
        );
        $inventoryPalletID   = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryPallet')->insertGetId($pallet);
        $breakdown            = array(
            'InventoryPalletID' => $inventoryPalletID,
            'Remarks' => 'OVERLANDED',
            'DelStatus' => 'N'
        );
        $breakdownId   = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->insertGetId($breakdown);
        $data    = array(
            'status' => "success",
            'data' => $inventory
        );
        return response($data);
    }
    function getPalletBreakdown()
    {
        $pallet    = array();
        $breakdown = array();
        $rawPallet = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryPallet')->where('InventoryID', $_GET['inventoryid'])->where('DelStatus', 'N')->get();
        $i         = 1;
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
                "DN" => is_null($value->DN) ? "" : $value->DN
            );
            array_push($pallet, $loopPallet);
            $rawBreakdown = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $value->InventoryPalletID)->where('DelStatus', 'N')->get();
            $x            = 1;
            $lastFrom     = null;
            foreach ($rawBreakdown as $keyBreak => $break)
            {
                $images   = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryPhoto')->where('BreakDownID', $break->BreakDownID)->get();
                $lastFrom = $break->InventoryPalletID;

                $flag         = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.Checklist')->where('Category', 'flag')->get();
                $flagSelected = array();
                // dd($flag);
                $strExplode = array_map('trim', explode(',', $break->Flags));
                if ($break->Flags)
                {
                    foreach ($flag as $key => $fl)
                    {
                        if (in_array($fl->Value, $strExplode))
                        {
                            array_push($flagSelected, true);
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
                    "FlagsSelected" => is_null($flagSelected) ? "" : $flagSelected,
                    "Tally" => is_null($break->Tally) ? "" : $break->Tally,
                    "Weight" => is_null($break->Weight) ? "" : $break->Weight,
                    "gallery" => $images
                );
                array_push($breakdown, $loopBreakdown);
            }
        }
        $typeChecklist  = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.Checklist')->where('Category', 'type')->get();
        $flagsChecklist = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.Checklist')->where('Category', 'flag')->get();
        $locations      = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.Checklist')->where('Category', 'location')->get();
        $data           = array(
            'status' => "success",
            'pallet' => $pallet,
            'breakdown' => $breakdown,
            'type' => $typeChecklist,
            'flags' => $flagsChecklist,
            'locations' => $locations
        );
        return response($data);
    }
    function copyPallet(Request $request)
    {
        $copy         = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $request->get('InventoryPalletID'))->first();
        $pallet       = array(
            "InventoryID" => $copy->InventoryID,
            "SequenceNo" => $copy->SequenceNo,
            "ExpCntrID" => $copy->ExpCntrID,
            "Reserved" => $copy->Reserved,
            "ReservedBy" => $copy->ReservedBy,
            "ReservedDt" => $copy->ReservedDt,
            "ClearedDate" => $copy->ClearedDate,
            "DeliveryID" => $copy->DeliveryID,
            "CreatedBy" => $copy->CreatedBy,
            "CreatedDt" => date("Y-m-d h:i:s"),
            "UpdatedBy" => $copy->UpdatedBy,
            "UpdatedDt" => $copy->UpdatedDt,
            "DelStatus" => $copy->DelStatus,
            "InterWhseFlag" => $copy->InterWhseFlag,
            "CurrentLocation" => $copy->CurrentLocation,
            "InterWhseTo" => $copy->InterWhseTo,
            "Tag" => $copy->Tag,
            "Location" => $copy->Location,
            "DN" => $copy->DN
        );
        $store        = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryPallet')->insertGetId($pallet);
        $breakdownRaw = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $request->get('InventoryPalletID'))->get();
        // dd($breakdownRaw);
        foreach ($breakdownRaw as $key => $valueBreakdown)
        {
            $breakdown = array(
                "InventoryPalletID" => $store,
                "Markings" => $valueBreakdown->Markings,
                "Quantity" => $valueBreakdown->Quantity,
                "Type" => $valueBreakdown->Type,
                "Length" => $valueBreakdown->Length,
                "Breadth" => $valueBreakdown->Breadth,
                "Height" => $valueBreakdown->Height,
                "Volume" => $valueBreakdown->Volume,
                "Remarks" => '',
                "CreatedBy" => $valueBreakdown->CreatedBy,
                "CreatedDt" => $valueBreakdown->CreatedDt,
                "UpdatedBy" => $valueBreakdown->UpdatedBy,
                "UpdatedDt" => $valueBreakdown->UpdatedDt,
                "DelStatus" => $valueBreakdown->DelStatus,
                "Flags" => '',
                "Tally" => $valueBreakdown->Tally,
                "Weight" => $valueBreakdown->Weight
            );
            DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->insert($breakdown);
        }
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function deletePallet(Request $request)
    {

        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $request->get('InventoryPalletID'))->update(array(
            'DelStatus' => 'Y'
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function addBreakdown(Request $request)
    {
        $breakdownRaw = DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $request->get('InventoryPalletID'))->first();
        $breakdown    = array(
            "InventoryPalletID" => (int) $breakdownRaw->InventoryPalletID,
            "Markings" => $breakdownRaw->Markings,
            "Quantity" => (int) $breakdownRaw->Quantity,
            "Type" => $breakdownRaw->Type,
            "Length" => (int) $breakdownRaw->Length,
            "Breadth" => (int) $breakdownRaw->Breadth,
            "Height" => (int) $breakdownRaw->Height,
            "Volume" => $breakdownRaw->Volume,
            "Remarks" => '',
            "CreatedBy" => $breakdownRaw->CreatedBy,
            "CreatedDt" => $breakdownRaw->CreatedDt,
            "UpdatedBy" => $breakdownRaw->UpdatedBy,
            "UpdatedDt" => $breakdownRaw->UpdatedDt,
            "DelStatus" => $breakdownRaw->DelStatus,
            "Flags" => '',
            "Tally" => $breakdownRaw->Tally,
            "Weight" => $breakdownRaw->Weight
        );
        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->insert($breakdown);
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function deleteBreakdown(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->get('BreakDownID'))->update(array(
            'DelStatus' => 'Y'
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    public function updateBreakdown(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->update(array(
            $request->post('type') => $request->post('data')
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    public function updatePallet(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $request->post('InventoryPalletID'))->update(array(
            $request->post('type') => $request->post('data')
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function updateBreakdownLBH(Request $request)
    {
        $qty     = (int) $request->post('Qty') ? $request->post('Qty') : 0;
        $length  = (int) $request->post('L') ? $request->post('L') : 0;
        $breadth = (int) $request->post('B') ? $request->post('B') : 0;
        $height  = (int) $request->post('H') ? $request->post('H') : 0;

        $lbh = array(
            'Quantity' => $qty,
            'Length' => $length,
            'Breadth' => $breadth,
            'Height' => $height,
            'Volume' => sprintf("%.3f", ($qty * $length * $breadth * $height) / 1000000)
        );
        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->update($lbh);
        $data = array(
            'status' => "success",
            'volume' => sprintf("%.3f", ($qty * $length * $breadth * $height) / 1000000)
        );
        return response($data);
    }
    function uploadBreakdownGallery(Request $request)
    {
        $cover     = $request->file('image');
        $image     = $cover->getClientOriginalName();
        $filename  = pathinfo($image, PATHINFO_FILENAME);
        $extension = pathinfo($image, PATHINFO_EXTENSION);
        $finalName = $filename . '_' . time() . '.' . $extension;
        Storage::disk('public')->put('breakdown/' . $finalName, File::get($cover));

        $dataImg = array(
            'BreakDownID' => $request->post('BreakDownID'),
            'PhotoName' => $finalName,
            'PhotoExt' => $extension,
            'CreatedDt' => date("Y-m-d h:i:s"),
            'CreatedBy' => '',
            'ModifyDt' => date("Y-m-d h:i:s"),
            'ModifyBy' => '',
            'DelStatus' => 'N',
            'Ordering' => 0,
            'Emailed' => null,
            'PhotoNameSystem' => $finalName
        );
        DB::connection("sqlsrv3")->table('HSC2017Test_V2.dbo.HSC_InventoryPhoto')->insert($dataImg);
        $data = array(
            'status' => 'success'
        );
        return response($data);
    }
}
