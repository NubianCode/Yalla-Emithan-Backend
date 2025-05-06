<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentStudentClass extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "students_students_classes";
    
    public function studentClass() {
        return $this->belongsTo(StudentClass::class,'student_class_id');
    }
    
}