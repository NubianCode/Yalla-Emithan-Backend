<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolBranch extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_branches";
    
    public function rooms() {
        return $this->hasMany(SchoolBranchRoom::class,'school_branch_id');
    }
    
}