<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolRegistration extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_registrations";
    
    public function type() {
        return $this->belongsTo(SchoolRegistrationType::class,'school_registration_type_id');
    }
    
    public function student() {
        return $this->belongsTo(SchoolStudent::class,'student_id');
    }
    
    public function room() {
        return $this->belongsTo(SchoolBranchRoom::class,'school_branch_room_id');
    }
    
    public function installments() {
        return $this->hasMany(SchoolRegistrationInstallment::class,'school_registration_id');
    }
    
    public function supervisor() {
        return $this->hasonethrough(Supervisor::class,SchoolSupervisor::class,'id','id','supervisor_id','supervisor_id');
    }
    
    public function currency() {
        return $this->belongsTo(Currency::class,'currency_id');
    }
    
    public function academicYear() {
        return $this->belongsTo(AcademicYear::class,'academic_year_id');
    }
}