<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Match extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "matches";
    
}