<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolVideoView extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "school_video_views";
}