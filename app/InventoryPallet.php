<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryPallet extends Model
{
    protected $table = 'InventoryPallet';
    public $timestamps = false;
    protected $primaryKey = 'InventoryPalletID';
    protected $fillable = [
        'InventoryID', 'SequenceNo', 'ExpCntrID', 'Reserved', 'ReservedBy', 'ReservedDt', 'ClearedDate', 'DeliveryID', 'CreatedBy', 'CreatedDt', 'UpdatedBy', 'UpdatedDt', 'DelStatus', 'InterWhseFlag', 'CurrentLocation', 'InterWhseTo', 'Tag', 'Location', 'DN', 'isActivityForStuffing', 'isActivityForSearch'
    ];
}
