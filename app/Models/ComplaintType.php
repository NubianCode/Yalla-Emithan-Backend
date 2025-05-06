<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintType extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "complaints_types";
    
}