<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolLesson extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_lessons";
    
    public function video() {
        return $this->belongsTo(Video::class,'lesson_id');
    }
    
}