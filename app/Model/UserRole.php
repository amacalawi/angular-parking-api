<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $guarded = ['id'];
    
    public $timestamps = false;

    public function role()
    {
        return $this->belongsTo('App\Model\Role', 'role_id', 'id');
    }
}
