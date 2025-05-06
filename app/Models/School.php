<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools";
    
    public function subscriptions() {
        return $this->hasMany(SchoolSubscription::class);
    }
    
}