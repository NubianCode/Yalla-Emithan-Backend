<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "payments";
    
}