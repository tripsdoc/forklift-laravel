<?php
namespace App\Http\Controllers;

date_default_timezone_set('Asia/Singapore');
use DB;
use Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManagerStatic as Image;
class StuffingController extends Controller
{
    protected $mode;
    protected $db2017;
    protected $db2012;
    protected $dbhsc;
    public function __construct()
    {
        $this->mode = "dev";
        switch ($this->mode)
        {
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
    
    public function exportSummary(Request $request)
    {
        $str = $request->get("warehouse");
        $exp = explode(",", $str);
        $parseTag = "";
        foreach ($exp as $value)
        {
            $parseTag = $parseTag . ",'" . $value . "'";
        }
        $tagging = substr($parseTag, 1);
        $query = DB::connection("sqlsrv3")->select("SELECT CI.DeliverTo, CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, CI.Dummy, CI.NTunstuffingstatus,
        CI.TallyBy, JI.ClientID, JI.POD, SUM(IB.Quantity) Qty
        FROM HSC2017.dbo.HSC_InventoryPallet IP, HSC2012.dbo.ContainerInfo CI, HSC2012.dbo.JobInfo JI, HSC2012.dbo.VesselInfo VI,
        HSC2017.dbo.HSC_InventoryBreakdown IB
        WHERE IP.ExpCntrID = CI.Dummy
        AND CI.JobNumber = JI.JobNumber
        AND JI.VesselID = VI.VesselID
        AND IP.InventoryPalletID = IB.InventoryPalletID
        AND IP.DelStatus = 'N'
        AND IB.DelStatus = 'N'
        AND CI.Status not in ('NEW','PRINTED','PENDING','INVOICED','CLOSED','CANCELLED')
        AND CI.[DateofStuf/Unstuf] IS NULL
        AND CI.DeliverTo IN (" . $tagging . ")
        GROUP BY CI.DeliverTo, CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, CI.NTunstuffingstatus, CI.TallyBy,
        JI.ClientID, JI.POD, CI.Dummy, VI.ETA
        ORDER BY CI.NTUnstuffingStatus DESC, VI.ETA");
        $data = array(
            'data' => $query
        );
        // dd($data);
        return response($data);
    }

    public function detailExportSummary()
    {
        $query = DB::connection("sqlsrv3")->select("select SequenceNo, SequencePrefix, HBL, POD, Note, MQuantity, Status from " . $this->db2017 . ".dbo.HSC_Inventory where CntrID ='" . $_GET['dummy'] . "' and Delstatus = 'N' ORDER BY SequenceNo ASC");
        $data = array(
            'data' => $query
        );
        return response($data);
    }
    public function infoExport(Request $request)
    {
        $query = DB::connection("sqlsrv3")->select("select T.SequenceNo, T.ExportRef, IB.Markings,
        case when I.StorageDate is not null then 'send in' else 'transhipment' end InvStatus,
        SUM(IB.Quantity) TotalQty, BreakdownIDs = '^' + isnull(STUFF((
        SELECT '^'+ CONVERT(varchar(10), T1.BreakDownIDImp) AS [text()]
        FROM HSC2017.dbo.HSC_TempExpPlan T1, HSC2017.dbo.HSC_InventoryBreakdown IB1
        WHERE T1.BreakDownIDImp = IB1.BreakDownID
        AND T1.CntrIDExp = " . $request->get('CntrIDExp') . "
        AND T1.ExportRef = T.ExportRef
        AND IB1.Markings = IB.Markings
        FOR XML PATH('')), 1, 1,''),'') + '^',
        case when I.StorageDate is not null then IP.CurrentLocation else ci.DeliverTo end CurrLocation
        from HSC2017.dbo.HSC_TempExpPlan T, HSC2017.dbo.HSC_InventoryBreakdown IB, HSC2017.dbo.HSC_InventoryPallet IP,
        HSC2017.dbo.HSC_Inventory I, HSC2012.dbo.ContainerInfo CI
        where T.BreakDownIDImp = IB.BreakDownID
        and IB.InventoryPalletID = IP.InventoryPalletID
        and IP.InventoryID = I.InventoryID
        and I.CntrID = CI.Dummy
        and T.CntrIDExp = " . $request->get('CntrIDExp') . "
        and T.DelStatus = 'N'
        and IB.DelStatus = 'N'
        and IP.DelStatus = 'N'
        and I.DelStatus = 'N'
        group by T.SequenceNo, T.ExportRef, IB.Markings,
        case when I.StorageDate is not null then 'send in' else 'transhipment' end,
        case when I.StorageDate is not null then IP.CurrentLocation else ci.DeliverTo end
        order by T.SequenceNo");
        $list = array();
        foreach ($query as $key => $value) {
            $galleries = array();
            $photos = DB::connection("sqlsrv3")->select("select PH.* from HSC2017.dbo.HSC_TempExpPlan T, HSC2017.dbo.HSC_InventoryBreakdown IB, HSC2017.dbo.HSC_InventoryPhoto PH where T.BreakDownIDImp = IB.BreakDownID and IB.BreakDownID = PH.BreakDownID and T.CntrIDExp = " . $request->get('CntrIDExp') . " and T.DelStatus = 'N' and IB.DelStatus = 'N' and PH.DelStatus = 'N' and charindex('^'+CONVERT(varchar(10),T.BreakDownIDImp)+'^', '" . $value->BreakdownIDs ."') > 0");
     
            foreach ($photos as $key => $img) {
                $loadImage = str_replace("//server-db/Files/Photo", "http://192.168.14.70:9030/", $img->PhotoNameSystem);
                $loadPath = str_replace("//server-db/Files/Photo", "", $img->PhotoNameSystem);
                $file = '\\\\SERVER-DB\\Files\\Photo\\' . $loadPath;
                if (file_exists($file))
                {
                    $dataImg = array(
                        'InventoryPhotoID' => $img->InventoryPhotoID,
                        'BreakDownID' => $img->BreakDownID,
                        'PhotoName' => $img->PhotoName,
                        'PhotoExt' => $img->PhotoExt,
                        'PhotoNameSystem' => $loadImage,
                    );
                    array_push($galleries, $dataImg);
                }
            }
            $data = array(
                'SequenceNo' => $value->SequenceNo,
                'ExportRef' => $value->ExportRef,
                'Markings' => $value->Markings,
                'TotalQty' => $value->TotalQty,
                'InvStatus' => $value->InvStatus,
                'BreakdownIDs' => $value->BreakdownIDs,
                'CurrLocation' => $value->CurrLocation,
                'photos' => $galleries
            );
            array_push($list, $data);
        }
        $data = array(
            'data' => $list
        );
        return response($data);
    }
    public function detailExport(Request $request)
    {
//         $query = DB::connection("sqlsrv3")->select("select ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, 
//         ci.SealNumber, ci.BkRef, ci.YardRemarks, sum(ib.Quantity) TotalQty, sum(ib.Volume) TotalVolume,
//         count(distinct case when ip.Tag = '' then null else ip.Tag end) TotalTag,
//         count(distinct ip.InventoryPalletID) TotalPallet, ci.Bay, ci.Stevedore, ci.NTunstuffingstatus, 
//         HSC2017.dbo.fn_GetDGClass(ExpPlan.CntrIDExp, '', 'TEMP_EXP_PLANNING') DgClass,
//         rtrim(ltrim(max(ExpRemarks.Remarks))) ExportRemarks, ci.SevenPoints
//  from HSC2017.dbo.HSC_InventoryPallet IP inner join
//  HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID inner join
//       HSC2017.dbo.HSC_TempExpPlan ExpPlan on ib.BreakDownID = ExpPlan.BreakDownIDImp inner join
//       HSC2012.dbo.ContainerInfo CI on CI.Dummy = ExpPlan.CntrIDExp inner join
//       HSC2012.dbo.JobInfo JI on ji.JobNumber = ci.JobNumber left join
//       HSC2017.dbo.HSC_TempExpPlanRemarks ExpRemarks on ExpPlan.CntrIDExp = ExpRemarks.CntrIDExp and ExpRemarks.DelStatus = 'N'
//  where ExpPlan.CntrIDExp = " . $request->get("CntrID") . "
//  and ip.DelStatus = 'N'
//  and ib.DelStatus = 'N'
//  and ExpPlan.DelStatus = 'N'
//  and ci.Status <> 'CANCELLED'
//  group by ExpPlan.CntrIDExp, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, 
//           ci.SealNumber, ci.BkRef, ci.YardRemarks, ci.Bay, ci.Stevedore, ci.SevenPoints, ci.NTunstuffingstatus");
        $query = DB::connection("sqlsrv3")->select("select ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD,
        ci.SealNumber, ci.BkRef, ci.YardRemarks, isnull(HSC2017.dbo.fn_GetInvQty(0,0,0,ExpPlan.CntrIDExp,'','TEMP_EXP_PLANNING',''),0) TotalQty, 
        ISNULL(HSC2017.dbo.fn_GetInvVolume(0,0,0,ExpPlan.CntrIDExp,'','TEMP_EXP_PLANNING',''),0) TotalVolume,
        count(distinct case when ip.Tag = '' then null else ip.Tag end) TotalTag,
        count(distinct ip.InventoryPalletID) TotalPallet, ci.Bay, ci.Stevedore, ci.NTunstuffingstatus,
        HSC2017.dbo.fn_GetDGClass(ExpPlan.CntrIDExp, '', 'TEMP_EXP_PLANNING') DgClass,
        rtrim(ltrim(max(ExpRemarks.Remarks))) ExportRemarks, ci.SevenPoints,
        HSC2017.dbo.fn_GetDraftRemark(0,0,0,ExpPlan.CntrIDExp,'','TEMP_EXP_PLANNING','') as DrafRemark
 from  HSC2017.dbo.HSC_InventoryPallet IP inner join
 HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID inner join
 HSC2017.dbo.HSC_TempExpPlan ExpPlan on ib.BreakDownID = ExpPlan.BreakDownIDImp inner join
      HSC2012.dbo.ContainerInfo CI on CI.Dummy = ExpPlan.CntrIDExp inner join
      HSC2012.dbo.JobInfo JI on ji.JobNumber = ci.JobNumber left join
      HSC2017.dbo.HSC_TempExpPlanRemarks ExpRemarks on ExpPlan.CntrIDExp = ExpRemarks.CntrIDExp and ExpRemarks.DelStatus = 'N'
 where ExpPlan.CntrIDExp = " . $request->get("CntrID") . "
 and ip.DelStatus = 'N'
 and ib.DelStatus = 'N'
 and ExpPlan.DelStatus = 'N'
 and ci.Status <> 'CANCELLED'
 group by ExpPlan.CntrIDExp, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD,
          ci.SealNumber, ci.BkRef, ci.YardRemarks, ci.Bay, ci.Stevedore, ci.SevenPoints, ci.NTunstuffingstatus");
      
        $checklistBay = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
            ->where('Category', 'Bay')
            ->get();
        $checklistStevedore = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
            ->where('Category', 'Stevedore')
            ->get();
        $checklist7P = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
            ->where('Category', '7p')
            ->get();
        $InventoryList = DB::connection("sqlsrv3")->select("select ci.Dummy, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, i.InventoryID, i.SequenceNo,
        i.Status, i.SequencePrefix, i.HBL, max(ib.Markings) Markings, sum(ib.Quantity) TotalQty, i.MQuantity, i.MWeight, i.MVolume,
        count(distinct case when ip.Tag = '' then null else ip.Tag end) TotalTag,
        TagList = STUFF((
                    SELECT ', '+ ISNULL(IP1.Tag,'') AS [text()]
                    FROM HSC2017.dbo.HSC_InventoryPallet IP1
                    WHERE IP1.ExpCntrID = IP.ExpCntrID
                      AND Tag <> ''
                    FOR XML PATH('')
                  ), 1, 2,''),
        I.CheckStatusStuffing
        from HSC2017.dbo.HSC_Inventory I inner join
        HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join
        HSC2012.dbo.ContainerInfo CI on CI.Dummy = IP.ExpCntrID inner join
        HSC2012.dbo.JobInfo JI on ji.JobNumber = ci.JobNumber inner join
        HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID
        where ip.ExpCntrID = '" . $request->get("CntrID") . "'
        and i.DelStatus = 'N'
        and ip.DelStatus = 'N'
        and ib.DelStatus = 'N'
        group by ip.ExpCntrID, ci.Dummy, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD,
        i.InventoryID, i.Status, i.SequenceNo, i.SequencePrefix, i.HBL, i.MWeight, i.MVolume, i.MQuantity, I.CheckStatusStuffing
        order by i.SequenceNo, i.SequencePrefix");
        $checkIsCompleted = array();
        // dd($InventoryList);
        if ($InventoryList)
        {
            foreach ($InventoryList as $key => $valueCheckInventory)
            {
                if ($valueCheckInventory->CheckStatusStuffing == "Y")
                {
                    array_push($checkIsCompleted, true);
                }
                else
                {
                    array_push($checkIsCompleted, false);
                }
            }
        }
        $seventPointSelected = array();
        if ($query)
        {
            $strExplode = array_map('trim', explode(',', $query[0]->SevenPoints));
            if ($query[0]->SevenPoints)
            {
                foreach ($checklist7P as $key => $fl)
                {
                    if (in_array(trim($fl->Value), $strExplode))
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
        $data = array(
            'data' => $query ? $query : null,
            'bay' => $checklistBay,
            'stevedore' => $checklistStevedore,
            'sevenPoints' => $checklist7P,
            'sevenPointsSelected' => $seventPointSelected,
            'isCompleted' => in_array(false, $checkIsCompleted) ? false : true
        );
        return response($data);
    }
    function updateContainerInfo(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2012.dbo.ContainerInfo')
            ->where('Dummy', $request->get('dummy'))
            ->update(array(
            $request->get('type') => $request->get('data')
        ));
        DB::connection("sqlsrv3")->table('HSC_IPS.dbo.ContainerInfo')
            ->where('Id', $request->get('dummy'))
            ->update(array(
            $request->get('type') => $request->get('data')
        ));
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function getCurrentContainer(Request $request)
    {
        $counter = DB::connection("sqlsrv3")->table($this->db2012 . '.dbo.ContainerInfo')
            ->where('NTunstuffingstatus', "PROCESSING " . $request->get('username'))
            ->first();
        $count = DB::connection("sqlsrv3")->table($this->db2012 . '.dbo.ContainerInfo')
            ->where('NTunstuffingstatus', "PROCESSING " . $request->get('username'))
            ->count();
        $data = array(
            'dummy' => $counter ? $counter->Dummy : "",
            'counter' => $count
        );
        return response($data);
    }
    public function startJob(Request $request)
    {
        DB::connection("sqlsrv3")->table($this->db2012 . '.dbo.ContainerInfo')
            ->where('Dummy', $request->get('dummy'))
            ->update(array(
            'StartTime' => date("Y-m-d H:i:s") ,
            'NTunstuffingstatus' => "PROCESSING " . $request->get('TallyBy')
        ));
        DB::connection("sqlsrv3")
            ->table($this->dbhsc . '.dbo.ContainerInfo')
            ->where('Id', $request->get('dummy'))
            ->update(array(
            'StartTime' => date("Y-m-d H:i:s")
        ));
        DB::connection("sqlsrv3")->statement("UPDATE HSC2017.dbo.HSC_InventoryPallet
        SET isActivityForStuffing = 1
        FROM HSC2017.dbo.HSC_InventoryPallet
        INNER JOIN HSC2017.dbo.HSC_InventoryBreakdown IB ON HSC_InventoryPallet.InventoryPalletID = IB.InventoryPalletID
        INNER JOIN HSC2017.dbo.HSC_TempExpPlan T ON IB.BreakDownID = T.BreakDownIDImp
        WHERE T.CntrIDExp = " . $request->get('dummy') . "
        AND HSC_InventoryPallet.DelStatus = 'N'
        AND IB.DelStatus = 'N'
        AND T.DelStatus = 'N'
        AND ISNULL(HSC_InventoryPallet.isActivityForStuffing, 0) = 0
        AND HSC_InventoryPallet.Tag <> ''");
        DB::connection("sqlsrv3")->statement("UPDATE HSC_IPS.dbo.InventoryPallet
        SET isActivityForStuffing = 1
        FROM HSC_IPS.dbo.InventoryPallet
        INNER JOIN HSC_IPS.dbo.InventoryBreakdown IB ON InventoryPallet.InventoryPalletID = IB.InventoryPalletID
        INNER JOIN HSC2017.dbo.HSC_TempExpPlan T ON IB.BreakDownID = T.BreakDownIDImp
        WHERE T.CntrIDExp = " . $request->get('dummy') . "
        AND InventoryPallet.DelStatus = 'N'
        AND IB.DelStatus = 'N'
        AND T.DelStatus = 'N'
        AND ISNULL(InventoryPallet.isActivityForStuffing, 0) = 0
        AND InventoryPallet.Tag <> ''");
        
        $dir = '\\\\SERVER-DB\\Files\\Photo\\';

        if (is_dir($dir))
        {
            if ($dh = opendir($dir))
            {
                $uold = umask(0);
                mkdir($dir . $request->get('dummy') , 0775);
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
        DB::connection("sqlsrv3")->table($this->db2012 . '.dbo.ContainerInfo')
            ->where('Dummy', $request->get('dummy'))
            ->update(array(
            'StartTime' => null,
            'NTunstuffingstatus' => "EMPTY"
        ));
        DB::connection("sqlsrv3")
            ->table($this->dbhsc . '.dbo.ContainerInfo')
            ->where('Id', $request->get('dummy'))
            ->update(array(
            'StartTime' => null,
        ));
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function finishJob(Request $request)
    {
        $check = DB::connection("sqlsrv3")->table($this->db2012 . '.dbo.ContainerInfo')->where('Dummy', $request->get('dummy'))->get();
        if ($check[0]->Status == "CREATED") 
        {
            DB::connection("sqlsrv3")->table($this->db2012 . '.dbo.ContainerInfo')
                ->where('Dummy', $request->get('dummy'))
                ->update(array(
                'TallyBy' => $request->get('TallyBy') ,
                'Status' => 'STUFFED',
                'EndTime' => date("Y-m-d H:i:s") ,
                'DateofStuf/Unstuf' => date("Y-m-d H:i:s") ,
                'NTunstuffingstatus' => "COMPLETED",
            ));
            DB::connection("sqlsrv3")
                ->table($this->dbhsc . '.dbo.ContainerInfo')
                ->where('Id', $request->get('dummy'))
                ->update(array(
                'TallyBy' => $request->get('TallyBy') ,
                'Status' => 'STUFFED',
                'EndTime' => date("Y-m-d H:i:s") ,
                'DateofStuf' => date("Y-m-d H:i:s") ,
            ));
        }
        else
        {
            DB::connection("sqlsrv3")->table($this->db2012 . '.dbo.ContainerInfo')
                ->where('Dummy', $request->get('dummy'))
                ->update(array(
                'TallyBy' => $request->get('TallyBy') ,
                'EndTime' => date("Y-m-d H:i:s") ,
                'DateofStuf/Unstuf' => date("Y-m-d H:i:s") ,
                'NTunstuffingstatus' => "COMPLETED",
            ));
            DB::connection("sqlsrv3")
                ->table($this->dbhsc . '.dbo.ContainerInfo')
                ->where('Id', $request->get('dummy'))
                ->update(array(
                'TallyBy' => $request->get('TallyBy') ,
                'EndTime' => date("Y-m-d H:i:s") ,
                'DateofStuf' => date("Y-m-d H:i:s") ,
            )); 
        }

        $data = array(
            'status' => "success"
        );
        return response($data);   
    }
    public function InventoryList(Request $request)
    {
        $rawQuery = "select ExpPlan.CntrIDExp Dummy, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, i.InventoryID,
        ExpPlan.SequenceNo, i.TranshipmentRef, i.CheckStatusStuffing, max(ib.Markings) Markings, sum(ib.Quantity) TotalQty, i.MQuantity,
        case when I.StorageDate is not null then 'send in' else 'transhipment' end InvStatus,
        count(distinct case when ip.Tag = '' then null else ip.Tag end) TotalTag,
        TagList = STUFF((
        SELECT ', '+ ISNULL(IP1.Tag,'') AS [text()]
        FROM HSC2017.dbo.HSC_InventoryPallet IP1, HSC2017.dbo.HSC_InventoryBreakdown IB1,
        HSC2017.dbo.HSC_TempExpPlan ExpPlan1
        WHERE IP1.InventoryPalletID = IB1.InventoryPalletID
        AND IB1.BreakDownID = ExpPlan1.BreakDownIDImp
        AND ExpPlan1.CntrIDExp = ExpPlan.CntrIDExp
        AND IP1.InventoryID = I.InventoryID
        AND IP1.Tag <> ''
        AND IP1.DelStatus = 'N'
        AND IB1.DelStatus = 'N'
        AND ExpPlan1.DelStatus = 'N'
        FOR XML PATH('')
        ), 1, 2,''),
        case when I.StorageDate is not null then I.StorageDate else CII.[DateofStuf/Unstuf] end StorageDate
        from HSC2012.dbo.ContainerInfo CII inner join
        HSC2017.dbo.HSC_Inventory I on I.CntrID = CII.Dummy inner join
        HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join
        HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID inner join
        HSC2017.dbo.HSC_TempExpPlan ExpPlan on ExpPlan.BreakDownIDImp = ib.BreakDownID inner join
        HSC2012.dbo.ContainerInfo CI on CI.Dummy = ExpPlan.CntrIDExp inner join
        HSC2012.dbo.JobInfo JI on ji.JobNumber = ci.JobNumber
        where ExpPlan.CntrIDExp = '" . $request->get("CntrID") . "'
        and i.DelStatus = 'N'
        and ip.DelStatus = 'N'
        and ib.DelStatus = 'N'
        and ExpPlan.DelStatus = 'N'
        and ci.Status <> 'CANCELLED'
        group by ExpPlan.CntrIDExp, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD,
        i.InventoryID, ExpPlan.SequenceNo, i.TranshipmentRef, i.MQuantity, i.CheckStatusStuffing,
        case when I.StorageDate is not null then 'send in' else 'transhipment' end,
        case when I.StorageDate is not null then I.StorageDate else CII.[DateofStuf/Unstuf] end
        order by ExpPlan.SequenceNo";
        $query = DB::connection("sqlsrv3")->select($rawQuery);
        $counterUpdate = 0;
        foreach ($query as $key => $value) {
            $counterUpdate += 1;
            $this->checkInventoryInternal($value->InventoryID, $request->get("CntrID"));
        }
        if (count($query) == $counterUpdate) {
            $queryLast = DB::connection("sqlsrv3")->select($rawQuery);
            $data = array(
                'data' => $queryLast
            );
            return response($data);
        } else {
            $data = array(
                'data' => $query
            );
            return response($data);
        }
    }
    public function InventoryList_old(Request $request)
    {
        $query = DB::connection("sqlsrv3")->select("select ExpPlan.CntrIDExp Dummy, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD, i.InventoryID,
        ExpPlan.SequenceNo, i.TranshipmentRef, i.CheckStatusStuffing, max(ib.Markings) Markings, sum(ib.Quantity) TotalQty, i.MQuantity,
        case when I.StorageDate is not null then 'send in' else 'transhipment' end InvStatus,
        count(distinct case when ip.Tag = '' then null else ip.Tag end) TotalTag,
        TagList = STUFF((
        SELECT ', '+ ISNULL(IP1.Tag,'') AS [text()]
        FROM HSC2017.dbo.HSC_InventoryPallet IP1, HSC2017.dbo.HSC_InventoryBreakdown IB1,
        HSC2017.dbo.HSC_TempExpPlan ExpPlan1
        WHERE IP1.InventoryPalletID = IB1.InventoryPalletID
        AND IB1.BreakDownID = ExpPlan1.BreakDownIDImp
        AND ExpPlan1.CntrIDExp = ExpPlan.CntrIDExp
        AND IP1.InventoryID = I.InventoryID
        AND IP1.Tag <> ''
        AND IP1.DelStatus = 'N'
        AND IB1.DelStatus = 'N'
        AND ExpPlan1.DelStatus = 'N'
        FOR XML PATH('')
        ), 1, 2,''),
        case when I.StorageDate is not null then I.StorageDate else CII.[DateofStuf/Unstuf] end StorageDate
        from HSC2012.dbo.ContainerInfo CII inner join
        HSC2017.dbo.HSC_Inventory I on I.CntrID = CII.Dummy inner join
        HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join
        HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID inner join
        HSC2017.dbo.HSC_TempExpPlan ExpPlan on ExpPlan.BreakDownIDImp = ib.BreakDownID inner join
        HSC2012.dbo.ContainerInfo CI on CI.Dummy = ExpPlan.CntrIDExp inner join
        HSC2012.dbo.JobInfo JI on ji.JobNumber = ci.JobNumber
        where ExpPlan.CntrIDExp = '" . $request->get("CntrID") . "'
        and i.DelStatus = 'N'
        and ip.DelStatus = 'N'
        and ib.DelStatus = 'N'
        and ExpPlan.DelStatus = 'N'
        and ci.Status <> 'CANCELLED'
        group by ExpPlan.CntrIDExp, ci.ContainerPrefix, ci.ContainerNumber, ci.ContainerSize, ci.ContainerType, ji.ClientID, ji.POD,
        i.InventoryID, ExpPlan.SequenceNo, i.TranshipmentRef, i.MQuantity, i.CheckStatusStuffing,
        case when I.StorageDate is not null then 'send in' else 'transhipment' end,
        case when I.StorageDate is not null then I.StorageDate else CII.[DateofStuf/Unstuf] end
        order by ExpPlan.SequenceNo");
        $data = array(
            'data' => $query
        );
        // dd($data);
        return response($data);
    }
    public function updateShutout(Request $request)
    {
        $getPallet = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')->where('InventoryID', $request->post('InventoryID'))->get();
        foreach ($getPallet as $key => $value) {
            $palletID = $value->InventoryPalletID;
            $getBreakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('InventoryPalletID', $palletID)->get();
            foreach ($getBreakdown as $key => $br) {

                if (empty($br->Flags)) {
                    $cleanFlag = array();
                    array_push($cleanFlag, "SHUT OUT");
                }else{
                    $firstFlag      = explode(",", $br->Flags);
                    $stuffingFlag   = array('SHUT OUT');
                    $cleanFlag      = array_diff(array_map('trim', $firstFlag), $stuffingFlag); 
                    // Log::info("MYFLAGcleanFlag", $cleanFlag);
                    array_push($cleanFlag, "SHUT OUT");
                    Log::info("append");
                }
                $flagFilter = array_filter(array_unique(array_map('trim', $cleanFlag)));
                Log::debug("FLAGFINAL", $flagFilter);
                DB::connection("sqlsrv3")->table('HSC_IPS.dbo.InventoryBreakdown')->where('BreakDownID', $br->BreakDownID)->update(array(
                    'Flags' => implode(", ", $flagFilter),
                    'UpdatedDt' => date("Y-m-d H:i:s"),
                    'UpdatedBy' => $request->get('UpdatedBy')
                ));
                DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $br->BreakDownID)->update(array(
                    'Flags' => implode(", ", $flagFilter),
                    'UpdatedDt' => date("Y-m-d H:i:s"),
                    'UpdatedBy' => $request->get('UpdatedBy')
                ));
            }
        }
        if ($this->checkInventoryInternal($request->post('InventoryID'), $request->post('CntrIDExp'))) {
            $data = array(
                'status' => "success"
            );
            return response($data);
        }else{
            $data = array(
                'status' => "success"
            );
            return response($data);
        }
    }
    function getPalletBreakdown(Request $request)
    {
        $pallet = array();
        $breakdown = array();
        // $sqlPallet = "select i.InventoryID, ExpPlan.SequenceNo, i.TranshipmentRef, i.MQuantity, i.MVolume, i.MWeight, ip.InventoryPalletID, ip.SequenceNo PltNo, ip.Tag, ib.Markings, ib.Quantity, ib.Type, ib.Length, ib.Breadth, ib.Height, ib.Volume, rtrim(ltrim(case when isnull(ExpPlan.DNS, 0) = 1 then '*Do Not Stack' else '' end + ' ' +  case when isnull(ExpPlan.TakePhoto, 0) = 1 then '*Take Photo' else '' end + ' ' + isnull(ExpPlan.Others,''))) SpecialInstruction from HSC2017.dbo.HSC_Inventory I inner join HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID inner join HSC2017.dbo.HSC_TempExpPlan ExpPlan on ib.BreakDownID = ExpPlan.BreakDownIDImp where i.InventoryID = " . $request->get('InventoryID') ." and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and ExpPlan.DelStatus = 'N' order by IP.SequenceNo, ib.BreakDownID";
        $sqlPallet = "select i.InventoryID, ExpPlan.SequenceNo, i.TranshipmentRef, i.MQuantity, i.MVolume, i.MWeight, ip.InventoryPalletID, ip.SequenceNo PltNo, ip.Tag, ib.Markings, ib.Quantity, ib.Type, ib.Length, ib.Breadth, ib.Height, ib.Volume, rtrim(ltrim(case when isnull(ExpPlan.DNS, 0) = 1 then '*Do Not Stack' else '' end + ' ' +  case when isnull(ExpPlan.TakePhoto, 0) = 1 then '*Take Photo' else '' end + ' ' + isnull(ExpPlan.Others,''))) SpecialInstruction, case when I.storagedate is not null then ip.CurrentLocation else ci.DeliverTo end WhseLocation from HSC2012.dbo.ContainerInfo CI inner join HSC2017.dbo.HSC_Inventory I on CI.Dummy = I.CntrID inner join HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID inner join HSC2017.dbo.HSC_TempExpPlan ExpPlan on ib.BreakDownID = ExpPlan.BreakDownIDImp where i.InventoryID = " . $request->get('InventoryID') . " and ExpPlan.CntrIDExp = " . $request->get('CntrIDExp') . " and i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and ExpPlan.DelStatus = 'N' order by IP.SequenceNo, ib.BreakDownID";
        $rawPallet = DB::connection("sqlsrv3")->select($sqlPallet);
        $i = 1;
        
        $DeliveryID = array();
        foreach ($rawPallet as $key => $value)
        {
            $loopPallet = array(
                "number" => $i++,
                "InventoryPalletID" => is_null($value->InventoryPalletID) ? "" : $value->InventoryPalletID,
                "HBL" => is_null($value->TranshipmentRef) ? "" : $value->TranshipmentRef,
                "InventoryID" => is_null($value->InventoryID) ? "" : $value->InventoryID,
                "SequenceNo" => is_null($value->SequenceNo) ? "" : $value->SequenceNo,
                "Tag" => is_null($value->Tag) ? "" : $value->Tag,
                "SpecialInstruction" => is_null($value->SpecialInstruction) ? "" : $value->SpecialInstruction,
                "Location" => is_null($value->WhseLocation) ? "" : $value->WhseLocation,
                "MQuantity" => is_null($value->MQuantity) ? 0 : $value->MQuantity,
                "MVolume" => is_null($value->MVolume) ? "" : $value->MVolume,
                "MWeight" => is_null($value->MWeight) ? "" : $value->MWeight,
            );
            array_push($pallet, $loopPallet);
            $rawBreakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')
                ->where('InventoryPalletID', $value->InventoryPalletID)
                ->where('DelStatus', 'N')
                ->orderBy('BreakDownID', 'ASC')
                ->get();
            $x = 1;
            $lastFrom = null;
            foreach ($rawBreakdown as $keyBreak => $break)
            {
                $galleries = array();
                $images = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryExpPhoto')
                    ->where('BreakDownID', $break->BreakDownID)
                    ->where('DelStatus', 'N')
                    ->get();

                foreach ($images as $gallery)
                {
                    $loadImage = str_replace("//server-db/Files/Photo", "http://192.168.14.70:9030/", $gallery->PhotoNameSystem);
                    if ($this->checkRemoteFile($loadImage))
                    {
                        list($width, $height) = getimagesize($loadImage);
                        $imageGallery = array(
                            'width' => $width,
                            'InventoryPhotoID' => $gallery->InventoryExpPhotoID,
                            'PhotoName' => $gallery->PhotoName,
                            'PhotoExt' => $gallery->PhotoExt,
                            'PhotoNameSystem' => $loadImage
                        );
                        array_push($galleries, $imageGallery);
                    }
                }

                $imagesImport   = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')->where('BreakDownID', $break->BreakDownID)->where('DelStatus', 'N')->get();
                foreach($imagesImport as $galleryImport)
                {
                    if ($galleryImport->PhotoNameSystem) {
                        // Production
                        $loadImage = str_replace("//server-db/Files/Photo", "http://192.168.14.70:9030/", $galleryImport->PhotoNameSystem);
                        $loadPath  = str_replace("//server-db/Files/Photo", "", $galleryImport->PhotoNameSystem);
                        $file      = '\\\\SERVER-DB\\Files\\Photo\\' . $loadPath;

                        if (file_exists($file))
                        {
                            if (@getimagesize($loadImage)) {
                                list($width, $height) = getimagesize($loadImage);
                                $imagegalleryImport = array(
                                    'width' => $width,
                                    'InventoryPhotoID' => $galleryImport->InventoryPhotoID,
                                    'PhotoName' => $galleryImport->PhotoName,
                                    'PhotoExt' => $galleryImport->PhotoExt,
                                    'PhotoNameSystem' => $loadImage
                                );
                                array_push($galleries, $imagegalleryImport);
                            }
                        }
                    }
                    else if($galleryImport->Photo) {
                        $image = imagecreatefromstring($galleryImport->Photo); 
                            $fileName = $galleryImport->InventoryPhotoID ."-" .$galleryImport->BreakDownID .".". $galleryImport->PhotoExt;
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
                                'InventoryPhotoID' => $galleryImport->InventoryPhotoID,
                                'PhotoName' => $galleryImport->PhotoName,
                                'PhotoExt' => $galleryImport->PhotoExt,
                                'PhotoNameSystem' => $loadImage
                            );
                            array_push($galleries, $imageGallery);
                    }
                }
                $lastFrom = $break->InventoryPalletID;

                $flag = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
                    ->where('Category', 'flagExp')
                    ->get();
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
                }
                else
                {
                    foreach ($flag as $key => $fl)
                    {
                        array_push($flagSelected, false);
                    }
                }
                // dd($break->Flags);
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
                    "Flags" => is_null($break->Flags) ? "" : $break->Flags,
                    "Flags_show" => is_null($break->Flags) ? "" : $break->Flags,
                    "FlagsSelected" => is_null($flagSelected) ? "" : $flagSelected,
                    "Weight" => is_null($break->Weight) ? "" : $break->Weight,
                    "gallery" => $galleries,
                    "SpecialInstruction" => ""
                    // "SpecialInstruction" => is_null($break->SpecialInstruction) ? "" : $break->SpecialInstruction,
                    
                );
                array_push($breakdown, $loopBreakdown);
            }
        }
        $typeChecklist = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
            ->where('Category', 'type')
            ->get();
        $flagsChecklist = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
            ->where('Category', 'flagExp')
            ->get();
        $locations = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
            ->where('Category', 'location')
            ->get();
        // dd($DeliveryID);
        $data = array(
            'status' => "success",
            'pallet' => $pallet,
            'breakdown' => $breakdown,
            'type' => $typeChecklist,
            'flags' => $flagsChecklist,
            'locations' => $locations,
        );
        return response($data);
    }

    function searchHBL(Request $request)
    {
        $str = $request->get("warehouse");
        $exp = explode(",", $str);
        $parseTag = "";
        foreach ($exp as $value)
        {
            $parseTag = $parseTag . ",'" . $value . "'";
        }
        $tagging = substr($parseTag, 1);
        $pallet = array();
        $breakdown = array();

        $selected = 0;

        $SQL = "select i.InventoryID, ExpPlan.SequenceNo, i.TranshipmentRef, i.MQuantity, i.MVolume, i.MWeight, ip.InventoryPalletID, ip.SequenceNo PltNo, ip.Tag, ib.Markings, ib.Quantity, ib.Type, ib.Length, ib.Breadth, ib.Height, ib.Volume, rtrim(ltrim(case when isnull(ExpPlan.DNS, 0) = 1 then '*Do Not Stack' else '' end + ' ' + case when isnull(ExpPlan.TakePhoto, 0) = 1 then '*Take Photo' else '' end + ' ' + isnull(ExpPlan.Others,''))) SpecialInstruction, case when I.storagedate is not null then ip.CurrentLocation else ci.DeliverTo end WhseLocation from HSC2012.dbo.ContainerInfo CI inner join HSC2017.dbo.HSC_Inventory I on CI.Dummy = I.CntrID inner join HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID inner join HSC2017.dbo.HSC_TempExpPlan ExpPlan on ib.BreakDownID = ExpPlan.BreakDownIDImp where i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and ExpPlan.DelStatus = 'N' and ExpPlan.CntrIDExp = '" . $request->get('CntrIDExp') . "' and exists (select 1 from HSC2017.dbo.HSC_InventoryPallet IP1 where IP1.Tag = '" . $request->get('TAG') . "' and IP1.InventoryID = IP.InventoryID) order by IP.SequenceNo, ib.BreakDownID";
        $rawPallet = DB::connection("sqlsrv3")->select($SQL);
        $i = 1;
        foreach ($rawPallet as $key => $value)
        {
            $loopPallet = array(
                "number" => $i++,
                "InventoryPalletID" => is_null($value->InventoryPalletID) ? "" : $value->InventoryPalletID,
                "HBL" => is_null($value->TranshipmentRef) ? "" : $value->TranshipmentRef,
                "InventoryID" => is_null($value->InventoryID) ? "" : $value->InventoryID,
                "SequenceNo" => is_null($value->SequenceNo) ? "" : $value->SequenceNo,
                "Tag" => is_null($value->Tag) ? "" : $value->Tag,
                "SpecialInstruction" => is_null($value->SpecialInstruction) ? "" : $value->SpecialInstruction,
                "Location" => "",
                "MQuantity" => is_null($value->MQuantity) ? "" : $value->MQuantity,
                "MVolume" => is_null($value->MVolume) ? "" : $value->MVolume,
                "MWeight" => is_null($value->MWeight) ? "" : $value->MWeight,
            );
            array_push($pallet, $loopPallet);
            $rawBreakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')
            ->where('InventoryPalletID', $value->InventoryPalletID)
            ->where('DelStatus', 'N')
            ->orderBy('BreakDownID', 'ASC')
            ->get();
            $x = 1;
            $typeChecklist = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
                ->where('Category', 'type')
                ->get();
            $flagsChecklist = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
                ->where('Category', 'flagExp')
                ->get();
    
            foreach ($rawBreakdown as $key => $break)
            {
                $galleries = array();
                $images = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryExpPhoto')
                    ->where('BreakDownID', $break->BreakDownID)
                    ->where('DelStatus', 'N')
                    ->get();
                // dd($images);
                foreach ($images as $gallery)
                {
                    if ($gallery->PhotoNameSystem)
                    {
                        $loadImage = str_replace("//server-db/Files/Photo", "http://192.168.14.70:9030/", $gallery->PhotoNameSystem);
                        $loadPath = str_replace("//server-db/Files/Photo", "", $gallery->PhotoNameSystem);
                        $file = '\\\\SERVER-DB\\Files\\Photo\\' . $loadPath;
                        // dd($file);
                        // if (file_exists($file)) {}
                        if (file_exists($file) && $file != "\\\\SERVER-DB\\Files\\Photo\\")
                        {
                            // dd($loadImage);
                            // dd("OK);
                            if (getimagesize($loadImage))
                            {
                                list($width, $height) = getimagesize($loadImage);
                                $imageGallery = array(
                                    'width' => $width,
                                    'InventoryPhotoID' => $gallery->InventoryExpPhotoID,
                                    'PhotoName' => $gallery->PhotoName,
                                    'PhotoExt' => $gallery->PhotoExt,
                                    'PhotoNameSystem' => $loadImage
                                );
                                array_push($galleries, $imageGallery);
                            }
                        }
                        else if ($this->checkRemoteFile($loadImage))
                        {
                            list($width, $height) = getimagesize($loadImage);
                            $imageGallery = array(
                                'width' => $width,
                                'InventoryPhotoID' => $gallery->InventoryExpPhotoID,
                                'PhotoName' => $gallery->PhotoName,
                                'PhotoExt' => $gallery->PhotoExt,
                                'PhotoNameSystem' => $loadImage
                            );
                            array_push($galleries, $imageGallery);
                        }
                    }
                    else if ($gallery->Photo)
                    {
                        $image = imagecreatefromstring($gallery->Photo);
                        $fileName = $gallery->InventoryExpPhotoID . "-" . $gallery->BreakDownID . "." . $gallery->PhotoExt;
                        $loadImage = "http://192.168.14.70:9133/temp/" . $fileName;
                        ob_start();
                        imagejpeg($image, null, 480);
                        $data = ob_get_contents();
                        ob_end_clean();
                        $fnl = "data:image/jpg;base64," . base64_encode($data);
                        list($type, $fnl) = explode(';', $fnl);
                        list(, $fnl) = explode(',', $fnl);
                        $fnl = base64_decode($fnl);
                        Storage::disk('public')->put('temp/' . $fileName, $fnl);
                        $imageGallery = array(
                            'is_base_64' => true,
                            'InventoryPhotoID' => $gallery->InventoryExpPhotoID,
                            'PhotoName' => $gallery->PhotoName,
                            'PhotoExt' => $gallery->PhotoExt,
                            'PhotoNameSystem' => $loadImage
                        );
                        array_push($galleries, $imageGallery);
                    }
                }
                $flag = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
                    ->where('Category', 'flagExp')
                    ->get();
                $flagSelected = array();
                $flagShow = array();
    
                $strExplode = array_map('trim', explode(',', $break->Flags));
                if ($break->Flags)
                {
                    foreach ($flag as $fl)
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
                    foreach ($flag as $fl)
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
                    "Flags" => is_null($break->Flags) ? "" : $break->Flags,
                    "Flags_show" => is_null($break->Flags) ? "" : $break->Flags,
                    "FlagsSelected" => is_null($flagSelected) ? "" : $flagSelected,
                    "Weight" => is_null($break->Weight) ? "" : $break->Weight,
                    "gallery" => $galleries,
                );
                array_push($breakdown, $loopBreakdown);
            }
        }
        $data = array(
            'pallet' => $pallet,
            'breakdown' => $breakdown,
            'type' => $typeChecklist,
            'flags' => $flagsChecklist,
            'selected' => (int)$selected
        );
        return response($data);
    }
    public function checkHBL(Request $request)
    {
        // dd($request);
        $str = $request->get("warehouse");
        $exp = explode(",", $str);
        $parseTag = "";
        foreach ($exp as $value)
        {
            $parseTag = $parseTag . ",'" . $value . "'";
        }
        $tagging = substr($parseTag, 1);
        
        
        $SQL = "select i.InventoryID, ExpPlan.SequenceNo, i.TranshipmentRef, i.MQuantity, i.MVolume, i.MWeight, ip.InventoryPalletID, ip.SequenceNo PltNo, ip.Tag, ib.Markings, ib.Quantity, ib.Type, ib.Length, ib.Breadth, ib.Height, ib.Volume, rtrim(ltrim(case when isnull(ExpPlan.DNS, 0) = 1 then '*Do Not Stack' else '' end + ' ' + case when isnull(ExpPlan.TakePhoto, 0) = 1 then '*Take Photo' else '' end + ' ' + isnull(ExpPlan.Others,''))) SpecialInstruction, case when I.storagedate is not null then ip.CurrentLocation else ci.DeliverTo end WhseLocation from HSC2012.dbo.ContainerInfo CI inner join HSC2017.dbo.HSC_Inventory I on CI.Dummy = I.CntrID inner join HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID inner join HSC2017.dbo.HSC_InventoryBreakdown IB on IP.InventoryPalletID = IB.InventoryPalletID inner join HSC2017.dbo.HSC_TempExpPlan ExpPlan on ib.BreakDownID = ExpPlan.BreakDownIDImp where i.DelStatus = 'N' and ip.DelStatus = 'N' and ib.DelStatus = 'N' and ExpPlan.DelStatus = 'N' and ExpPlan.CntrIDExp = '" . $request->get('CntrIDExp') . "' and exists (select 1 from HSC2017.dbo.HSC_InventoryPallet IP1 where IP1.Tag = '" . $request->get('TAG') . "' and IP1.InventoryID = IP.InventoryID) order by IP.SequenceNo, ib.BreakDownID";
        $list = DB::connection("sqlsrv3")->select($SQL);

        $data = array(
            'is_exist' => count($list) > 0 ? true : false,
            'data' => $list
        );
        return response($data);
    }
    function updateBreakdownLBH(Request $request)
    {
        $getBreakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->get();

        $markings = is_null($request->post('Markings')) ? '" "' : '"' . trim($request->post('Markings')) .'"';
        $type = '"' . trim($request->post('T')) .'"';
        $qty      = (int) $request->post('Qty') ? $request->post('Qty') : 0;
        $length   = (int) $request->post('L') ? $request->post('L') : 0;
        $breadth  = (int) $request->post('B') ? $request->post('B') : 0;
        $height   = (int) $request->post('H') ? $request->post('H') : 0;
        $volume   = sprintf("%.3f", ($qty * $length * $breadth * $height) / 1000000);
        $remarks  = is_null($request->post('R')) ? '" "' : '"' . $request->post('R') .'"';
        $flags    = is_null($getBreakdown[0]->Flags) ? '" "' : '"' . $getBreakdown[0]->Flags .'"';
        $UpdatedBy = '"' . $request->get('UpdatedBy') .'"';
        $parse = DB::connection("sqlsrv3")->statement("SET NOCOUNT ON;SET ARITHABORT ON;SET QUOTED_IDENTIFIER OFF;SET ANSI_NULLS ON;exec HSC2017.dbo.InventoryBreakdown_InsertUpdate " . $request->post('BreakDownID') . ", " . '"-"'. ", " . $markings . ", " . $qty . ", " . $type . ", " . $length . ", " . $breadth . ", " . $height . ", " . $volume . ", " . $remarks . ", " . $UpdatedBy . ", " . $flags . "");

        $data = array(
            'status' => "success",
            'volume' => sprintf("%.3f", ($qty * $length * $breadth * $height) / 1000000)
        );
        return response($data);
    }
    public function updateBreakdown(Request $request)
    {
        if($request->post('type') == "Flags")
        {
            $getBreakdown = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->get();
            if (empty($getBreakdown[0]->Flags)) {
                $flags = $getBreakdown[0]->Flags;
                $cleanFlag = array();
                array_push($cleanFlag, ltrim($request->post('data')));
            }else{
                $firstFlag      = explode(",", $getBreakdown[0]->Flags);
                $stuffingFlag   = array('SHUT OUT', 'MISSING TAG');
                $cleanFlag      = array_diff(array_map('trim', $firstFlag), $stuffingFlag); 
                array_push($cleanFlag, ltrim($request->post('data')));
            }
            $flagFilter = array_filter(array_unique(array_map('trim', $cleanFlag)));
            // Log::debug("MYFLAGcleanFlag", $flagFilter);
            DB::connection("sqlsrv3")->table('HSC_IPS.dbo.InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->update(array(
                $request->post('type') => implode(", ", $flagFilter),
                'UpdatedDt' => date("Y-m-d H:i:s"),
                'UpdatedBy' => $request->get('UpdatedBy')
            ));
            DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')->where('BreakDownID', $request->post('BreakDownID'))->update(array(
                $request->post('type') => implode(", ", $flagFilter),
                'UpdatedDt' => date("Y-m-d H:i:s"),
                'UpdatedBy' => $request->get('UpdatedBy')
            ));
        }
        else
        {
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
        }
        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    public function checkInventory(Request $request)
    {
        $SQL = "select COUNT(*) CountTag
        from HSC2012.dbo.ContainerInfo CII, HSC2017.dbo.HSC_Inventory I,
                HSC2017.dbo.HSC_InventoryPallet IP, HSC2017.dbo.HSC_InventoryBreakdown IB,
                HSC2017.dbo.HSC_TempExpPlan ExpPan
        where CII.Dummy = I.CntrID
        and I.InventoryID = IP.InventoryID
        and IP.InventoryPalletID = IB.InventoryPalletID
        and IB.BreakDownID = ExpPan.BreakDownIDImp
        and IP.InventoryID = " . $request->get('InventoryID') . "
        and ExpPan.CntrIDExp = " . $request->get('CntrIDExp') . "
        and IP.DelStatus = 'N'
        and IB.DelStatus = 'N'
        and ExpPan.DelStatus = 'N'
        and ((IP.Tag <> '' and CHARINDEX('missing tag', isnull(IB.Flags,'')) <= 0 and CHARINDEX('shut out', isnull(IB.Flags,'')) <= 0) OR
             (CII.[DateofStuf/Unstuf] is null and I.StorageDate is null and CHARINDEX('shut out', isnull(IB.Flags,'')) <= 0 and isnull(I.ToDGWhse,0) = 0))";
        $parse = DB::connection("sqlsrv3")->select($SQL);
      
        if ($parse[0]->CountTag == 0)
        {
          DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
              'CheckStatusStuffing' => 'Y'
          ));
        }
        else if ($parse[0]->CountTag >= 1)
        {
          DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $request->get('InventoryID'))->update(array(
              'CheckStatusStuffing' => 'N'
          ));
        }
        $data = array(
            'status' => "success"
        );
        return response($data);
    }

    function checkInventoryInternal($inventoryID, $CntrIDExp)
    {
        $SQL = "select COUNT(*) CountTag
        from HSC2012.dbo.ContainerInfo CII, HSC2017.dbo.HSC_Inventory I,
                HSC2017.dbo.HSC_InventoryPallet IP, HSC2017.dbo.HSC_InventoryBreakdown IB,
                HSC2017.dbo.HSC_TempExpPlan ExpPan
        where CII.Dummy = I.CntrID
        and I.InventoryID = IP.InventoryID
        and IP.InventoryPalletID = IB.InventoryPalletID
        and IB.BreakDownID = ExpPan.BreakDownIDImp
        and IP.InventoryID = " . $inventoryID . "
        and ExpPan.CntrIDExp = " . $CntrIDExp . "
        and IP.DelStatus = 'N'
        and IB.DelStatus = 'N'
        and ExpPan.DelStatus = 'N'
        and ((IP.Tag <> '' and CHARINDEX('missing tag', isnull(IB.Flags,'')) <= 0 and CHARINDEX('shut out', isnull(IB.Flags,'')) <= 0) OR
             (CII.[DateofStuf/Unstuf] is null and I.StorageDate is null and CHARINDEX('shut out', isnull(IB.Flags,'')) <= 0 and isnull(I.ToDGWhse,0) = 0))";
        $parse = DB::connection("sqlsrv3")->select($SQL);
      
        if ($parse[0]->CountTag == 0)
        {
          DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $inventoryID)->update(array(
              'CheckStatusStuffing' => 'Y'
          ));
        }
        else if ($parse[0]->CountTag >= 1)
        {
          DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')->where('InventoryID', $inventoryID)->update(array(
              'CheckStatusStuffing' => 'N'
          ));
        }
        return true;
    }
    function unTick(Request $request)
    {
        Log::debug('DEBUG QUERY -  UNTAG ' . $request->get('InventoryPalletID'));
        DB::connection("sqlsrv3")->table('HSC_IPS.dbo.InventoryPallet')
            ->where('InventoryPalletID', $request->get('InventoryPalletID'))
            ->update(array(
            'Tag' => null
        ));
        DB::connection("sqlsrv3")
            ->table('HSC2017.dbo.HSC_InventoryPallet')
            ->where('InventoryPalletID', $request->get('InventoryPalletID'))
            ->update(array(
            'Tag' => null
        ));
        if ($request->get('InventoryID')) {
            $this->checkInventoryInternal($request->get('InventoryID'), $request->get('CntrID'));
        }
        $data = array(
            'status' => true
        );
        return response($data);
    }
    function uploadBreakdownGallery(Request $request)
    {
        $maxId = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryExpPhoto')->max('InventoryExpPhotoID');
        $maxOrdering = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryExpPhoto')->where('BreakDownID', $request->post('BreakDownID'))->max('Ordering');
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
        $id = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryExpPhoto')->insertGetId($dataImg);
        
        Log::debug('DEBUG QUERY -  UPDATE ' . $cntr->CntrID);
        unlink(public_path('image/breakdown/' .$finalName));
        $data = array(
            'status' => 'success',
            'last_photo' => array(
              'InventoryPhotoID' => $id,
              'PhotoNameSystem' => $cntr->CntrID  . "/" . $finalName
            )
        );
        return response($data);
    }
    function deleteBreakdownPhoto(Request $request)
    {
        DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryExpPhoto')->where('InventoryExpPhotoID', $request->post('InventoryPhotoID'))->update(array(
            'DelStatus' => 'Y',
            'ModifyDt' => date("Y-m-d H:i:s"),
            'ModifyBy' => $request->get('UpdatedBy'),
        ));

        $data = array(
            'status' => "success"
        );
        return response($data);
    }
    function checkRemoteFile($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        // don't download content
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        curl_close($ch);
        if($result !== FALSE)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}

