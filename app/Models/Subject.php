<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "subjects";

    public function chapters() {
        return $this->hasMany(Chapter::class);
    }
    
    public function lessons() {
        return $this->hasManyThrough(Lesson::class, Chapter::class);
    }
    
    public function questions() {
        return $this->hasManyDeep(Question::class, [Chapter::class ,Lesson::class]);
    }

    public function oldExams() {
        return $this->hasMany(OldExam::class);
    }
    public function notes() {
        return $this->hasMany(Note::class);
    }
    
    public function videos() {
        return $this->hasMany(Video::class);
    }
    
    public function classes() {
        return $this->hasManyThrough(Classs::class , ClassSubject::class , 'subject_id' , 'id' , 'id' , 'class_id');
    }
    
    public function progresses() {
        return $this->hasMany(SchoolVideoStudentProgress::class, 'subject_id');
    }
    
    public function videosChpaters() {
        return $this->hasMany(SchoolVideoChapter::class, 'subject_id');
    }
    
    
}