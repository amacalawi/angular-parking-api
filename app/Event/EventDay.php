<?php

namespace App\Event;

use Illuminate\Database\Eloquent\Model;

class EventDay extends Model
{
    public function event()
    {
        return $this->belongsTo('App\Event\Event');
    }
}
