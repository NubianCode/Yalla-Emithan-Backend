<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "chapters";

    public function lessons() {
        return $this->hasMany(Lesson::class);
    }
    public function questions() {
        return $this->hasManyThrough(Question::class, Lesson::class);
    }
    public function subject() {
        return $this->belongsTo(Subject::class);
    }
    
}