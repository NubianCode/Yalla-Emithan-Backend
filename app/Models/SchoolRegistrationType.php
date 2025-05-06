<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolRegistrationType extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_registrations_types";
    
}