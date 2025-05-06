<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "blocks";
    
    public function student() {
        return $this->belongsTo(Student::class,'client_id2');
    }
    
}