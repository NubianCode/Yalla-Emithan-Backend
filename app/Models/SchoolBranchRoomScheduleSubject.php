<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolBranchRoomScheduleSubject extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_branches_rooms_schedules_subjects";
    
    public function subject() {
        return $this->belongsTo(Subject::class);
    }
    
    public function teacher() {
        return $this->hasOneThrough(Student::class,SchoolTeacher::class,'id','id','teacher_id','teacher_id');
    }

}