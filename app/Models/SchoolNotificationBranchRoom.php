<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolNotificationBranchRoom extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_notifications_branches_rooms";
    
    public function room() {
        return $this->belongsTo(SchoolBranchRoom::class,'school_branch_room_id');
    }
    
}