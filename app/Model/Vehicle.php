<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    // protected $fillable = [];

    protected $guarded = ['id'];
    
    public $timestamps = false;
}
