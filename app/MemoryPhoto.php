<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MemoryPhoto extends Model
{
    protected $table = 'memories_photos';

    protected $fillable = ['memory_id', 'filename'];

    public function memory()
    {
        return $this->belongsTo('App\Memory');
    }
}
