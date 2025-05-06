<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolRegistrationInstallment extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_registrations_installments";
    
    public function supervisor() {
        return $this->hasonethrough(Supervisor::class,SchoolSupervisor::class,'id','id','supervisor_id','supervisor_id');
    }
}