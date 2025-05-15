<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolNotification extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_notifications";
    
    public function room() {
        return $this->hasOne(SchoolNotificationBranchRoom::class);
    }
    
    public function student() {
        return $this->hasOne(SchoolNotificationStudent::class);
    }
    
    public function supervisor() {
        return $this->hasOne(SchoolNotificationSupervisor::class,'school_notification_id');
    }
    
}