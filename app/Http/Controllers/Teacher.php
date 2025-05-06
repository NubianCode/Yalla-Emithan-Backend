<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "teachers";
    
    public function subjects() {
        return $this->hasMany(TeacherSubject::class,'teacher_id');
    }
    
}