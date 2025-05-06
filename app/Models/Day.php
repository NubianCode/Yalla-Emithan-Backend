<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "days";
    
    public function subjects() {
        return $this->hasMany(SchoolClassScheduleSubject::class,'day_id');
    }
    
}