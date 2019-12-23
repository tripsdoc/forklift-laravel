<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShifterUser extends Model
{
    protected $table = 'ShifterUser';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'email', 'warehouse', 'password'
    ];
}
