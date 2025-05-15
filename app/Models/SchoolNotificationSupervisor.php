<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolNotificationSupervisor extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_notifications_supervisors";

    public function supervisor() {
        return $this->hasonethrough(Supervisor::class,SchoolSupervisor::class,'id','id','school_supervisor_id','supervisor_id');
    }
    
}