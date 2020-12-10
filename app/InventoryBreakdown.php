<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryBreakdown extends Model
{
    protected $connection = 'sqlsrv3';
    protected $table = 'HSC_InventoryBreakdown';
    public $timestamps = false;
    protected $primaryKey = 'BreakDownID';
    protected $fillable = [
        'InventoryPalletID', 'Markings', 'Quantity', 'Type', 'Length', 'Breadth', 'Height', 'Volume', 'Remarks', 'CreatedBy', 'CreatedDt', 'UpdatedBy', 'UpdatedDt', 'DelStatus', 'Flags', 'Tally', 'Weight'
    ];
}
