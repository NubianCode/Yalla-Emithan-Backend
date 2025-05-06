<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentComplaint extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "comments_complaints";
    
}