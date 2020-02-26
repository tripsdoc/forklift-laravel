<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShifterUser extends Model
{
    protected $connection = 'sqlsrv2';
    protected $table = 'ShifterUser';
    protected $primaryKey = 'ShifterID';
    protected $fillable = [
        'Name', 'UserName', 'Warehouse', 'Password'
    ];
}
