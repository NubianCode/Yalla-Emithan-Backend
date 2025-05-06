<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolBranchRoom extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_branches_rooms";
    
    public function branch() {
        return $this->belongsTo(SchoolBranch::class,'school_branch_id');
    }
    
    public function students() {
        return $this->hasMany(SchoolStudent::class,'school_branch_room_id');
    }
    
    public function classs() {
        return $this->belongsTo(Classs::class,'class_id');
    }
    
}