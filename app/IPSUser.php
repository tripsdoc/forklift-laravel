<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IPSUser extends Model
{
    protected $connection = 'sqlsrv3';
    protected $table = 'IPS_IpsUser';
    protected $primaryKey = 'UserId';
    public $timestamps = false;
    protected $fillable = [
        'UserName', 'Password'
    ];
}
