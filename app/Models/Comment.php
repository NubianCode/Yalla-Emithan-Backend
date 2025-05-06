<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "comments";
    
    public function client() {
        return $this->belongsTo(Student::class, 'client_id');
    }
    
    public function loves() {
        return $this->hasMany(CommentLove::class);
    }
    
    public function post() {
        return $this->belongsTo(Post::class);
    }

    
}