<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "lessons";

    public function resultAnswers() {
        return $this->hasMany(ResultAnswer::class);
    }
    
    public function questions() {
        return $this->hasMany(Question::class);
    }
    
}