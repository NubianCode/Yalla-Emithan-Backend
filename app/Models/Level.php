<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "levels";

    public function classes() {
        return $this->hasMany(Classs::class);
    }
    
}