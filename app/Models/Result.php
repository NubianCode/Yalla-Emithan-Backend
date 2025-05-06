<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "results";
    
    
    public function student() {
        return $this->beLongsTo(Student::class);
    }
}