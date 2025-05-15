<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformPrice extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "platforms_prices";
    
}