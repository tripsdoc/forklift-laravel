<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryPallet extends Model
{
    protected $connection = 'sqlsrv3';
    protected $table = 'HSC_InventoryPallet';
    public $timestamps = false;
    protected $primaryKey = 'InventoryPalletID';
    protected $fillable = [
        'InventoryID', 'SequenceNo', 'ExpCntrID', 'Reserved', 'ReservedBy', 'ReservedDt', 'ClearedDate', 'DeliveryID', 'CreatedBy', 'CreatedDt', 'UpdatedBy', 'UpdatedDt', 'DelStatus', 'InterWhseFlag', 'CurrentLocation', 'InterWhseTo', 'Tag', 'Location', 'DN', 'isActivityForStuffing', 'isActivityForSearch'
    ];

    public function breakdown()
    {
        return $this->hasMany('App\InventoryBreakdown', 'InventoryPalletID', 'InventoryPalletID');
    }
}
