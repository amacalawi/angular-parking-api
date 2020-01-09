<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = ['id'];
    
    public $timestamps = false;

    public function privileges()
    {   
        return $this->hasMany('App\Model\Privilege', 'role_id', 'id');    
    }
}
