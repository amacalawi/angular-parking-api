<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $guarded = ['id'];
    
    public $timestamps = false;

    public function transaction()
    {
        return $this->belongsTo('App\Model\Transaction', 'transaction_id', 'id');
    }
}
