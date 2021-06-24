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
        return $this->belongsTo(Classs::class,'class_id');
    }

    public function subscriptionPackage() {
        return $this->belongsTo(SubscriptionPackage::class);
    }

    public function subscriptionStatus() {
        return $this->belongsTo(SubscriptionStatus::class);
    }
    
}