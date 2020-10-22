<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Log;

date_default_timezone_set("Asia/Singapore");
class ReceivingController extends Controller
{
    function getSummary(Request $request)
    {
        $str = $request->get("warehouse");
        $exp = explode(",", $str);
        $parseTag = "";
        foreach ($exp as $value) {
            $parseTag = $parseTag . ",'" . $value . "'";
        }
        $tagging = substr($parseTag, 1);
        $list = DB::connection("sqlsrv3")->select("select distinct j.ClientID
        from HSC2012.dbo.JobInfo j inner join
             HSC2012.dbo.ContainerInfo ci on  j.JobNumber = ci.JobNumber inner join
             HSC2017.dbo.HSC_Inventory i on ci.Dummy = i.CntrID inner join
             HSC2017.dbo.HSC_InventoryPallet ip on i.InventoryID = ip.InventoryID
        where DATEDIFF(hour, i.StorageDate, GETDATE()) <= 24
        and ip.CurrentLocation in (" . $tagging . ")
          and i.DelStatus = 'N'
          and ip.DelStatus = 'N'");
        $data = array(
            'data' => $list
        );
        return response($data);
    }
    function getReceivingList(Request $request)
    {
        $str = $request->get("warehouse");
        $exp = explode(",", $str);
        $parseTag = "";
        foreach ($exp as $value) {
            $parseTag = $parseTag . ",'" . $value . "'";
        }
        $tagging = substr($parseTag, 1);
        $list     = DB::connection("sqlsrv3")->select("select j.ClientID, i.TranshipmentRef, i.POD, i.SequenceNo, i.StorageDate, i.MQuantity, i.InventoryID, i.CntrID, SUM(ib.Quantity) ItemQty, i.HBL, i.CheckStatus, i.MQuantity, i.MVolume, i.MWeight,
        COUNT(distinct case when ip.Tag <> '' then ip.Tag else null end) CntTag, COUNT(distinct ip.InventoryPalletID) CntPlt, MAX(A.DN) DN
 from HSC2012.dbo.JobInfo j inner join
      HSC2012.dbo.ContainerInfo ci on  j.JobNumber = ci.JobNumber inner join
      HSC2017.dbo.HSC_Inventory i on ci.Dummy = i.CntrID inner join
      HSC2017.dbo.HSC_InventoryPallet ip on i.InventoryID = ip.InventoryID inner join
      HSC2017.dbo.HSC_InventoryBreakdown ib on ip.InventoryPalletID = ib.InventoryPalletID left join
      (select ip1.InventoryPalletID, MAX(di.DN) DN
       from HSC2017.dbo.HSC_Inventory i1 inner join
            HSC2017.dbo.HSC_InventoryPallet ip1 on i1.InventoryID = ip1.InventoryID inner join
            HSC2017.dbo.HSC_DNInventory dni on ip1.InventoryPalletID = dni.InventoryPalletID inner join
            HSC2017.dbo.HSC_DeliveryInfo di on dni.DeliveryID = di.DeliveryID
       where DATEDIFF(hour, i1.StorageDate, GETDATE()) <= 24
         and di.ClientID = '" . $request->get('ClientID') . "'
         and ip1.CurrentLocation in (" . $tagging . ")
         and di.CancelStatus = 'N'
         and i1.DelStatus = 'N'
         and ip1.DelStatus = 'N'
       group by ip1.InventoryPalletID) A on A.InventoryPalletID = ip.InventoryPalletID
 where DATEDIFF(hour, i.StorageDate, GETDATE()) <= 24
   and j.ClientID = '" . $request->get('ClientID') . "'
   and ip.CurrentLocation in (" . $tagging . ")
   and i.DelStatus = 'N'
   and ip.DelStatus = 'N'
   and ib.DelStatus = 'N'
 group by j.ClientID, i.TranshipmentRef, i.POD, i.MQuantity, i.StorageDate, i.InventoryID, i.CntrID, i.HBL, i.CheckStatus, i.MVolume, i.MWeight, i.SequenceNo ORDER BY i.StorageDate DESC");
        $dataList = array();
        foreach ($list as $key => $value)
        {
            $dataClient = array(
                'ClientID' => $value->ClientID,
                'TranshipmentRef' => $value->TranshipmentRef,
                'POD' => $value->POD,
                'SequenceNo' => $value->SequenceNo,
                'StorageDate' => date("d/m/Y H:i", strtotime($value->StorageDate)),
                'MQuantity' => $value->MQuantity,
                'InventoryID' => $value->InventoryID,
                'CntrID' => $value->CntrID,
                'ItemQty' => $value->ItemQty,
                'HBL' => $value->HBL ? $value->HBL : "-",
                'CheckStatus' => $value->CheckStatus,
                'MVolume' => $value->MVolume,
                'MWeight' => $value->MWeight,
                'CntTag' => $value->CntTag,
                'CntPlt' => $value->CntPlt,
                'DN' => $value->DN
            );
            array_push($dataList, $dataClient);
        }
        $data = array(
            'data' => $dataList
        );
        return response($data);
    }

    function checkLockedInventory(Request $request)
    {
        $inventory = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->select('LockedBy')->where('InventoryID', $request->get('InventoryID'))->first();
        // dd($inventory);
        if (empty($inventory) || is_null($inventory->LockedBy))
        {
            $data = array(
                'status' => false,
                'LockedBy' => is_null($inventory->LockedBy) ? "" : $inventory->LockedBy
            );
            return response($data);
        }
        else
        {

            $data = array(
                'status' => true,
                'LockedBy' => is_null($inventory->LockedBy) ? "" : $inventory->LockedBy
            );
            return response($data);
        }
    }
    function updateLockedInventory(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
            'LockedBy' => $request->get('type') == "locked" ? $request->get('LockedBy') : "",
            'LockedDt' => $request->get('type') == "locked" ? date("Y-m-d H:i:s") : null,
            'LockedPC' => $request->get('type') == "locked" ? $request->get('LockedPC') : ""
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function getPalletBreakdown()
    {
        $pallet     = array();
        $breakdown  = array();
        $rawPallet  = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryID', $_GET['inventoryid'])->where('DelStatus', 'N')->orderBy('InventoryPalletID', 'ASC')->get();
        $i          = 1;
        $DeliveryID = array();
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
                "Location" => is_null($value->Location) ? "" : $value->Location
            );
            // array_push($DeliveryID, is_null($value->DeliveryID) || $value->DeliveryID == 0 ? 0 : 1);
            array_push($DeliveryID, $value->DeliveryID >= 1 ? 1 : 0);
            array_push($pallet, $loopPallet);
            $rawBreakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $value->InventoryPalletID)->where('DelStatus', 'N')->orderBy('BreakDownID', 'ASC')->get();
            $x            = 1;
            $lastFrom     = null;
            foreach ($rawBreakdown as $keyBreak => $break)
            {
                $galleries = array();
                $images    = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->where('BreakDownID', $break->BreakDownID)->where('DelStatus', 'N')->get();
                // dd($images);
                foreach ($images as $gallery)
                {
                    if ($gallery->PhotoNameSystem) {
                        $loadImage = str_replace("//server-db/Files/Photo", "http://192.168.14.70:9030/", $gallery->PhotoNameSystem);
                        $loadPath  = str_replace("//server-db/Files/Photo", "", $gallery->PhotoNameSystem);
                        $file      = '\\\\SERVER-DB\\Files\\Photo\\' . $loadPath;
                        // dd($file);
                        // if (file_exists($file)) {}
                        if (file_exists($file) && $file != "\\\\SERVER-DB\\Files\\Photo\\")
                        {
                            if (@getimagesize($loadImage)) {
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
                    }
                    else if($gallery->Photo) {
                        $image = imagecreatefromstring($gallery->Photo); 
                            $fileName = $gallery->InventoryPhotoID ."-" .$gallery->BreakDownID .".". $gallery->PhotoExt;
                            $loadImage = "http://192.168.14.70:9133/temp/" . $fileName;
                            ob_start();
                            imagejpeg($image, null, 480);
                            $data = ob_get_contents();
                            ob_end_clean();
                            $fnl = "data:image/jpg;base64," .  base64_encode($data);
                            list($type, $fnl) = explode(';', $fnl);
                            list(, $fnl)      = explode(',', $fnl);
                            $fnl = base64_decode($fnl);
                            Storage::disk('public')->put('temp/' . $fileName, $fnl);
                            $imageGallery = array(
                                'is_base_64' => true,
                                'InventoryPhotoID' => $gallery->InventoryPhotoID,
                                'PhotoName' => $gallery->PhotoName,
                                'PhotoExt' => $gallery->PhotoExt,
                                'PhotoNameSystem' => $loadImage
                            );
                            array_push($galleries, $imageGallery);
                    }
                }
                $lastFrom = $break->InventoryPalletID;

                $flag         = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'flagImp')->get();
                $flagSelected = array();
                $flagShow     = array();
                // dd($flag);
                $strExplode   = array_map('trim', explode(',', $break->Flags));
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
                }
                else
                {
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
                    "Flags_show" => is_null($flagShow) ? "" : implode(", ", $flagShow),
                    "FlagsSelected" => is_null($flagSelected) ? "" : $flagSelected,
                    "Tally" => is_null($break->Tally) ? "" : $break->Tally,
                    "Weight" => is_null($break->Weight) ? "" : $break->Weight,
                    "gallery" => $galleries
                );
                array_push($breakdown, $loopBreakdown);
            }
        }
        $typeChecklist  = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'type')->get();
        $flagsChecklist = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'flagImp')->get();
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
        return response($data);
    }
    function copyPallet(Request $request)
    {
        $copy          = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $request->get('InventoryPalletID'))->first();
        $InventoryPalletID = 0;
        $type = "copy";
        $CreatedBy = '"' . $request->get('CreatedBy') .' "';
        DB::connection("sqlsrv3")->update("SET NOCOUNT ON;SET ANSI_NULLS ON; SET ANSI_WARNINGS ON;SET ARITHABORT ON;exec HSC2017.dbo.InventoryPallet_Insert " . $InventoryPalletID . ", "  . $copy->InventoryID . ", " .  $request->get('InventoryPalletID') . ", " . $type . ", " . $CreatedBy . "");
        

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function deletePallet(Request $request)
    {

      $palletInfo = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $request->get('InventoryPalletID'))->first();
      $inventory = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $palletInfo->InventoryID)->first();
      $hbl = "";
    //   dd($inventory->HBL);
      $UpdatedBy = '"' . $request->get('UpdatedBy') .' "';
      DB::connection("sqlsrv3")->statement("SET ARITHABORT ON;exec HSC2017.dbo.InventoryPallet_Delete " . $palletInfo->InventoryID . ", " .  $request->get('InventoryPalletID') . ", " . '" "'. ", " . $UpdatedBy . "");
        
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function addBreakdown(Request $request)
    {
        $breakdownRaw = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $request->get('InventoryPalletID'))->first();

        $returnBreakdownID = 0;
        $CreatedBy = '"' . $request->get('CreatedBy') .' "';
        DB::connection("sqlsrv3")->update("SET NOCOUNT ON;SET ANSI_NULLS ON; SET ANSI_WARNINGS ON;SET ARITHABORT ON;exec HSC2017.dbo.InventoryBreakdown_CopyBreakdown " . $returnBreakdownID . ", "  . $breakdownRaw->BreakDownID . ", " . $CreatedBy . "");
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function deleteBreakdown(Request $request)
    {
        // DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->get('BreakDownID'))->update(array(
        //     'DelStatus' => 'Y',
        //     'UpdatedDt' => date("Y-m-d H:i:s"),
        //     'UpdatedBy' => $request->get('UpdatedBy')
        // ));
        Log::debug('DEBUG QUERY -  DELETE BREAKDOWN');
        Log::debug('DEBUG QUERY -  USER ' . $request->get('UpdatedBy') . ' DELETEBREAKDOWNID ' . $request->get('BreakDownID') );
        $UpdatedBy = '"' . $request->get('UpdatedBy') .' "';
        DB::connection("sqlsrv3")->statement("SET ARITHABORT ON;exec HSC2017.dbo.InventoryBreakdown_Delete " . $request->get('BreakDownID') .", " .  $UpdatedBy ."");
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    public function updateBreakdown(Request $request)
    {
        Log::debug('DEBUG QUERY -  UPDATE BREAKDOWN');
        Log::debug('DEBUG QUERY -  UPDATE BREAKDOWN TRY - '. $request->post('type') . ' with data ' . $request->post('data') . ' in BreakDownID = ' . $request->post('BreakDownID'));
        DB::connection("sqlsrv3")->table('HSC_IPS.dbo.InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->update(array(
            $request->post('type') => ltrim($request->post('data')),
            'UpdatedDt' => date("Y-m-d H:i:s"),
            'UpdatedBy' => $request->get('UpdatedBy')
        ));
        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->update(array(
            $request->post('type') => ltrim($request->post('data')),
            'UpdatedDt' => date("Y-m-d H:i:s"),
            'UpdatedBy' => $request->get('UpdatedBy')
        ));
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    public function checkTag(Request $request)
    {
        $count = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('Tag', $request->get('Tag'))->count();

        $data = array(
            'status' => $count >= 1 ? false : true
        );
        return response($data);
    }
    public function updatePallet(Request $request)
    {

        if ($request->post('type') == "Tag") {
            $check = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $request->post('InventoryPalletID'))->first();

            if ((isset($check->Tag) || trim($check->Tag) != '')) {
                $curl = curl_init();
    
                curl_setopt_array($curl, array(
                  CURLOPT_URL => "http://192.168.14.5:8080/qpe/getTagPosition?version=2&tag=" . $request->post('data') . "&maxAge=900000&humanReadable=true",
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "GET",
                ));
                
                $response = curl_exec($curl);
                
                curl_close($curl);
                $parse = json_decode($response);
                $checkTAG = DB::connection("sqlsrv3")->table('HSC_IPS.dbo.ForkLiftJobsFilter')->where('TagID', $request->post('data'))->first();
                if (isset($parse->tags[0])) {
                    if (isset($parse->tags[0]->smoothedPosition)) {
                        if ($checkTAG) 
                        {
                            DB::connection("sqlsrv3")->table('HSC_IPS.dbo.ForkLiftJobsFilter')->where('TagID', $request->post('data'))->update(array(
                                'x' => $parse->tags[0]->smoothedPosition[0],
                                'y' => $parse->tags[0]->smoothedPosition[1],
                                'mode' => 0
                            ));
                        }
                        else
                        {
                            DB::connection("sqlsrv3")->table('HSC_IPS.dbo.ForkLiftJobsFilter')->insert(array(
                                'TagID' => $request->post('data'),
                                'x' => $parse->tags[0]->smoothedPosition[0],
                                'y' => $parse->tags[0]->smoothedPosition[1],
                                'mode' => 0
                            ));
                        }
                    }
                }
            }
            else{
                $curl = curl_init();
    
                curl_setopt_array($curl, array(
                  CURLOPT_URL => "http://192.168.14.5:8080/qpe/getTagPosition?version=2&tag=" . $request->post('data') . "&maxAge=900000&humanReadable=true",
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "GET",
                ));
                
                $response = curl_exec($curl);
                
                curl_close($curl);
                $parse = json_decode($response);
                $checkTAG = DB::connection("sqlsrv3")->table('HSC_IPS.dbo.ForkLiftJobsFilter')->where('TagID', $request->post('data'))->first();
                if (isset($parse->tags[0])) {
                    if (isset($parse->tags[0]->smoothedPosition)) {
                        if ($checkTAG) 
                        {
                            DB::connection("sqlsrv3")->table('HSC_IPS.dbo.ForkLiftJobsFilter')->where('TagID', $request->post('data'))->update(array(
                                'x' => $parse->tags[0]->smoothedPosition[0],
                                'y' => $parse->tags[0]->smoothedPosition[1],
                                'mode' => 0
                            ));
                        }
                        else
                        {
                            DB::connection("sqlsrv3")->table('HSC_IPS.dbo.ForkLiftJobsFilter')->insert(array(
                                'TagID' => $request->post('data'),
                                'x' => $parse->tags[0]->smoothedPosition[0],
                                'y' => $parse->tags[0]->smoothedPosition[1],
                                'mode' => 0
                            ));
                        }
                    }
                }
            }
            DB::connection("sqlsrv3")->table('HSC_IPS.dbo.InventoryPallet')->where('InventoryPalletID', $request->post('InventoryPalletID'))->update(array(
                $request->post('type') => $request->post('data'),
                'UpdatedDt' => date("Y-m-d H:i:s"),
                'UpdatedBy' => $request->post('UpdatedBy')
            ));

            DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $request->post('InventoryPalletID'))->update(array(
                $request->post('type') => $request->post('data'),
                'UpdatedDt' => date("Y-m-d H:i:s"),
                'UpdatedBy' => $request->post('UpdatedBy')
            ));
        }else{
            DB::connection("sqlsrv3")->table('HSC_IPS.dbo.InventoryPallet')->where('InventoryPalletID', $request->post('InventoryPalletID'))->update(array(
                $request->post('type') => $request->post('data'),
                'UpdatedDt' => date("Y-m-d H:i:s"),
                'UpdatedBy' => $request->post('UpdatedBy')
            ));

            DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $request->post('InventoryPalletID'))->update(array(
                $request->post('type') => $request->post('data'),
                'UpdatedDt' => date("Y-m-d H:i:s"),
                'UpdatedBy' => $request->post('UpdatedBy')
            ));
        }
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function updateBreakdownLBH(Request $request)
    {
        Log::debug('DEBUG QUERY -  UPDATE BREAKDOWN PROC');
        Log::debug('DEBUG QUERY -  UPDATE BREAKDOWN PROC TRY - REMARKS with data ' . $request->post('r'));
        $markings = is_null($request->post('Markings')) ? '" "' : '"' . trim($request->post('Markings')) .'"';
        $type = '"' . trim($request->post('T')) .'"';
        $qty      = (int) $request->post('Qty') ? $request->post('Qty') : 0;
        $length   = (int) $request->post('L') ? $request->post('L') : 0;
        $breadth  = (int) $request->post('B') ? $request->post('B') : 0;
        $height   = (int) $request->post('H') ? $request->post('H') : 0;
        $volume   = sprintf("%.3f", ($qty * $length * $breadth * $height) / 1000000);
        $remarks  = is_null($request->post('R')) ? '" "' : '"' . $request->post('R') .'"';
        $flags    = is_null($request->post('F')) ? '" "' : '"' . $request->post('F') .'"';
        $UpdatedBy = '"' . $request->get('UpdatedBy') .'"';
        $parse = DB::connection("sqlsrv3")->statement("SET NOCOUNT ON;SET ARITHABORT ON;SET QUOTED_IDENTIFIER OFF;SET ANSI_NULLS ON;exec HSC2017.dbo.InventoryBreakdown_InsertUpdate " . $request->post('BreakDownID') . ", " . '"-"'. ", " . $markings . ", " . $qty . ", " . $type . ", " . $length . ", " . $breadth . ", " . $height . ", " . $volume . ", " . $remarks . ", " . $UpdatedBy . ", " . $flags . "");

        $data = array(
            'status' => "success",
            'volume' => sprintf("%.3f", ($qty * $length * $breadth * $height) / 1000000)
        );
        return response($data);
    }
    function uploadBreakdownGallery(Request $request)
    {
        $dir   = '\\\\SERVER-DB\\Files\\Photo\\';
        $year  = date("Y");
        $month = date("m");

        if (is_dir($dir))
        {
            Log::debug('yea is a dir');
            if (!file_exists($dir . $request->get('CntrID')))
            {
                Log::debug('yea is a exist');
                mkdir($dir . $request->get('CntrID'), 0775);
            }
            if ($dh = opendir($dir))
            {
                Log::debug('yea is a opened');
                $uold      = umask(0);
                $filename  = $dir . $request->get('CntrID') . "/" . $year;
                $filename2 = $dir . $request->get('CntrID') . "/" . $year . "/" . $month;
                if (file_exists($filename))
                {
                    Log::debug('year already');
                    if (!file_exists($filename2))
                    {
                        Log::debug('creating month folder');
                        mkdir($filename2, 0775);
                    }
                }
                else
                {
                    mkdir($filename, 0775);
                    mkdir($filename2, 0775);
                }
                umask($uold);
            }
        }else{

            Log::debug('yea is a dir');
        }
        $maxId       = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->max('InventoryPhotoID');
        $maxOrdering = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->where('BreakDownID', $request->post('BreakDownID'))->max('Ordering');
        // getContainerID
        // $palletID    = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->first();
        // $inventoryID = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $palletID->InventoryPalletID)->first();
        // $cntr        = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $inventoryID->InventoryID)->first();
        $cover       = $request->file('image');
        $image       = $cover->getClientOriginalName();
        $filename    = pathinfo($image, PATHINFO_FILENAME);
        $extension   = pathinfo($image, PATHINFO_EXTENSION);
        // $finalName = $filename . '_' . time() . '.' . $extension;
        $finalName   = 'Sendinphoto_' . ($maxId + 1) . '.' . $extension;
        $finalNameDB = 'Sendinphoto_' . ($maxId + 1);

        // temp folder
        Storage::disk('public')->put('temp/' . $finalName, File::get($cover));

        $imageFix = public_path() . '/temp/' . $finalName;

        $dir   = '\\\\SERVER-DB\\Files\\Photo\\';

        list($width, $height) = getimagesize($imageFix);
        if ($width > $height)
        {
            $source = imagecreatefromjpeg($imageFix);

            $rotate       = imagerotate($source, 90, 0);
            $image_resize = Image::make($rotate);
            $image_resize->resize(640, 480);
            $image_resize->text(date("d/m/Y H:i"), 500, 400, function($font) {
                $font->file(public_path() . '/fonts/RobotoCondensed-Bold.ttf');
                $font->size(20);
                $font->color('#FFFF00');
                $font->align('center');
                $font->valign('bottom');
            });
            $image_resize->save(public_path('image/breakdown/' . $finalName));
        }
        else
        {
            $image_resize = Image::make($imageFix);
            $image_resize->resize(480, 640);
            $image_resize->text(date("d/m/Y H:i"), 350, 620, function($font) {
                $font->file(public_path() . '/fonts/RobotoCondensed-Bold.ttf');
                $font->size(20);
                $font->color('#FFFF00');
                $font->align('center');
                $font->valign('bottom');
            });
            $image_resize->save(public_path('image/breakdown/' . $finalName));
        }
        Log::debug('UPLOADING RECEIVING - ContainerID = ' . $request->get('CntrID'));
        copy(public_path('image/breakdown/' . $finalName), $dir . $request->get('CntrID') . "/" . $year . "/" . $month . '/' . $finalName);
        // copy(public_path('image/container/HSC-1581302045134_1581302040.jpeg'), "\\\\SERVER-DB\\Files\\Photo\\HSC-test.jpeg");
        $dataImg = array(
            'BreakDownID' => $request->post('BreakDownID'),
            'PhotoName' => $finalNameDB,
            'PhotoExt' => "." . $extension,
            'CreatedDt' => date("Y-m-d H:i:s"),
            'CreatedBy' => $request->get('CreatedBy'),
            'ModifyDt' => null,
            'ModifyBy' => '',
            'DelStatus' => 'N',
            'Ordering' => $maxOrdering + 1,
            'Emailed' => 1,
            'PhotoNameSystem' => "//server-db/Files/Photo/" . $request->get('CntrID') . "/" . $year . "/" . $month . '/' . $finalName
        );
        $id      = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->insertGetId($dataImg);
        unlink(public_path('image/breakdown/' . $finalName));
        $data = array(
            'status' => 'success',
            'last_photo' => array(
                'InventoryPhotoID' => $id,
                'PhotoNameSystem' => $year . "/" . $month . '/' . $finalName
            )
        );
        return response($data);
    }
    function deleteBreakdownPhoto(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->where('InventoryPhotoID', $request->post('InventoryPhotoID'))->update(array(
            'DelStatus' => 'Y',
            'ModifyDt' => date("Y-m-d H:i:s"),
            'ModifyBy' => $request->get('UpdatedBy')
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function uploadPhotoHBL(Request $request)
    {
        $maxId       = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_CntrPhoto')->max('CntrPhotoID');
        $maxOrdering = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_CntrPhoto')->where('CntrID', $request->post('CntrID'))->max('Ordering');
        $cover       = $request->file('image');
        $image       = $cover->getClientOriginalName();
        $filename    = pathinfo($image, PATHINFO_FILENAME);
        $extension   = pathinfo($image, PATHINFO_EXTENSION);
        $finalName   = 'cntrPhoto_' . ($maxId + 1) . '.' . $extension;
        $finalNameDB = 'cntrPhoto_' . ($maxId + 1);
        // temp folder
        Storage::disk('public')->put('temp/' . $finalName, File::get($cover));

        $imageFix = public_path() . '/temp/' . $finalName;

         $dir   = '\\\\SERVER-DB\\Files\\Photo\\';

        list($width, $height) = getimagesize($imageFix);
        if ($width > $height)
        {
            $source = imagecreatefromjpeg($imageFix);

            $rotate       = imagerotate($source, 90, 0);
            $image_resize = Image::make($rotate);
            $image_resize->resize(640, 480);
            $image_resize->save(public_path('image/container/' . $finalName));
        }
        else
        {
            $image_resize = Image::make($imageFix);
            $image_resize->resize(480, 640);
            $image_resize->save(public_path('image/container/' . $finalName));
        }
        
        copy(public_path('image/container/' . $finalName), $dir . $request->post('CntrID') . '/' . $finalName);

        $dataImg = array(
            'CntrID' => $request->post('CntrID'),
            'PhotoName' => $finalNameDB,
            'PhotoExt' => "." . $extension,
            'CreatedDt' => date("Y-m-d H:i:s"),
            'CreatedBy' => $request->get('CreatedBy'),
            'ModifyDt' => null,
            'ModifyBy' => '',
            'DelStatus' => 'N',
            'Ordering' => $maxOrdering + 1,
            'Emailed' => 1,
            'PhotoNameSystem' => "//server-db/Files/Photo/" . $request->get('CntrID') . "/" . $year . "/" . $month . '/' . $finalName
        );
        $id      = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_CntrPhoto')->insertGetId($dataImg);
        unlink(public_path('image/container/' . $finalName));
        $data = array(
            'status' => 'success',
            'last_photo' => array(
                'InventoryPhotoID' => $id,
                'PhotoNameSystem' => $finalName
            )
        );
        return response($data);
    }
    public function getPhotoHBL(Request $request)
    {
        $galleries = array();
        $images    = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_CntrPhoto')->where('CntrID', $request->get('CntrID'))->where('DelStatus', 'N')->get();
        foreach ($images as $gallery)
        {

            $loadImage = str_replace("//server-db/Files/Photo", "http://192.168.14.70:9030/", $gallery->PhotoNameSystem);
            list($width, $height) = getimagesize($loadImage);
            $imageGallery = array(
                'width' => $width,
                'CntrPhotoID' => $gallery->CntrPhotoID,
                'PhotoName' => $gallery->PhotoName,
                'PhotoExt' => $gallery->PhotoExt,
                'PhotoNameSystem' => $loadImage
            );
            array_push($galleries, $imageGallery);
        }
        $data = array(
            'status' => 'success',
            'images' => $galleries
        );
        return response($data);
    }
    function deleteHBLPhoto(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_CntrPhoto')->where('CntrPhotoID', $request->get('CntrPhotoID'))->update(array(
            'DelStatus' => 'Y',
            'ModifyDt' => date("Y-m-d H:i:s"),
            'ModifyBy' => $request->get('UpdatedBy')
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    public function checkInventory(Request $request)
    {
        $inventory     = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->first();
        $pallet        = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->select('InventoryPalletID', 'Tag', 'CurrentLocation', 'DeliveryID')->where('InventoryID', $request->get('InventoryID'))->get();
        $ContainerInfo = DB::connection("sqlsrv3")->table('HSC2012.dbo.ContainerInfo')->where('Dummy', $inventory->CntrID)->first();
        $jobInfo       = DB::connection("sqlsrv3")->table('HSC2012.dbo.JobInfo')->where('JobNumber', $ContainerInfo->JobNumber)->first();
        if ($inventory->HBL == 'OVERLANDED')
        {
            Log::debug('DEBUG QUERY -  CHECK INVENTORY OVERLANDED CHECKSTATUS IS -');
            $data = array(
                'status' => "success"
            );
            return response($data);
        }
        else
        {
            $qty        = 0;
            $totalTag   = 0;
            $totalPhoto = 0;
            $check      = array();
            $checkPalletDelivery =  array();
            $checkPalletLocation =  array();
            foreach ($pallet as $key => $plt)
            {
                if ($plt->Tag)
                {
                    $totalTag += 1;
                }
                if($plt->DeliveryID > 0)
                {
                  array_push($checkPalletDelivery, 'have_delivery');
                }
                if ($plt->CurrentLocation == "HSC" || $plt->CurrentLocation == "122") 
                {
                    array_push($checkPalletLocation, 'have_location');
                }
                $breakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->select('BreakDownID', 'Quantity', 'Flags')->where('InventoryPalletID', $plt->InventoryPalletID)->where('DelStatus', 'N')->get();
                foreach ($breakdown as $key => $brk)
                {
                    $qty += $brk->Quantity;

                    $photo = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->select('InventoryPhotoID', 'DelStatus')->where('BreakDownID', $brk->BreakDownID)->where('DelStatus', 'N')->count();
                    $totalPhoto += $photo;
                    if (in_array('SHORTLANDED', str_replace(' ', '', explode(',', $brk->Flags))))
                    {
                        array_push($check, 'yes');
                    }
                    else if(in_array('CONNECTING', str_replace(' ', '', explode(',', $brk->Flags)))) {
                        array_push($check, 'connecting');
                    }
                }
            }

            $MQty = (int) $inventory->MQuantity;
            if ($MQty == $qty)
            {
                if(in_array('have_location', $checkPalletLocation))
                {
                    if ($totalPhoto >= 1)
                    {
                        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                            'CheckStatus' => 'Y'
                        ));
                    }
                    else if ($totalPhoto >= 1 && $totalTag >= 1)
                    {
                        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                            'CheckStatus' => 'Y'
                        ));
                    }
                    else{
                        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                            'CheckStatus' => 'N'
                        ));
                    }
                }
                else if(in_array('have_delivery', $checkPalletDelivery)) 
                {
                    DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                        'CheckStatus' => 'Y'
                    ));
                }
                else if ($totalPhoto >= 1 && $totalTag >= 1)
                {
                    DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                        'CheckStatus' => 'Y'
                    ));
                }
                else if ($jobInfo->ClientID == 'VANGUARD' && $totalPhoto >= 1)
                {
                    DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                        'CheckStatus' => 'Y'
                    ));
                }
                else if(in_array('connecting', $check) && $totalPhoto >= 1)
                {
                    DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                        'CheckStatus' => 'Y'
                    ));
                }
                else
                {
                    DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                        'CheckStatus' => 'N'
                    ));
                }
            }
            else{

                DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                    'CheckStatus' => 'N'
                ));
            }
            $data = array(
                'status' => "success",
                'location' => $checkPalletLocation
            );
            return response($data);
        }
    }
}