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
    
}