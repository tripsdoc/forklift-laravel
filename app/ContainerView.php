<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContainerView extends Model
{
    protected $table = "HSC2012.dbo.Onee";
    protected $primaryKey = "Dummy";
    public $timestamps = false;

    /*
    There will be 2 Process
    - Import
        -Driver will bring container from outside to KD, then driver will inform shifter and ask where to park the container -> Show container
        (In this process will select the shifter by random, only select available shifter)
        -Shifter will inform the driver the name of the parking lot
        -Driver process to park the container based on shifter information
        (System will track container info to currently asigned shifter)
    - Export
        -Driver will ask where the container to shifter
        (In this process the system will check who is the one that bring the container to the warehouse)
        -Shifter inform the driver the information of parking lot where the container is
        -Driver bring container outside of KD
    */

    /*
    Part 1 
    Show summary and overview of the job, will show most data from onee view

    Part 2 (Parking)
    Show list of available container

    Container from outside to KD
    -Driver select container
    -Driver click button inform shifter -- Button disabled, change text "Requesting"
    -System choice shifter to inform
    -Shifter receive container information (Shown as dialog with list of selectable park)
    -Shifter select the park -- Show parking lots in driver overview, change button text "Finish Parking - Bay 1 (107)
    -System tell the driver the information from shifter
    -Driver continue to park in parking lots

    Container from KD to outside
    -Driver select container
    -Driver click button request container -- Button disabled, change text "Requesting"
    -System tell shifter that handle the container
    -Shifter will receive dialog ("Allow this driver to receive container info?")
    -Shifter click allow
    --System return information with park lots location to driver
    --Driver go to parking lots
    -Shifter click deny
    --System return denied status
    --Driver will shown a dialog ("Cannot receive the container data because shifter not allowed")
    */

    /*
    I/E-
    ETA-
    CLIENT-
    PREFIX NUMBER-
    SEAL-
    SIZE TYPE-
    LD/POD
    REMARKS
    STATUS

    SELECT 
    VI.VesselID, VI.VesselName, VI.InVoy, VI.OutVoy, VI.ETA, VI.COD, VI.Berth, VI.ETD, ServiceRoute,

    FROM VesselInfo VI
    */

    /*

    Temporary Park Data
    -Shifter Id
    -CntrID -> Dummy
    -Park Time -> From shifter take over the container
    -Finish Time
    -ParkId

    "data":[{
        "VesselID":"628498", ---
        "VesselName":"MOL GRANDEUR", ---
        "InVoy":"066W", ---
        "OutVoy":"066W", ---
        "ETA":"2019-12-01 05:10:00.000", ---
        "COD":"2019-12-02 00:00:00.000", ---
        "Berth":"P27", ---
        "ETD":"2019-12-02 07:55:00.000", ---
        "ServiceRoute":null, ---
        "Client":"VANGUARD",
        "TruckTo":"109",
        "Import\/Export":"Export",
        "I\/E":"E",
        "LD\/POD":"KARACHI",
        "DeliverTo":"109",
        "Prefix":"",
        "Number":"",
        "Seal":"",
        "Size":"0",
        "Type":"",
        "Remarks":"",
        "Status":"",
        "DateofStuf\/Unstuf":null,
        "Dummy":708399,
        "Expr1":null,
        "Expr2":null,
        "Expr3":null,
        "Chassis":null}
        */
}
