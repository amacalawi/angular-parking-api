<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = ['id'];
    
    public $timestamps = false;

    public function customers()
    {
        return $this->belongsTo('App\Model\Customer', 'customer_id' , 'id');
    }
}
