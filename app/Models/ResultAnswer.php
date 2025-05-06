<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultAnswer extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "results_answers";

    public function result() {
        return $this->beLongsTo(Result::class);
    }
    
}