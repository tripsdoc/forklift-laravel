<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContainerView extends Model
{
    protected $table = "HSC2012.dbo.Onee";
    protected $primaryKey = "Dummy";
    public $timestamps = false;

    /*
    I/E
    ETA
    CLIENT
    PREFIX
    NUMBER
    SEAL
    SIZE
    TYPE
    LD/POD
    REMARKS
    STATUS
    */

    /*
    There will be 2 Process
    - Import
        -Driver will bring container from outside to KD, then driver will inform shifter and ask where to park the container
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
}
