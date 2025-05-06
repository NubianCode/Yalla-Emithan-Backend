<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostUp extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "posts_ups";
    
    public function post() {
        return $this->belongsTo(Post::class);
    }
}