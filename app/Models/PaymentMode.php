<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "payments_modes";
    
}