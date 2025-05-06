<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "religions";
    
}