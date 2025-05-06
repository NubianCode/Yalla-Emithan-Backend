<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "subscriptions";

    public function payment() {
        return $this->belongsTo(Payment::class,'id');
    }

    public function classs() {
        return $this->hasOneThrough(LiveClass::class,SubscriptionLiveClass::class,'subscription_id','id','id','live_class_id');
    }

    public function subscriptionPackage() {
        return $this->belongsTo(SubscriptionPackage::class,'subscription_package_id');
    }

    public function subscriptionStatus() {
        return $this->belongsTo(SubscriptionStatus::class);
    }
    
    public function status() {
        return $this->belongsTo(Status::class);
    }
    
    public function student() {
        return $this->belongsTo(Student::class);
    }
    
}