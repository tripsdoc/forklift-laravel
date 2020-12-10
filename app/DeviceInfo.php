<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceInfo extends Model
{
    protected $connection = 'sqlsrv3';
    protected $table = 'IPS_DeviceInfo';
    protected $primaryKey = 'DeviceInfoId';
    public $timestamps = false;
    protected $fillable = [
        'DeviceName', 'SerialNumber', 'WareHouses', 'IsActive', 'tag'
    ];
}
