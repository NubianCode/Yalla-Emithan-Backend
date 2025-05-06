<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_classes";
    
    public function classs() {
        return $this->belongsTo(Classs::class,'class_id');
    }
}