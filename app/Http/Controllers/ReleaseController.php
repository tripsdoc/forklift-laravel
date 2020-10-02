<?php
namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManagerStatic as Image;
date_default_timezone_set("Asia/Singapore");
class ReleaseController extends Controller
{
    function getClient()
    {
        $client = DB::connection("sqlsrv3")->table('HSC2012.dbo.ClientInfo')
            ->select('ClientID')
            ->Where("Discontinued", 0)
            ->get();
        $data = array(
            'data' => $client
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

        $selected = 0;

        $SQL = "select CntrInfo.Dummy, CntrInfo.ContainerPrefix, CntrInfo.ContainerNumber, CntrInfo.ContainerSize, CntrInfo.ContainerType, CntrInfo.ClientID,
        CntrInfo.SequenceNo, CntrInfo.Ref, CntrInfo.InvStatus, CntrInfo.POD, CntrInfo.StorageDate,
        CntrInfo.ExpCntrPrefix, CntrInfo.ExpCntrNo, CntrInfo.TotalPlt, CntrInfo.TotalTag, ip1.SequenceNo PltNo,
        IB1.Markings, IB1.Quantity, IB1.Type, ib1.Length, IB1.Breadth, IB1.Height, ib1.Volume, IP1.Tag, IP1.InventoryPalletID,
        IB1.BreakDownID, CntrInfo.WhseLoc, rtrim(ltrim(ISNULL(IB1.Remarks, '') + ' ' + ISNULL(IB1.Flags, ''))) Remarks,
        IP1.DeliveryID, TLL.CoordinateSystemName
 from
 (
 select CI.Dummy, CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, JI.ClientID,
        min(I.SequenceNo) SequenceNo,
        case when i.StorageDate is not null or isnull(I.TranshipmentRef, '') <> '' then I.TranshipmentRef else I.HBL end Ref,
        case when i.StorageDate is not null then 'Export' when isnull(I.TranshipmentRef, '') <> '' then 'Transhipment' else 'Local' end InvStatus,
        case when i.StorageDate is not null then I.StorageDate else CI.[DateofStuf/Unstuf] end StorageDate,
        I.POD, CIExp.ContainerPrefix ExpCntrPrefix, CIExp.ContainerNumber ExpCntrNo, I.InventoryID,
        --count(distinct IP.InventoryPalletID) TotalPlt,
        --count(distinct case when IP.Tag = '' then null else IP.Tag end) TotalTag,
        count(IP.InventoryPalletID) TotalPlt,
        count(case when IP.Tag = '' then null else IP.Tag end) TotalTag,
        case when i.StorageDate is not null then IP.CurrentLocation else CI.DeliverTo end WhseLoc
 from HSC2012.dbo.JobInfo JI inner join
      HSC2012.dbo.ContainerInfo CI on JI.JobNumber = CI.JobNumber inner join
         HSC2017.dbo.HSC_Inventory I on CI.Dummy = I.CntrID inner join
         HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID left join
         HSC2012.dbo.ContainerInfo CIExp on IP.ExpCntrID = CIExp.Dummy
 where I.DelStatus = 'N'
     and IP.DelStatus = 'N'
     --and isnull(IP.Tag, '') <> ''
     --and (IP.ExpCntrID > 0 or IP.DeliveryID > 0)
      and (CI.DeliverTo IN (" . $tagging . ") OR IP.CurrentLocation IN (" . $tagging . ")) ";

        if ($request->get('ClientID') || $request->get('HBL'))
        {
            $SQL .= " and ((JI.ClientID = '" . $request->get('ClientID') . "' and (I.HBL = '" . $request->get('HBL') . "' OR I.TranshipmentRef = '" . $request->get('HBL') . "')))";
        }
        else
        {
            $SQL .= " and (IP.Tag = '" . $request->get('TAG') . "' AND IP.Tag <> '')";
        }

        $SQL .= " group by CI.Dummy, CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, JI.ClientID,
      case when i.StorageDate is not null or isnull(I.TranshipmentRef, '') <> '' then I.TranshipmentRef else I.HBL end,
      case when i.StorageDate is not null then 'Export' when isnull(I.TranshipmentRef, '') <> '' then 'Transhipment' else 'Local' end,
      case when i.StorageDate is not null then I.StorageDate else CI.[DateofStuf/Unstuf] end,
      I.POD, CI.[DateofStuf/Unstuf], CIExp.ContainerPrefix, CIExp.ContainerNumber, I.InventoryID,
      case when i.StorageDate is not null then IP.CurrentLocation else CI.DeliverTo end
) CntrInfo inner join
 HSC2017.dbo.HSC_InventoryPallet IP1 on CntrInfo.InventoryID = ip1.InventoryID inner join
 HSC2017.dbo.HSC_InventoryBreakdown IB1 on ip1.InventoryPalletID = ib1.InventoryPalletID left join
 HSC_IPS.dbo.TagLocationLatest TLL on IP1.Tag = TLL.Id
where ip1.DelStatus = 'N'
 and ib1.DelStatus = 'N' ORDER BY InventoryPalletID ASC";

        $rawPallet = DB::connection("sqlsrv3")->select($SQL);

        $x = 1;
        foreach ($rawPallet as $key => $value)
        {

            if ($value->Tag == $request->get('TAG'))
            {
                $selected = $key;
            }
            if ($value->CoordinateSystemName)
            {
                $exp = explode("-", $value->CoordinateSystemName);
                if (strpos($value->CoordinateSystemName, "107"))
                {
                    $zone = "107";
                }
                elseif (strpos($value->CoordinateSystemName, "108"))
                {
                    $zone = "108,109,110";
                }
            }
            else
            {
                $zone = false;
            }

            $loopPallet = array(
                "number" => $x++,
                "Dummy" => $value->Dummy ? $value->Dummy : "",
                "ContainerPrefix" => $value->ContainerPrefix ? $value->ContainerPrefix : "",
                "ContainerNumber" => $value->ContainerNumber ? $value->ContainerNumber : "",
                "ContainerSize" => $value->ContainerSize ? $value->ContainerSize : "",
                "ContainerType" => $value->ContainerType ? $value->ContainerType : "",
                "ClientID" => $value->ClientID ? $value->ClientID : "",
                "SequenceNo" => $value->SequenceNo ? $value->SequenceNo : "",
                "Ref" => $value->Ref ? $value->Ref : "",
                "InvStatus" => $value->InvStatus ? $value->InvStatus : "",
                "POD" => $value->POD ? $value->POD : "",
                "StorageDate" => $value->StorageDate ? date("d-m-Y H:i", strtotime($value->StorageDate)) . " HR" : "",
                "ExpCntrPrefix" => $value->ExpCntrPrefix ? $value->ExpCntrPrefix : "",
                "ExpCntrNo" => $value->ExpCntrNo ? $value->ExpCntrNo : "",
                "TotalPlt" => $value->TotalPlt ? $value->TotalPlt : "",
                "TotalTag" => $value->TotalTag ? $value->TotalTag : "",
                "PltNo" => $value->PltNo ? $value->PltNo : "",
                "Tag" => $value->Tag ? $value->Tag : "",
                "InventoryPalletID" => $value->InventoryPalletID ? $value->InventoryPalletID : "",
                "WhseLoc" => $zone ? $zone : "",
                "WhseLocPallet" => $value->WhseLoc ? $value->WhseLoc : "",
                "DeliveryID" => $value->DeliveryID ? $value->DeliveryID : ""
            );
            $ids = array_map(function ($pallet)
            {
                return $pallet['InventoryPalletID'];
            }
            , $pallet);
            if (!in_array($value->InventoryPalletID, $ids))
            {
                array_push($pallet, $loopPallet);
            }
        }

        $sqlBreakdown = "select CntrInfo.ContainerPrefix, CntrInfo.ContainerNumber, CntrInfo.ContainerSize, CntrInfo.ContainerType, CntrInfo.ClientID,
        CntrInfo.SequenceNo, CntrInfo.Ref, CntrInfo.InvStatus, CntrInfo.POD, CntrInfo.StorageDate,
        CntrInfo.ExpCntrPrefix, CntrInfo.ExpCntrNo, CntrInfo.TotalPlt, CntrInfo.TotalTag, ip1.SequenceNo PltNo,
        IB1.Markings, IB1.Quantity,IB1.Flags, IB1.Type, ib1.Length, IB1.Breadth, IB1.Height, ib1.Volume, IP1.Tag, IP1.InventoryPalletID,
        IB1.BreakDownID, IB1.Remarks, CntrInfo.WhseLoc, rtrim(ltrim(ISNULL(IB1.Remarks, ''))) Remarks, IP1.DeliveryID from(select CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, JI.ClientID,
        min(I.SequenceNo) SequenceNo,
case when i.StorageDate is not null or isnull(I.TranshipmentRef, '') <> '' then I.TranshipmentRef else I.HBL end Ref,
   	   case when i.StorageDate is not null then 'Export' when isnull(I.TranshipmentRef, '') <> '' then 'Transhipment' else 'Local' end InvStatus,
        case when i.StorageDate is not null then I.StorageDate else CI.[DateofStuf/Unstuf] end StorageDate,
        I.POD, CIExp.ContainerPrefix ExpCntrPrefix, CIExp.ContainerNumber ExpCntrNo, I.InventoryID,
        count(IP.InventoryPalletID) TotalPlt,
        count(case when IP.Tag = '' then null else IP.Tag end) TotalTag,
        case when i.StorageDate is not null then IP.CurrentLocation else CI.DeliverTo end WhseLoc
 from HSC2012.dbo.JobInfo JI inner join
      HSC2012.dbo.ContainerInfo CI on JI.JobNumber = CI.JobNumber inner join
         HSC2017.dbo.HSC_Inventory I on CI.Dummy = I.CntrID inner join
         HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID left join
         HSC2012.dbo.ContainerInfo CIExp on IP.ExpCntrID = CIExp.Dummy
 where I.DelStatus = 'N'
     and IP.DelStatus = 'N'
     --and isnull(IP.Tag, '') <> ''
     --and (IP.ExpCntrID > 0 or IP.DeliveryID > 0)
     and (CI.DeliverTo IN (" . $tagging . ") OR IP.CurrentLocation IN (" . $tagging . "))";
        if ($request->get('ClientID') || $request->get('HBL'))
        {
            $sqlBreakdown .= " and ((JI.ClientID = '" . $request->get('ClientID') . "' and (I.HBL = '" . $request->get('HBL') . "' OR I.TranshipmentRef = '" . $request->get('HBL') . "')))";
        }
        else
        {
            $sqlBreakdown .= " and (IP.Tag = '" . $request->get('TAG') . "' AND IP.Tag <> '')";
        }

        $sqlBreakdown .= " group by CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, JI.ClientID,
        case when i.StorageDate is not null or isnull(I.TranshipmentRef, '') <> '' then I.TranshipmentRef else I.HBL end,
       case when i.StorageDate is not null then 'Export' when isnull(I.TranshipmentRef, '') <> '' then 'Transhipment' else 'Local' end,
               case when i.StorageDate is not null then I.StorageDate else CI.[DateofStuf/Unstuf] end,
               I.POD, CI.[DateofStuf/Unstuf], CIExp.ContainerPrefix, CIExp.ContainerNumber, I.InventoryID,
               case when i.StorageDate is not null then IP.CurrentLocation else CI.DeliverTo end
        ) CntrInfo, HSC2017.dbo.HSC_InventoryPallet IP1, HSC2017.dbo.HSC_InventoryBreakdown IB1
        where CntrInfo.InventoryID = ip1.InventoryID
          and ip1.InventoryPalletID = ib1.InventoryPalletID
          and ip1.DelStatus = 'N'
          and ib1.DelStatus = 'N'";
        $rawBreakdown = DB::connection("sqlsrv3")->select($sqlBreakdown);
        $breakdown = array();
        $i = 1;
        $typeChecklist = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
            ->where('Category', 'type')
            ->get();
        $flagsChecklist = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
            ->where('Category', 'flag')
            ->get();

        foreach ($rawBreakdown as $key => $value)
        {
            $galleries = array();
            $images = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')
                ->where('BreakDownID', $value->BreakDownID)
                ->where('DelStatus', 'N')
                ->get();

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
                                'InventoryPhotoID' => $gallery->InventoryPhotoID,
                                'PhotoName' => $gallery->PhotoName,
                                'PhotoExt' => $gallery->PhotoExt,
                                'PhotoNameSystem' => $loadImage
                            );
                            array_push($galleries, $imageGallery);
                        }
                    }
                }
                else if ($gallery->Photo)
                {
                    $image = imagecreatefromstring($gallery->Photo);
                    $fileName = $gallery->InventoryPhotoID . "-" . $gallery->BreakDownID . "." . $gallery->PhotoExt;
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
                        'InventoryPhotoID' => $gallery->InventoryPhotoID,
                        'PhotoName' => $gallery->PhotoName,
                        'PhotoExt' => $gallery->PhotoExt,
                        'PhotoNameSystem' => $loadImage
                    );
                    array_push($galleries, $imageGallery);
                }
            }
            $flag = DB::connection("sqlsrv3")->table('HSC2017.dbo.Checklist')
                ->where('Category', 'flag')
                ->get();
            $flagSelected = array();
            $flagShow = array();

            $strExplode = array_map('trim', explode(',', $value->Flags));
            if ($value->Flags)
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
                "number" => $i++,
                "ContainerPrefix" => $value->ContainerPrefix ? $value->ContainerPrefix : "-",
                "ContainerNumber" => $value->ContainerNumber ? $value->ContainerNumber : "",
                "ContainerSize" => $value->ContainerSize ? $value->ContainerSize : "",
                "ContainerType" => $value->ContainerType ? $value->ContainerType : "",
                "ClientID" => $value->ClientID ? $value->ClientID : "",
                "DeliveryID" => $value->DeliveryID ? $value->DeliveryID : "",
                "SequenceNo" => $value->SequenceNo ? $value->SequenceNo : "",
                "InvStatus" => $value->InvStatus ? $value->InvStatus : "",
                "Ref" => $value->Ref ? $value->Ref : "",
                "WhseLoc" => $value->WhseLoc ? $value->WhseLoc : "",
                "POD" => $value->POD ? $value->POD : "",
                "StorageDate" => date("d-m-Y H:i", strtotime($value->StorageDate)) ,
                "ExpCntrPrefix" => $value->ExpCntrPrefix ? $value->ExpCntrPrefix : "",
                "ExpCntrNo" => $value->ExpCntrNo ? $value->ExpCntrNo : "",
                "TotalPlt" => $value->TotalPlt ? $value->TotalPlt : "",
                "TotalTag" => $value->TotalTag ? $value->TotalTag : "",
                "PltNo" => $value->PltNo ? $value->PltNo : "",
                "Markings" => $value->Markings ? $value->Markings : "",
                "Quantity" => $value->Quantity ? $value->Quantity : "",
                "Type" => $value->Type ? $value->Type : "",
                "Length" => $value->Length ? $value->Length : "",
                "Breadth" => $value->Breadth ? $value->Breadth : "",
                "Height" => $value->Height ? $value->Height : "",
                "Volume" => $value->Volume ? $value->Volume : "",
                "Tag" => $value->Tag ? $value->Tag : "",
                "Remarks" => $value->Remarks ? $value->Remarks : "",
                "InventoryPalletID" => $value->InventoryPalletID ? $value->InventoryPalletID : "",
                "BreakdownID" => $value->BreakDownID ? $value->BreakDownID : "",
                "Flags_show" => is_null($flagShow) ? "" : implode(", ", $flagShow) ,
                "FlagsSelected" => is_null($flagSelected) ? "" : $flagSelected,
                "gallery" => $galleries
            );
            array_push($breakdown, $loopBreakdown);
        }

        $data = array(
            'pallet' => $pallet,
            'breakdown' => $breakdown,
            'type' => $typeChecklist,
            'flags' => $flagsChecklist,
            'selected' => (int)$selected
        );
        // dd($data);
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
        $SQL = "select CntrInfo.Dummy, CntrInfo.ContainerPrefix, CntrInfo.ContainerNumber, CntrInfo.ContainerSize, CntrInfo.ContainerType, CntrInfo.ClientID,
        CntrInfo.SequenceNo, CntrInfo.Ref, CntrInfo.InvStatus, CntrInfo.POD, CntrInfo.StorageDate,
        CntrInfo.ExpCntrPrefix, CntrInfo.ExpCntrNo, CntrInfo.TotalPlt, CntrInfo.TotalTag, ip1.SequenceNo PltNo,
        IB1.Markings, IB1.Quantity, IB1.Type, ib1.Length, IB1.Breadth, IB1.Height, ib1.Volume, IP1.Tag, IP1.InventoryPalletID,
        IB1.BreakDownID, CntrInfo.WhseLoc, rtrim(ltrim(ISNULL(IB1.Remarks, '') + ' ' + ISNULL(IB1.Flags, ''))) Remarks,
        IP1.DeliveryID, TLL.CoordinateSystemName
 from
 (
 select CI.Dummy, CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, JI.ClientID,
        min(I.SequenceNo) SequenceNo,
        case when i.StorageDate is not null or isnull(I.TranshipmentRef, '') <> '' then I.TranshipmentRef else I.HBL end Ref,
        case when i.StorageDate is not null then 'Export' when isnull(I.TranshipmentRef, '') <> '' then 'Transhipment' else 'Local' end InvStatus,
        case when i.StorageDate is not null then I.StorageDate else CI.[DateofStuf/Unstuf] end StorageDate,
        I.POD, CIExp.ContainerPrefix ExpCntrPrefix, CIExp.ContainerNumber ExpCntrNo, I.InventoryID,
        --count(distinct IP.InventoryPalletID) TotalPlt,
        --count(distinct case when IP.Tag = '' then null else IP.Tag end) TotalTag,
        count(IP.InventoryPalletID) TotalPlt,
        count(case when IP.Tag = '' then null else IP.Tag end) TotalTag,
        case when i.StorageDate is not null then IP.CurrentLocation else CI.DeliverTo end WhseLoc
 from HSC2012.dbo.JobInfo JI inner join
      HSC2012.dbo.ContainerInfo CI on JI.JobNumber = CI.JobNumber inner join
         HSC2017.dbo.HSC_Inventory I on CI.Dummy = I.CntrID inner join
         HSC2017.dbo.HSC_InventoryPallet IP on I.InventoryID = IP.InventoryID left join
         HSC2012.dbo.ContainerInfo CIExp on IP.ExpCntrID = CIExp.Dummy
 where I.DelStatus = 'N'
     and IP.DelStatus = 'N'
     --and isnull(IP.Tag, '') <> ''
     --and (IP.ExpCntrID > 0 or IP.DeliveryID > 0)
      and (CI.DeliverTo IN (" . $tagging . ") OR IP.CurrentLocation IN (" . $tagging . ")) ";

        if ($request->get('ClientID') || $request->get('HBL'))
        {
            $SQL .= " and ((JI.ClientID = '" . $request->get('ClientID') . "' and (I.HBL = '" . $request->get('HBL') . "' OR I.TranshipmentRef = '" . $request->get('HBL') . "')))";
        }
        else
        {
            $SQL .= " and (IP.Tag = '" . $request->get('TAG') . "' AND IP.Tag <> '')";
        }

        $SQL .= " group by CI.Dummy, CI.ContainerPrefix, CI.ContainerNumber, CI.ContainerSize, CI.ContainerType, JI.ClientID,
      case when i.StorageDate is not null or isnull(I.TranshipmentRef, '') <> '' then I.TranshipmentRef else I.HBL end,
      case when i.StorageDate is not null then 'Export' when isnull(I.TranshipmentRef, '') <> '' then 'Transhipment' else 'Local' end,
      case when i.StorageDate is not null then I.StorageDate else CI.[DateofStuf/Unstuf] end,
      I.POD, CI.[DateofStuf/Unstuf], CIExp.ContainerPrefix, CIExp.ContainerNumber, I.InventoryID,
      case when i.StorageDate is not null then IP.CurrentLocation else CI.DeliverTo end
) CntrInfo inner join
 HSC2017.dbo.HSC_InventoryPallet IP1 on CntrInfo.InventoryID = ip1.InventoryID inner join
 HSC2017.dbo.HSC_InventoryBreakdown IB1 on ip1.InventoryPalletID = ib1.InventoryPalletID left join
 HSC_IPS.dbo.TagLocationLatest TLL on IP1.Tag = TLL.Id
where ip1.DelStatus = 'N'
 and ib1.DelStatus = 'N' ORDER BY InventoryPalletID ASC";

        $list = DB::connection("sqlsrv3")->select($SQL);
        $data = array(
            'is_exist' => count($list) > 0 ? true : false
        );
        return response($data);
    }
    function uploadBreakdownGallery(Request $request)
    {
        $maxId = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')
            ->max('InventoryPhotoID');
        $maxOrdering = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')
            ->where('BreakDownID', $request->post('BreakDownID'))
            ->max('Ordering');
        // getContainerID
        $palletID = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryBreakdown')
            ->where('BreakDownID', $request->post('BreakDownID'))
            ->first();
        $inventoryID = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPallet')
            ->where('InventoryPalletID', $palletID->InventoryPalletID)
            ->first();
        $cntr = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_Inventory')
            ->where('InventoryID', $inventoryID->InventoryID)
            ->first();
        $cover = $request->file('image');
        $image = $cover->getClientOriginalName();
        $filename = pathinfo($image, PATHINFO_FILENAME);
        $extension = pathinfo($image, PATHINFO_EXTENSION);
        // $finalName = $filename . '_' . time() . '.' . $extension;
        $finalName = 'Sendinphoto_' . ($maxId + 1) . '.' . $extension;
        $finalNameDB = 'Sendinphoto_' . ($maxId + 1);

        // temp folder
        Storage::disk('public')->put('temp/' . $finalName, File::get($cover));

        $imageFix = public_path() . '/temp/' . $finalName;
        $dir = '\\\\SERVER-DB\\Files\\Photo\\';
        $year = date("Y");
        $month = date("m");

        if (is_dir($dir))
        {
            if (!file_exists($dir . $request->get('CntrID')))
            {
                mkdir($dir . $request->get('CntrID') , 0775);
            }
            if ($dh = opendir($dir))
            {
                $uold = umask(0);
                $filename = $dir . $request->get('CntrID') . "/" . $year;
                $filename2 = $dir . $request->get('CntrID') . "/" . $year . "/" . $month;
                if (file_exists($filename))
                {
                    if (!file_exists($filename2))
                    {
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
        }

        // if (is_dir($dir))
        // {
        //     if (!file_exists($dir . $cntr->CntrID))
        //     {
        //       mkdir($dir . $cntr->CntrID, 0775);
        //     }
        // }
        list($width, $height) = getimagesize($imageFix);
        if ($width > $height)
        {
            $source = imagecreatefromjpeg($imageFix);

            $rotate = imagerotate($source, 90, 0);
            $image_resize = Image::make($rotate);
            $image_resize->resize(640, 480);
            $image_resize->text(date("d/m/Y H:i") , 500, 400, function ($font)
            {
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
            $image_resize->text(date("d/m/Y H:i") , 350, 620, function ($font)
            {
                $font->file(public_path() . '/fonts/RobotoCondensed-Bold.ttf');
                $font->size(20);
                $font->color('#FFFF00');
                $font->align('center');
                $font->valign('bottom');
            });
            $image_resize->save(public_path('image/breakdown/' . $finalName));
        }
        // copy(public_path('image/breakdown/' . $finalName), $dir . $cntr->CntrID . '/' . $finalName);
        copy(public_path('image/breakdown/' . $finalName) , $dir . $cntr->CntrID . "/" . $year . "/" . $month . '/' . $finalName);
        $dataImg = array(
            'BreakDownID' => $request->post('BreakDownID') ,
            'PhotoName' => $finalNameDB,
            'PhotoExt' => "." . $extension,
            'CreatedDt' => date("Y-m-d H:i:s") ,
            'CreatedBy' => $request->get('CreatedBy') ,
            'ModifyDt' => null,
            'ModifyBy' => '',
            'DelStatus' => 'N',
            'Ordering' => $maxOrdering + 1,
            'Emailed' => 1,
            'PhotoNameSystem' => "//server-db/Files/Photo/" . $cntr->CntrID . "/" . $year . "/" . $month . '/' . $finalName
        );
        $id = DB::connection("sqlsrv3")->table('HSC2017.dbo.HSC_InventoryPhoto')
            ->insertGetId($dataImg);
        unlink(public_path('image/breakdown/' . $finalName));
        $data = array(
            'status' => 'success',
            'last_photo' => array(
                'InventoryPhotoID' => $id,
                'PhotoNameSystem' => $year . "/" . $month . '/' . $finalName
            ) ,
            'CntrID' => $cntr->CntrID
        );
        return response($data);
    }
    function unTick(Request $request)
    {
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
        $data = array(
            'status' => true
        );
        return response($data);
    }
}

