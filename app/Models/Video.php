<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "videos";
    
    public function subject() {
        return $this->belongsTo(Subject::class);
    }
    
}