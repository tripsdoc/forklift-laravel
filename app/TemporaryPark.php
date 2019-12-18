<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TemporaryPark extends Model
{
    protected $table = "HSC_OngoingPark";
    protected $primaryKey = "id";
    protected $fillable = [
        'parkId', 'CntrId', 'parkIn', 'parkOut', 'created_at', 'created_by', 'updated_at', 'updated_by'
    ];
}
