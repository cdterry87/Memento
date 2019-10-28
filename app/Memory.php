<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Memory extends Model
{
    protected $table = 'memories';

    protected $guarded = [];

    public function reasons()
    {
        return $this->belongsToMany('App\Reason', 'memories_reasons')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
