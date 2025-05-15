<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolVideo extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_videos";

    public function basic() {
        return $this->hasOne(SchoolVideoBasic::class, 'school_video_id');
    }

    public function advanced() {
        return $this->hasOne(SchoolVideoAdvanced::class, 'school_video_id');
    }
    
}