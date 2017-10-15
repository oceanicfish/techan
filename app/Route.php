<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    //
    protected $fillable = ['path','total_distance', 'total_time', 'token'];
}
