<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    protected $guarded = ['id'];
    
    public $timestamps = false;

    public function subrate()
    {
        return $this->hasOne('App\Model\SubscriptionRate', 'customer_type_id', 'id');
    }
}
