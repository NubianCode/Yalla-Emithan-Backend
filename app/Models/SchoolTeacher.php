<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolTeacher extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_teachers";
    
    public function teacher() {
        return $this->belongsTo(Student::class,'teacher_id');
    }
    
    public function supervisor() {
        return $this->belongsTo(Supervisor::class,'teacher_id');
    }
    
    public function status() {
    return $this->belongsTo(User::class, 'teacher_id')->select(['id', 'status_id', 'phone']);
}

public function branch() {
        return $this->belongsTo(SchoolBranch::class,'school_branch_id');
    }
}