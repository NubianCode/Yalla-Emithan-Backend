<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "students";
    
    public function user() {
        return $this->hasOne(User::class,'id');
    }
    public function subscriptions() {
        return $this->hasMany(Subscription::class,'student_id');
    }
    
    public function schoolStudent()
    {
        return $this->hasOne(SchoolStudent::class, 'student_id');
    }
    
    public function watchedVideos() {
        return $this->hasMany(WatchedVideo::class);
    }
    
    public function teacher() {
        return $this->hasOne(Teacher::class,'id');
    }
    
    public function exams() {
        return $this->hasMany(StudentExam::class);
    }
    
    public function ludo() {
        return $this->hasMany(LudoStudent::class);
    }
    
    public function blocks()
    {
        return $this->hasMany(Block::class, 'client_id1');
    }
    
    public function friendRequests()
    {
        return $this->hasMany(Notification::class, 'client_id');
    }
    
    public function friends() {
        return $this->hasMany(Friend::class,'student_id1');
    }
    
    public function classs() {
        return $this->hasOne(StudentStudentClass::class,'student_id');
    }

    
}