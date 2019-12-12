<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = ['id'];
    
    public $timestamps = false;

    public function type()
    {
        return $this->belongsTo('App\Model\CustomerType', 'customer_type_id', 'id');
    }
}
