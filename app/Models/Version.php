<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "versions";
    
}