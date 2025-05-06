<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "friends";
    
    public function friend() {
        return $this->belongsTo(Student::class,'student_id2');
    }
    
}