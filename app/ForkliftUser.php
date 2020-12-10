<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ForkliftUser extends Model
{
    protected $connection = 'sqlsrv3';
    protected $table = 'IPS_ForkliftUser';
    protected $primaryKey = 'UserId';
    public $timestamps = false;
    protected $fillable = [
        'UserName', 'Password', 'isSupervisor'
    ];
}
