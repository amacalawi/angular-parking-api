<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = ['id'];
    
    public $timestamps = false;

    public function customer()
    {
        return $this->belongsTo('App\Model\Customer', 'customer_id', 'id');
    }

    public function detail()
    {
        return $this->hasOne('App\Model\TransactionDetail', 'transaction_id', 'id');
    }

    public function type()
    {
        return $this->belongsTo('App\Model\TransactionType', 'transaction_type_id', 'id');
    }
}
