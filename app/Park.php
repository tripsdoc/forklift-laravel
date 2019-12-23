<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Park extends Model
{
    protected $table = "HSC_Park";
    protected $primaryKey = "id";
    protected $fillable = [
        'name', 'detail', 'type', 'place'
    ];
}
