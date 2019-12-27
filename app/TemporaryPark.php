<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TemporaryPark extends Model
{
    protected $table = "HSC2017Test_V2.dbo.HSC_OngoingPark";
    protected $primaryKey = "ParkingID";
    public $timestamps = false;
    protected $fillable = [
        'ParkingLot', 'Dummy', 'createdBy', 'createdDt', 'updatedBy', 'updatedDt'
    ];
}
