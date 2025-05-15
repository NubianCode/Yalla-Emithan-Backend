<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolRegistrationInstallmentSupervisor extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_registrations_installments_supervisors";
    
    public function supervisor() {
        return $this->hasonethrough(Supervisor::class,SchoolSupervisor::class,'id','id','supervisor_id','supervisor_id');
    }
}