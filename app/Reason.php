<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    public function memories()
    {
        return $this->belongsToMany('App\Memory', 'memories_reasons')->withTimestamps();
    }
}
