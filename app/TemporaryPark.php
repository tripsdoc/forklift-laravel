<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TemporaryPark extends Model
{
    protected $table = "HSC_OngoingPark";
    protected $primaryKey = "id";
    protected $fillable = [
        'ShifterId', 'parkId', 'CntrId', 'requestIn', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by'
    ];
}
