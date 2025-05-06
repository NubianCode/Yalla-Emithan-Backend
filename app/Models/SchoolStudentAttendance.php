<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolStudentAttendance extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_students_attendances";
    
}