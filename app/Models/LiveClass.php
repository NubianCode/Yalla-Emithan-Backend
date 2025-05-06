<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveClass extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "live_classes";
    
    public function teacher() {
        return $this->belongsTo(Student::class,'teacher_id');
    }
    
    public function subscription() {
        return $this->hasOne(SubscriptionLiveClass::class,'live_class_id');
    }
    
}