<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShifterUser extends Model
{
    protected $connection = 'sqlsrv2';
    protected $table = 'HSC2017.dbo.IPS_ShifterUser';
    protected $primaryKey = 'ShifterID';
    protected $fillable = [
        'Name', 'UserName', 'Warehouse', 'Password'
    ];
}
