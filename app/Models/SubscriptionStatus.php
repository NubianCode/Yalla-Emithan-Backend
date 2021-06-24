<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionStatus extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "subscriptions_status";
    
}