<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolCost extends Model
{
    public $timestamps = false;
	protected $guarded = [];
    protected $table = "schools_costs";
    
    public function schoolCostType() {
        return $this->belongsTo(SchoolCostType::class);
    }
    
    public function currency() {
        return $this->belongsTo(Currency::class,'currency_id');
    }
    
    public function employee() {
        return $this->hasonethrough(Supervisor::class,SchoolSupervisor::class,'id','id','employee_id','supervisor_id');
    }
    
    public function branch() {
        return $this->hasonethrough(SchoolBranch::class,SchoolBranchCost::class,'school_cost_id','id','id','school_branch_id');
    }
    public function branchId() {
        return $this->hasOne(SchoolBranchCost::class,'school_cost_id');
    }
}