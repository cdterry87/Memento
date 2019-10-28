<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Memory extends Model
{
    protected $table = 'memories';

    protected $guarded = [];

    public function emotions()
    {
        return $this->hasMany('App\Emotion');
    }

    public function reasons()
    {
        return $this->hasMany('App\Reason');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
