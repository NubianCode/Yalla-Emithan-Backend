<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "notes";
    
}