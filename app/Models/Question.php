<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "questions";

    public function text() {
        return $this->belongsTo(QuestionText::class,'id');
    }
    public function image() {
        return $this->belongsTo(QuestionImage::class,'id');
    }
    public function answers() {
        return $this->hasMany(Answer::class);
    }
    public function entry() {
        return $this->belongsTo(Supervisor::class,'entry_id','id');
    }
    public function lesson() {
        return $this->belongsTo(Lesson::class);
    }
    
    public function chapter() {
        return $this->hasOneThrough(Chapter::class, Lesson::class,'id','id','lesson_id','chapter_id');
    }
    
}