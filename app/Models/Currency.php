<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Currency extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = "currencies";

    protected static function booted()
    {
        // Add a global scope to exclude id = 0 by default
        static::addGlobalScope('excludeZeroId', function (Builder $builder) {
            $builder->where('id', '!=', 0);
        });
    }
}
