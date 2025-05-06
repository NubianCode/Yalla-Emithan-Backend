<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolVideoChapter extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_videos_chapters";
    
    
    public function videos() {
        return $this->hasMany(SchoolVideo::class, 'school_video_chapter_id');
    }
    
    public function subject() {
        return $this->belongsTo(Subject::class,'subject_id');
    }
}