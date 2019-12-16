<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IPSUser extends Model
{
    protected $table = 'IpsUser';
    protected $primaryKey = 'UserId';
    public $timestamps = false;
    protected $fillable = [
        'UserName', 'Password'
    ];
}
