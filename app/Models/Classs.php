<?php

namespace App\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use Illuminate\Database\Eloquent\Model;

class Classs extends Model
{
    use LaravelSubQueryTrait;
    
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    
    
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "classes";

    public function Subjects() {
        return $this->hasManyThrough(Subject::class , ClassSubject::class , 'class_id' , 'id' , 'id' , 'subject_id');
    }
    
    public function level() {
        return $this->belongsTo(Level::class);
    }
    
    public function payments() {
        return $this->hasmanythrough(Payment::class,Subscription::class, 'class_id' , 'id');
    }
    
    public function questions() {
        return $this->hasManyDeep(Question::class, [Subject::class , Chapter::class ,Lesson::class],['class_id']);
    }
    
    public function exams() {
     return $this->hasMany(StudentExam::class,'class_id');   
    }
    public function ludo() {
        return $this->hasMany(LudoStudent::class,'class_id');  
    }
    public function watchedVideos() {
        return $this->hasManyDeep(WatchedVideo::class,[Subject::class,Video::class ],['class_id']);
    }
    
}