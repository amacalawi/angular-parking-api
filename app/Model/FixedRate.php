<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FixedRate extends Model
{
    protected $guarded = ['id'];
    
    public $timestamps = false;

    public function vehicle()
    {
        return $this->belongsTo('App\Model\Vehicle', 'vehicle_id', 'id');
    }
}
