<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolBranchSupervisor extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_branches_supervisors";
    
    public function branch() {
        return $this->belongsTo(SchoolBranch::class,'school_branch_id');
    }
    
}