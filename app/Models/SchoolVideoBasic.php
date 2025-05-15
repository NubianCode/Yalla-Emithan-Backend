<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolVideoBasic extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_videos_basic";

    public function video() {
        return $this->belongsTo(Video::class,'video_id');
    }
    
}