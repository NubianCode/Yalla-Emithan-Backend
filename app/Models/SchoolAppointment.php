<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolAppointment extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_appointments";
    
    public function appointmentClass() {
        return $this->hasOne(SchoolAppointmentClass::class,'school_appointment_id');
    }
    
}
    