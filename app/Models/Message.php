<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "messages";
    
}