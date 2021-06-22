<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "subscriptions";

    public function payment() {
        return $this->belongsTO(Payment::class,'id');
    }
    
}