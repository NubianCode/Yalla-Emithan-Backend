<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSubscription extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_subscriptions";
    
    public function fee() {
    return $this->belongsTo(Subscription::class, 'subscription_id')->select('id', 'price' , 'currency');
    }
    
    public function academicYear() {
        return $this->belongsTo(AcademicYear::class);
    }
    
}