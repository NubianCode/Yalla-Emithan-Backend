<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "subjects";

    public function chapters() {
        return $this->hasMany(Chapter::class);
    }
    
}