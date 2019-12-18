<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $table = "HSC_ParkHistory";
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = [
        'parkId', 'CntrId', 'note', 'status', 'parkIn', 'parkOut', 'created_at', 'created_by'
    ];
}
