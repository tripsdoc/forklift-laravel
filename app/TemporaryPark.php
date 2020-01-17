<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TemporaryPark extends Model
{
    protected $connection = 'sqlsrv3';
    protected $table = "HSC_OngoingPark";
    protected $primaryKey = "ParkingID";
    public $timestamps = false;
    protected $fillable = [
        'ParkingLot', 'Dummy', 'createdBy', 'createdDt', 'updatedBy', 'updatedDt'
    ];
}
