<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "notifications";
    
    public function post() {
        return $this->belongsTo(Post::class , 'post_id');
    }
    public function sender() {
        return $this->belongsTo(Student::class, 'sender_id');
    }

    
}