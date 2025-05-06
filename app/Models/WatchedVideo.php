<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchedVideo extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "watcheds_videos";
    
}