<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    // protected $fillable = [];

    protected $guarded = ['id'];
    
    public $timestamps = false;
}
