<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolBranchRoomScheduleDay extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_branches_rooms_schedules_days";
    
    public function subjects() {
        return $this->hasMany(SchoolBranchRoomScheduleSubject::class,'school_branch_room_schedule_day_id');
    }
    
    public function day() {
        return $this->belongsTo(Day::class);
    }

}