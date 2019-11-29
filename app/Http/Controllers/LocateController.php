<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;

class LocateController extends Controller
{
    function getContainerList() {
      $container =  DB::select("SELECT CI.[Dummy], JI.[ClientID], JI.[POD], CI.[ContainerPrefix], CI.[ContainerNumber], CI.[ContainerSize], CI.[ContainerType], CI.[Status], VI.[ETA], CI.[DeliverTo] FROM VesselInfo VI, JobInfo JI, ContainerInfo CI WHERE VI.VesselID = JI.VesselID AND JI.JobNumber = CI.JobNumber AND JI.[Import/Export] = 'Export' AND CI.[DateofStuf/Unstuf] IS NULL AND CI.StartTime IS NULL AND CI.DeliverTo IN ('110','108','109') AND EXISTS (SELECT 1 FROM HSC_InventoryPallet IP WHERE IP.ExpCntrID = CI.[Dummy] AND IP.DelStatus = 'N')");
      $data = array(
        'status' => 'success',
        'container' => $container
      );
      return response($data);
    }
}
