<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;

class ReceivingController extends Controller
{
    function getSummary(Request $request)
    {
        $list  = DB::select("SELECT DISTINCT JI.ClientID FROM JobInfo JI, ContainerInfo CI, HSC_Inventory I, HSC_InventoryPallet IP, HSC_InventoryBreakdown IB WHERE JI.JobNumber = CI.JobNumber AND CI.Dummy = I.CntrID AND I.InventoryID = IP.InventoryID AND IP.InventoryPalletID = IB.InventoryPalletID AND DATEDIFF(DAY, StorageDate, GETDATE()) = 0 AND I.DelStatus = 'N' AND IP.DelStatus = 'N' AND IB.DelStatus = 'N' ORDER BY JI.ClientID");
        $data    = array(
            'data' => $list
        );
        return response($data);
    }
}
