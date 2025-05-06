<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolNotificationType extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_notifications_types";
    
}