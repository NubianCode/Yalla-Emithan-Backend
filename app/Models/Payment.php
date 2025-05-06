<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "payments";

    public function subscription() {
        return $this->hasOne(Subscription::class,'id');
    }

    public function notePayment() {
        return $this->hasOne(NotePayment::class,'id');
    }
    
    public function student() {
        return $this->belongsTo(Student::class);
    }
    
    public function mBookRequest() {
        return $this->hasOne(MBookRequest::class,'id');
    }
    
}