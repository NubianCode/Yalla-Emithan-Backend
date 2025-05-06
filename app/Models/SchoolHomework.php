<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolHomework extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_homeworks";
    
    public function subject() {
        return $this->belongsTo(Subject::class);
    }
    
    public function homeworkSolutions() {
        return $this->hasMany(SchoolHomeworkSolution::class,'school_homework_id');
    }
    
    public function room() {
        return $this->belongsTo(SchoolBranchRoom::class,'school_branch_room_id');
    }
    
    public function supervisor() {
        return $this->hasonethrough(Supervisor::class,SchoolSupervisor::class,'id','id','supervisor_id','supervisor_id');
    }
    
}