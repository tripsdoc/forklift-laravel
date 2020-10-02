<?php
namespace App\Http\Controllers;

date_default_timezone_set('Asia/Singapore');
use DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManagerStatic as Image;
use Icewind\SMB\ServerFactory;
use Icewind\SMB\BasicAuth;
use Illuminate\Support\Facades\Log;
// use Icewind\SMB\AnonymousAuth;
// use App\Http\Controllers\Api\smbClientTestsssss;
// require_once('smbclient.php');
class UnstuffingController extends Controller
{
    protected $mode;
    protected $db2017;
    protected $db2012;
    protected $dbhsc;
    public function __construct()
    {
        $this->mode = "dev";
        switch ($this->mode) {
            case 'dev':
                $this->db2017 = "HSC2017";
                $this->db2012 = "HSC2012";
                $this->dbhsc = "HSC_IPS";
                break;
            case 'prod':
                $this->db2017 = "HSC2017";
                $this->db2012 = "HSC2012";
                $this->dbhsc = "HSC_IPS";
                break;
            default:
                $this->db2017 = "HSC2017";
                $this->db2012 = "HSC2012";
                $this->dbhsc = "HSC_IPS";
                break;
        }
    }
    function getCurrentContainer(Request $request)
    {
        $counter = DB::connection("sqlsrv3")->table( $this->db2012 . '.dbo.ContainerInfo')->where('NTunstuffingstatus', "PROCESSING " . $request->get('username'))->first();
        $count   = DB::connection("sqlsrv3")->table( $this->db2012 . '.dbo.ContainerInfo')->where('NTunstuffingstatus', "PROCESSING " . $request->get('username'))->count();
        $data    = array(
            'dummy' => $counter ? $counter->Dummy : "",
            'counter' => $count
        );
        return response($data);
    }
    function getDetailImportsumary()
    {
        $detail  = DB::connection("sqlsrv3")->select("select ji.ClientID, ci.SealNumber, ci.ContainerPrefix, ci.ContainerNumber,ci.ContainerSize, ci.ContainerType, ji.POD, ci.Bay, ci.Stevedore from " . $this->db2012 . ".dbo.JobInfo ji, " . $this->db2012 . ".dbo.ContainerInfo ci where ji.JobNumber = ci.JobNumber and ci.Dummy =  '" . $_GET['dummy'] . "'");
        $joblist = DB::connection("sqlsrv3")->select("select i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, i.CheckStatus, max(ib.Markings) Markings, sum(ib.Quantity) Quantity, i.MQuantity, i.MVolume, i.Status, i.MWeight, i.POD, max(ib.Remarks) Remarks from " . $this->db2017 . ".dbo.HSC_Inventory i, " . $this->db2017 . ".dbo.HSC_InventoryPallet ip, " . $this->db2017 . ".dbo.HSC_InventoryBreakdown ib where i.InventoryID = ip.InventoryID and ip.InventoryPalletID = ib.InventoryPalletID and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and i.CntrID = '" . $_GET['dummy'] . "' group by i.InventoryID, i.SequenceNo, i.SequencePrefix, i.HBL, i.CheckStatus, i.MQuantity, i.MVolume, i.MWeight, i.Status, i.POD");
        $check   = array();
        foreach ($joblist as $key => $value)
        {
          if ($value->CheckStatus == 'Y') {
            array_push($check, 'yes');
          }
        }
        $data = array(
            'status' => 'success',
            'container' => $detail,
            'is_completed' => count($joblist) == count($check) ? 'yes' : 'no'
        );
        return response($data);
    }
    public function detailimportsummary()
    {
        $query = DB::connection("sqlsrv3")->select("select SequenceNo, SequencePrefix, HBL, POD, Note, MQuantity, Status from " . $this->db2017 . ".dbo.HSC_Inventory where CntrID ='" . $_GET['dummy'] . "' and Delstatus = 'N' ORDER BY SequenceNo ASC");
        $data  = array(
            'data' => $query
        );
        return response($data);
    }
    function updateBaySteveDore(Request $request)
    {
        
        Log::debug('DEBUG QUERY -  UPDATE BAY_STEVEDORE');
        Log::debug('DEBUG VALUE -  UPDATE ' . $request->get('type') . ' with value ' . $request->get('data'));
        DB::connection("sqlsrv3")->table(  $this->db2012 . '.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
            $request->get('type') => trim($request->get('data'))
        ));
        DB::connection("sqlsrv3")->table(  $this->dbhsc .'.dbo.ContainerInfo')->where('Id', $request->get('dummy'))->update(array(
            $request->get('type') => trim($request->get('data'))
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function startJob(Request $request)
    {
        DB::connection("sqlsrv3")->table( $this->db2012 . '.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
            'StartTime' => date("Y-m-d H:i:s"),
            'NTunstuffingstatus' => "PROCESSING " . $request->get('TallyBy')
        ));
        DB::connection("sqlsrv3")->table($this->dbhsc . '.dbo.ContainerInfo')->where('Id', $request->get('dummy'))->update(array(
            'StartTime' => date("Y-m-d H:i:s")
        ));
        $dir = '\\\\SERVER-DB\\Files\\Photo\\';

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                $uold     = umask(0);
                mkdir($dir . $request->get('dummy'), 0775);
                umask($uold);
            }
        }
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    public function revertJob(Request $request)
    {
        DB::connection("sqlsrv3")->table($this->db2012 .'.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
            'StartTime' => null,
            'NTunstuffingstatus' => "EMPTY"
        ));
        DB::connection("sqlsrv3")->table($this->dbhsc .'.dbo.ContainerInfo')->where('Id', $request->get('dummy'))->update(array(
            'StartTime' => null,
        ));
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function finishJob(Request $request)
    {
        DB::connection("sqlsrv3")->table($this->db2012 . '.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->update(array(
            'TallyBy' => $request->get('TallyBy'),
            'Status' => 'EMPTY',
            'EndTime' => date("Y-m-d H:i:s"),
            'DateofStuf/Unstuf' => date("Y-m-d H:i:s"),
            'NTunstuffingstatus' => "COMPLETED",
        ));
        DB::connection("sqlsrv3")->table($this->dbhsc . '.dbo.ContainerInfo')->where('Id', $request->get('dummy'))->update(array(
            'TallyBy' => $request->get('TallyBy'),
            'Status' => 'EMPTY',
            'EndTime' => date("Y-m-d H:i:s"),
            'DateofStuf' => date("Y-m-d H:i:s"),
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function getJobList(Request $request)
    {
        $joblist = DB::connection("sqlsrv3")->select("select i.InventoryID, i.CheckStatus, i.SequenceNo, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) Quantity,
        i.MQuantity, i.MVolume, i.Status, i.MWeight, i.POD, i.LockedBy, max(ib.Remarks) Remarks
        from " . $this->db2017 . ".dbo.HSC_Inventory i, " . $this->db2017 . ".dbo.HSC_InventoryPallet ip, " . $this->db2017 . ".dbo.HSC_InventoryBreakdown ib
        where i.InventoryID = ip.InventoryID and ip.InventoryPalletID = ib.InventoryPalletID and i.DelStatus = 'N'
        and ip.DelStatus = 'N' and ib.DelStatus = 'N' and i.CntrID = '" . $_GET['dummy'] . "'
        group by i.InventoryID, i.CheckStatus, i.SequenceNo, i.SequencePrefix, i.HBL, i.MQuantity, i.MVolume, i.MWeight, i.Status, i.POD, i.LockedBy
        order by i.CheckStatus, i.SequenceNo, i.SequencePrefix");
        $container = DB::connection("sqlsrv3")->table('HSC2012.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->first();
        $data    = array(
            'count' => count($joblist),
            'status' => "success",
            'data' => $joblist,
            'floorboard' => $container->Floorboard
        );
        return response($data);
    }
    function checkLockedInventory(Request $request)
    {
        $inventory  = DB::connection("sqlsrv3")->table($this->db2017 . '.dbo.HSC_Inventory')->select('LockedBy', 'LockedPC')->where('InventoryID', $request->get('InventoryID'))->first();
        // dd($inventory);
        if(empty($inventory))
        {
            $data    = array(
                'status' => false,
                'LockedBy' => $inventory->LockedBy,
                'LockedPC' => $inventory->LockedPC
            );
            return response($data);
        }else{

            $data    = array(
                'status' => true,
                'LockedBy' => $inventory->LockedBy,
                'LockedPC' => $inventory->LockedPC
            );
            return response($data);
        }
    }
    function updateLockedInventory(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
            'LockedBy' => $request->get('type') == "locked" ? $request->get('LockedBy') : "" ,
            'LockedDt' => $request->get('type') == "locked" ? date("Y-m-d H:i:s") : null,
            'LockedPC' => $request->get('type') == "locked" ? $request->get('LockedPC') : "" ,
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function addOverlanded(Request $request)
    {
        $inventoryPalletID = 0;
        DB::connection("sqlsrv3")->update("SET ANSI_NULLS ON; SET ANSI_WARNINGS ON;exec " . $this->db2017 . ".dbo.InventoryPallet_InsertOverlanded " . $inventoryPalletID . ", "  . $request->get('Dummy') .", " .  $request->get('CreatedBy') ."");
        
        $data    = array(
            'status' => "success",
            // 'data' => $inventory
        );
        return response($data);
    }
    function getPalletBreakdown()
    {
        $pallet    = array();
        $breakdown = array();
        $rawPallet = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryID', $_GET['inventoryid'])->where('DelStatus', 'N')->orderBy('InventoryPalletID', 'ASC')->get();
        $i         = 1;
        // dd($rawPallet);
        // dd($value->DeliveryID);
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
                "Location" => is_null($value->Location) ? "" : $value->Location,
            );
            // array_push($DeliveryID, is_null($value->DeliveryID) ? 0 : 1);
            array_push($DeliveryID, $value->DeliveryID >= 1 ? 1 : 0);
            array_push($pallet, $loopPallet);
            $rawBreakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $value->InventoryPalletID)->where('DelStatus', 'N')->orderBy('BreakDownID', 'ASC')->get();
            $x            = 1;
            $lastFrom     = null;
            foreach ($rawBreakdown as $keyBreak => $break)
            {
                $galleries = array();
                $images   = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->where('BreakDownID', $break->BreakDownID)->where('DelStatus', 'N')->get();
                foreach($images as $gallery)
                {
                    if ($gallery->PhotoNameSystem) {
                        // Production
                        $loadImage = str_replace("//server-db/Files/Photo", "http://192.168.14.70:9030/", $gallery->PhotoNameSystem);
                        $loadPath  = str_replace("//server-db/Files/Photo", "", $gallery->PhotoNameSystem);
                        $file      = '\\\\SERVER-DB\\Files\\Photo\\' . $loadPath;

                        if (file_exists($file))
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

                        // TESTING
                        // $loadImage = str_replace("//server-db/Files/Photo","http://192.168.14.70:9030/",$gallery->PhotoNameSystem);
                        // $loadPath = str_replace("//server-db/Files/Photo","",$gallery->PhotoNameSystem);
                        // $file = '../../../Photo2' . $loadPath;
                        // if (file_exists($file)) {
                        //     list($width, $height) = getimagesize($file);
                        //     $imageGallery = array(
                        //         'width' => $width,
                        //         'InventoryPhotoID' => $gallery->InventoryPhotoID,
                        //         'PhotoName' => $gallery->PhotoName,
                        //         'PhotoExt' => $gallery->PhotoExt,
                        //         'PhotoNameSystem' => $loadImage
                        //     );
                        //     array_push($galleries, $imageGallery);
                        // }
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
                    "gallery" => $galleries
                );
                array_push($breakdown, $loopBreakdown);
            }
        }
        $typeChecklist  = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'type')->get();
        $flagsChecklist = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'flag')->get();
        $locations      = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')->where('Category', 'location')->get();
        // dd($galleries);
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
        Log::debug('DEBUG QUERY -  COPY PALLET FROM ' . $request->get('InventoryPalletID'));
        $copy          = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $request->get('InventoryPalletID'))->first();
        //   $listAvailable = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryID', $copy->InventoryID)->where('DelStatus', 'N')->get();
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
        $breakdownId = 0;
        DB::connection("sqlsrv3")->update("SET ANSI_NULLS ON; SET ANSI_WARNINGS ON;exec HSC2017.dbo.InventoryBreakdown_CopyBreakdown " . $breakdownId . ", "  . $breakdownRaw->BreakDownID .", " .  $request->get('CreatedBy') ."");
        
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function deleteBreakdown(Request $request)
    {

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
            'status' => $count >= 1 ? false : true,
        );
        return response($data);
    }
    public function updatePallet(Request $request)
    {
        Log::debug('DEBUG QUERY -  UPDATE PALLET');
        Log::debug('DEBUG QUERY -  UPDATE PALLET TRY - '. $request->post('type') . ' with data ' . $request->post('data'));
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

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function updateBreakdownLBH(Request $request)
    {
        Log::debug('DEBUG QUERY -  UPDATE BREAKDOWN PROC');
        Log::debug('DEBUG QUERY -  UPDATE BREAKDOWN PROC TRY - FLAG with data ' . $request->post('F'));
        Log::debug('DEBUG QUERY -  UPDATE BREAKDOWN PROC  - LBH with data ' . $request->post('L'));
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
        $maxId = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->max('InventoryPhotoID');
        $maxOrdering = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->where('BreakDownID', $request->post('BreakDownID'))->max('Ordering');
        // getContainerID
        $palletID = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->first();
        $inventoryID = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryPalletID', $palletID->InventoryPalletID)->first();
        $cntr = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $inventoryID->InventoryID)->first();
        $cover     = $request->file('image');
        $image     = $cover->getClientOriginalName();
        $filename  = pathinfo($image, PATHINFO_FILENAME);
        $extension = pathinfo($image, PATHINFO_EXTENSION);
        // $finalName = $filename . '_' . time() . '.' . $extension;
        $finalName = 'inventoryPhoto_' . ($maxId + 1) . '.' . $extension;
        $finalNameDB = 'inventoryPhoto_' . ($maxId + 1);

        // temp folder
        Storage::disk('public')->put('temp/' . $finalName, File::get($cover));

        $imageFix = public_path() . '/temp/' . $finalName;

        $dir = '\\\\SERVER-DB\\Files\\Photo\\';
        // $dir = '../../../Photo2/';

        if (is_dir($dir))
        {
            if (!file_exists($dir . $cntr->CntrID))
            {
              mkdir($dir . $cntr->CntrID, 0775);
            }
        }

        list($width, $height) = getimagesize($imageFix);
        if ($width > $height)
        {
            $source = imagecreatefromjpeg($imageFix);

            $rotate = imagerotate($source, 90, 0);
            $image_resize = Image::make($rotate);
            $image_resize->resize(640, 480);
            $image_resize->text(date("d/m/Y H:i"), 500, 400, function($font) {
                $font->file(public_path() . '/fonts/RobotoCondensed-Bold.ttf');
                $font->size(20);
                $font->color('#FFFF00');
                $font->align('center');
                $font->valign('bottom');
            });
            $image_resize->save(public_path('image/breakdown/' .$finalName));
        }else{
            $image_resize = Image::make($imageFix);
            $image_resize->resize(480, 640);
            $image_resize->text(date("d/m/Y H:i"), 350, 620, function($font) {
                $font->file(public_path() . '/fonts/RobotoCondensed-Bold.ttf');
                $font->size(20);
                $font->color('#FFFF00');
                $font->align('center');
                $font->valign('bottom');
            });
            $image_resize->save(public_path('image/breakdown/' .$finalName));
        }
        copy(public_path('image/breakdown/' . $finalName), $dir . $cntr->CntrID . '/' . $finalName);

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
            'PhotoNameSystem' => "//server-db/Files/Photo/" . $cntr->CntrID . '/' . $finalName
        );
        $id = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->insertGetId($dataImg);
        unlink(public_path('image/breakdown/' .$finalName));
        $data = array(
            'status' => 'success',
            'last_photo' => array(
              'InventoryPhotoID' => $id,
              'PhotoNameSystem' => $cntr->CntrID . '/' .$finalName
            )
        );
        return response($data);
    }
    function deleteBreakdownPhoto(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->where('InventoryPhotoID', $request->post('InventoryPhotoID'))->update(array(
            'DelStatus' => 'Y',
            'ModifyDt' => date("Y-m-d H:i:s"),
            'ModifyBy' => $request->get('UpdatedBy'),
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function uploadPhotoHBL(Request $request)
    {
        $maxId = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_CntrPhoto')->max('CntrPhotoID');
        $maxOrdering = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_CntrPhoto')->where('CntrID', $request->post('CntrID'))->max('Ordering');
        $cover     = $request->file('image');
        $image     = $cover->getClientOriginalName();
        $filename  = pathinfo($image, PATHINFO_FILENAME);
        $extension = pathinfo($image, PATHINFO_EXTENSION);
        $finalName = 'cntrPhoto_' . ($maxId + 1) . '.' . $extension;
        $finalNameDB = 'cntrPhoto_' . ($maxId + 1);
        // temp folder
        Storage::disk('public')->put('temp/' . $finalName, File::get($cover));

        $imageFix = public_path() . '/temp/' . $finalName;

        $dir = '\\\\SERVER-DB\\Files\\Photo\\';
        // $dir = '../../../Photo2';

        list($width, $height) = getimagesize($imageFix);
        if ($width > $height)
        {
            $source = imagecreatefromjpeg($imageFix);

            $rotate = imagerotate($source, 90, 0);
            $image_resize = Image::make($rotate);
            $image_resize->resize(640, 480);
            $image_resize->text(date("d/m/Y H:i"), 500, 400, function($font) {
                $font->file(public_path() . '/fonts/RobotoCondensed-Bold.ttf');
                $font->size(20);
                $font->color('#FFFF00');
                $font->align('center');
                $font->valign('bottom');
            });
            $image_resize->save(public_path('image/container/' . $finalName));
        }else{
            $image_resize = Image::make($imageFix);
            $image_resize->resize(480, 640);
            $image_resize->text(date("d/m/Y H:i"), 350, 620, function($font) {
                $font->file(public_path() . '/fonts/RobotoCondensed-Bold.ttf');
                $font->size(20);
                $font->color('#FFFF00');
                $font->align('center');
                $font->valign('bottom');
            });
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
            'PhotoNameSystem' => "//server-db/Files/Photo/" . $request->post('CntrID') . '/' . $finalName
        );
        $id = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_CntrPhoto')->insertGetId($dataImg);
        unlink(public_path('image/container/' .$finalName));
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
        $images = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_CntrPhoto')->where('CntrID', $request->get('CntrID'))->where('DelStatus', 'N')->get();
        foreach($images as $gallery)
        {

            $loadImage = str_replace("//server-db/Files/Photo","http://192.168.14.70:9030/",$gallery->PhotoNameSystem);
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
        $inventory = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->first();
        $pallet    = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->select('InventoryPalletID', 'Tag')->where('InventoryID', $request->get('InventoryID'))->get();
        $ContainerInfo =  DB::connection("sqlsrv3")->table('HSC2012.dbo.ContainerInfo')->where('Dummy', $inventory->CntrID)->first();
        $jobInfo =  DB::connection("sqlsrv3")->table('HSC2012.dbo.JobInfo')->where('JobNumber', $ContainerInfo->JobNumber)->first();
        if ($inventory->HBL == 'OVERLANDED') {
          $data = array(
              'status' => "success"
          );
          return response($data);
        }else{
          $qty = 0;
          $totalTag = 0;
          $totalPhoto = 0;
          $check   = array();
          foreach ($pallet as $key => $plt) {
              if ($plt->Tag) {
                $totalTag += 1;
              }
              $breakdown    = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->select('BreakDownID', 'Quantity', 'Flags')->where('InventoryPalletID', $plt->InventoryPalletID)->where('DelStatus', 'N')->get();
              foreach ($breakdown as $key => $brk) {
                $qty += $brk->Quantity;

                $photo = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->select('InventoryPhotoID', 'DelStatus')->where('BreakDownID', $brk->BreakDownID)->where('DelStatus', 'N')->count();
                $totalPhoto += $photo;
                if (in_array('SHORTLANDED', str_replace(' ', '', explode(',', $brk->Flags))))
                {
                    array_push($check, 'yes');
                }
              }
          }

          $MQty = (int) $inventory->MQuantity;
          if (in_array('yes', $check))
          {
            DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                'CheckStatus' => 'Y'
            ));
          }
          else if($MQty == $qty)
          {
            DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                'CheckStatus' => 'Y'
            ));
            if ($jobInfo->ClientID == 'VANGUARD' && $totalPhoto >= 1)
            {
                DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                    'CheckStatus' => 'Y'
                ));
            }
            elseif ($totalPhoto >= 1 && $totalTag >= 1)
            {
                DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                    'CheckStatus' => 'Y'
                ));
            }
            elseif ($inventory->ToDGWhse == 1)
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
        //   elseif ($jobInfo->ClientID == 'VANGUARD' && $totalPhoto >= 1)
        //   {
        //     DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
        //         'CheckStatus' => 'Y'
        //     ));
        //   }
        //   elseif ($inventory->ToDGWhse == 1)
        //   {
        //     DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
        //         'CheckStatus' => 'Y'
        //     ));
        //   }
          else
          {
            DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
                'CheckStatus' => 'N'
            ));
          }
          $data = array(
              'status' => "success"
          );
          return response($data);
        }
    }
}
