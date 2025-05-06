<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "posts";
    
    public function text() {
        return $this->hasOne(PostText::class , 'id');
    }
    
    public function image() {
        return $this->hasOne(PostImage::class , 'id');
    }
    
    public function video() {
        return $this->hasOne(PostVideo::class);
    }
    
    public function ups() {
        return $this->hasMany(PostUp::class);
    }
    
    public function comments() {
        return $this->hasMany(Comment::class);
    }
    
    public function client() {
        return $this->belongsTo(Student::class, 'client_id');
    }

    
}