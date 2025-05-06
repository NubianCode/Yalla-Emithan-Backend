<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "complaints";
    
    
    public function question() {
        return $this->hasOneThrough(Question::class, QuestionComplaint::class,'id','id','id','question_id');
    }
    
    public function type() {
        return $this->hasOneThrough(QuestionComplaintType::class, QuestionComplaint::class,'id','id','id','question_complaint_type_id');
    }
    
    public function complaintType() {
        return $this->beLongsTo(ComplaintType::class);
    }
    
    public function student() {
        return $this->belongsTo(Student::class,'user_id');
    }
}