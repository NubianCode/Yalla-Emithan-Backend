<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentExam extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "students_exams";
    
}