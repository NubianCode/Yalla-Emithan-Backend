<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostVideo extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "posts_videos";
}