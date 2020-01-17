<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceInfo extends Model
{
    protected $table = 'DeviceInfo';
    protected $primaryKey = 'DeviceInfoId';
    public $timestamps = false;
    protected $fillable = [
        'DeviceName', 'SerialNumber', 'WareHouses', 'IsActive', 'tag'
    ];
}
