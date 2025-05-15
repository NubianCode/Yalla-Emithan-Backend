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
        return $this->hasMany(SchoolRegistration::class,'school_student_id');
    }
    
    public function attendances() {
        return $this->hasMany(SchoolStudentAttendance::class);
    }
    
    public function school() {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function type() {
        return $this->belongsTo(SchoolStudentType::class,'school_student_type_id');
    }

    public function state() {
        return $this->belongsTo(State::class,'state_id');
    }

    public function country() {
        return $this->belongsTo(Country::class,'country_id');
    }

    public function getNotificationsAttribute()
{
    return SchoolNotification::where(function ($query) {
        $query->where(function ($q) {
            $q->where('school_notification_type_id', 1)
              ->where('school_id', $this->school_id);
        })
        ->orWhereHas('student', function ($q) {
            $q->where('school_student_id', $this->id);
        })
        ->orWhereHas('room', function ($q) {
            $q->where('school_branch_room_id', $this->school_branch_room_id);
        });
    })->with('supervisor.supervisor')->limit(50)->orderBy('id','desc')->get();
}

    
}