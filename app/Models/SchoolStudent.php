<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolStudent extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_students";
    
    public function room() {
        return $this->belongsTo(SchoolBranchRoom::class,'school_branch_room_id');
    }
    
    public function registrations() {
        return $this->hasMany(SchoolRegistration::class,'student_id');
    }
    
    public function attendances() {
        return $this->hasMany(SchoolStudentAttendance::class);
    }
    
    public function school() {
        return $this->belongsTo(School::class, 'school_id');
    }
    
}