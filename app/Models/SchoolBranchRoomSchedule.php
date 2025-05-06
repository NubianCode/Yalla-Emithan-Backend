<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolBranchRoomSchedule extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_branches_rooms_schedules";
    
    public function room() {
        return $this->belongsTo(SchoolBranchRoom::class,'school_branch_room_id');
    }
    public function days() {
        return $this->hasMany(SchoolBranchRoomScheduleDay::class,'school_branch_room_schedule_id');
    }

}