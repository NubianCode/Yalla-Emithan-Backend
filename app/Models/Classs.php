<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classs extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "classes";

    public function Subjects() {
        return $this->hasMany(Subject::class , 'class_id');
    }
    
}