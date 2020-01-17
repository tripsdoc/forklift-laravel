<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TagLocationLatest extends Model
{
    protected $table = 'TagLocationLatest';
    public $timestamps = false;
    protected $primaryKey = 'Id';
    protected $fillable = [
        'AreaId', 'AreaName', 'CoordinateSystemId', 'CoordinateSystemName', 'PositionTS', 'SmoothedPositionX', 'SmoothedPositionY', 'SmoothedPositionZ', 'PositionAccuracy', 'Zones'
    ];
}
