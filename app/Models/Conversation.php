<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "conversations";
    
    public function messages() {
        return $this->hasMany(Message::class);
    }
    
    public function student1() {
        return $this->belongsTo(Student::class,'student_id1');
    }
    
    public function student2() {
        return $this->belongsTo(Student::class,'student_id2');
    }
    
    public function friend() {
        return $this->hasMany(Friend::class);
    }
    
}