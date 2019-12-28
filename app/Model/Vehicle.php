<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $guarded = ['id'];
    
    public $timestamps = false;

    public function fixrate()
    {
        return $this->hasOne('App\Model\FixedRate', 'vehicle_id', 'id');
    }
}
