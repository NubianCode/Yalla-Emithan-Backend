<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentLove extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "comments_loves";

    
}