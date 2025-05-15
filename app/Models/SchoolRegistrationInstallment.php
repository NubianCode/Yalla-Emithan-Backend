<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolRegistrationInstallment extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_registrations_installments";
    
    public function supervisor() {
        return $this->hasOne(SchoolRegistrationInstallmentSupervisor::class , 'school_registration_installment_id');
    }

    public function currency() {
        return $this->belongsTo(Currency::class,'currency_id');
    }

    public function document() {
        return $this->hasOne(SchoolRegistrationInstallmentDocument::class , 'school_registration_installment_id');

    }
}