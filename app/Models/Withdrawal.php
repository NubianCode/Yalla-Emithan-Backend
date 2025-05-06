<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "withdrawals";
    
}