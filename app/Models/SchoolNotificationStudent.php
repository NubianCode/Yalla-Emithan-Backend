<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolNotificationStudent extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_notifications_students";
    
    public function student() {
        return $this->belongsTo(SchoolStudent::class, "school_student_id");
    }
    
}