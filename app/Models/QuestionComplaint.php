<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionComplaint extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "questions_complaints";
    
}