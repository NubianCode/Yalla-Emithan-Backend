<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSupervisor extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_supervisors";


    public function type() {
        return $this->belongsTo(SchoolSupervisorType::class,'school_supervisor_type_id');
    }
    
    public function data() {
        return $this->belongsTo(Supervisor::class,'supervisor_id');
    }
    public function branch() {
        return $this->hasOne(SchoolBranchSupervisor::class,'school_supervisor_id');
    }
    public function status() {
    return $this->belongsTo(User::class, 'supervisor_id')->select(['id', 'status_id', 'phone']);
}
}