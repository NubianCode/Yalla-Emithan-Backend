<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentClass extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "students_classes";
    
}