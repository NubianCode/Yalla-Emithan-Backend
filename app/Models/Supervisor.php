<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "supervisors";
    
    public function user() {
        return $this->hasOne(User::class,'id');
    }
    
    public function supervisorType() {
        return $this->belongsTo(SupervisorType::class);
    }
    
    public function questions() {
        return $this->hasMany(Question::class , 'entry_id');
    }
    
    public function schoolSupervisor() {
        return $this->hasOne(SchoolSupervisor::class,'supervisor_id');
    }
    
}