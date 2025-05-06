<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MBookRequest extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "mbook_requests";

}