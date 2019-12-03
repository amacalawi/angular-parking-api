<?php

namespace App\Event;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'created_by'
    ];

    public function days()
    {
        return $this->hasMany('App\Event\EventDay');
    }
}
