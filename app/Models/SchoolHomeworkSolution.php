<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolHomeworkSolution extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_homeworks_solutions";
    
    public function student() {
        return $this->belongsTo(SchoolStudent::class,'school_student_id');
    }
    
    public function status() {
        return $this->belongsTo(HomeworkStatus::class,'status_id');
    }
    
}