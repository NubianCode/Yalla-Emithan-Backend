<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotePayment extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "notes_payments";

    public function payment() {
        return $this->belongsTO(Payment::class,'id');
    }
    public function note() {
        return $this->belongsTo(Note::class);
    }
    
}