<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ForkliftUser extends Model
{
    protected $table = 'ForkliftUser';
    protected $primaryKey = 'UserId';
    public $timestamps = false;
    protected $fillable = [
        'UserName', 'Password'
    ];
}
