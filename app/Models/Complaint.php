<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "complaints";
    
}