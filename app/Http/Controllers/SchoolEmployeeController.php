<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classs;
use App\Models\User;
use App\Models\Supervisor;
use App\Models\SchoolSupervisor;
use App\Models\SchoolBranchSupervisor;
use App\Models\SchoolTeacher;
use App\Models\SchoolBranch;
use Illuminate\Support\Facades\Hash;
use DB;

class SchoolEmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api");
    }
    
    public function editTeacher(Request $request)
{
    $this->validate($request, [
        'school_teacher_id' => 'required|exists:schools_teachers,id',
        'teacher_name' => 'required|string|min:4',
        'school_branch_id' => 'required|exists:schools_branches,id',
        'teacher_mobile_number' => 'required',
    ]);

    DB::beginTransaction();

    try {
        // Get the school teacher record
        $schoolTeacher = SchoolTeacher::findOrFail($request->school_teacher_id);

        // 1. Update supervisor name
        $supervisor = Supervisor::findOrFail($schoolTeacher->teacher_id);
        $supervisor->name = $request->teacher_name;
        $supervisor->save();

        // 2. Update user mobile number
        $user = User::findOrFail($schoolTeacher->teacher_id);
        $user->phone = $request->teacher_mobile_number;
        $user->credential = $request->teacher_mobile_number;
        $user->save();

        // 3. Update branch
        $schoolTeacher->school_branch_id = $request->school_branch_id;
        $schoolTeacher->save();

        DB::commit();

        return response()->json(['message' => 'School teacher updated successfully'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to update teacher',
            'error' => $e->getMessage(),
        ], 500);
    }
}



    public function addTeacher(Request $request)
    {
        $this->validate($request, [
            "teacher_mobile_number" => "required|min:10|max:14",
            "teacher_name" => "required|min:4",
            "school_id" => "required|exists:schools,id",
            "school_branch_id" => "required|exists:schools_branches,id",
        ]);

        DB::beginTransaction();

        try {
            // 1. Create User
            $user = new User();
            $user->password = Hash::make("123456"); // or auto-generated
            $user->phone = $request->teacher_mobile_number;
            $user->credential = $request->teacher_mobile_number;
            $user->user_type_id = 2; 
            if (!$user->save()) {
                DB::rollBack();
                return response()->json(
                    ["message" => "User creation failed"],
                    500
                );
            }

            // 2. Create Supervisor
            $supervisor = new Supervisor();
            $supervisor->id = $user->id;
            $supervisor->supervisor_type_id = 5; // or derive it if needed
            $supervisor->name = $request->teacher_name;
            if (!$supervisor->save()) {
                DB::rollBack();
                return response()->json(
                    ["message" => "Supervisor creation failed"],
                    500
                );
            }
            
            $student = new Student();

            // 3. Create SchoolTeacher
            $schoolTeacher = new SchoolTeacher();
            $schoolTeacher->school_id = $request->school_id;
            $schoolTeacher->teacher_id = $user->id;
            $schoolTeacher->school_branch_id = $request->school_branch_id;
            $schoolTeacher->save();

            DB::commit();
            return response()->json(
                [
                    "message" =>
                        "School teacher and supervisor added successfully",
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                ["message" => "Error occurred", "error" => $e->getMessage()],
                500
            );
        }
    }

    public function getTeachers(Request $request)
{
    $this->validate($request, [
        'school_id' => 'required|exists:schools,id',
        'school_branch_id' => 'nullable|integer|exists:schools_branches,id',
        'key' => 'nullable|string',
    ]);

    $user = auth()->user();

    // Get supervisor context for current user
    $supervisor = SchoolSupervisor::where('school_id', $request->school_id)
        ->where('supervisor_id', $user->id)
        ->first();

    if (!$supervisor) {
        return response()->json(['message' => 'Supervisor not found'], 404);
    }

    $query = SchoolTeacher::query()
        ->where('school_id', $request->school_id)
        ->with('supervisor','status' , 'branch')
        ->orderBy('id', 'desc');

    // Apply branch filter if present
    if ($request->filled('school_branch_id')) {
        $query->where('school_branch_id', $request->school_branch_id);
    }

    // Filter based on role
    if ($supervisor->school_supervisor_type_id != 4) {
        $query->where('school_supervisor_type_id', '!=', 4)
              ->where('id', '!=', $supervisor->id);
    }

    // Optional search key (by teacher name, etc.)
    if ($request->filled('key')) {
        $query->whereHas('supervisor', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->key . '%');
        });
    }

    $teachers = $query->paginate(50);

    $branches = SchoolBranch::where('school_id', $request->school_id)->get();

    return response()->json([
        'teachers' => $teachers,
        'branches' => $branches,
    ]);
}


    public function addSupervisor(Request $request)
    {
        $this->validate($request, [
            "supervisor_mobile_number" => "required|min:10|max:14",
            "supervisor_name" => "bail|required|min:4",
            "school_supervisor_type_id" =>
                "bail|required|exists:schools_supervisors_types,id",
            "school_branch_id" => "bail|required|exists:schools_branches,id",
            "school_id" => "bail|required|exists:schools,id",
            "school_branch_id" => "bail|nullable",
        ]);

        DB::beginTransaction();

        $user = new User();
        $user->password = Hash::make("123456");
        $user->phone = $request->supervisor_mobile_number;
        $user->credential = $request->supervisor_mobile_number;
        $user->user_type_id = 2;

        $flag = $user->save();

        if (!$flag) {
            DB::rollBack();
            return response()->json("", 500);
        }

        $supervisor = new Supervisor();
        $supervisor->id = $user->id;
        $supervisor->supervisor_type_id = 5;
        $supervisor->name = $request->supervisor_name;

        $flag = $supervisor->save();

        if (!$flag) {
            DB::rollBack();
            return response()->json("", 500);
        }

        $schoolSupervisor = new SchoolSupervisor();

        $schoolSupervisor->supervisor_id = $supervisor->id;
        $schoolSupervisor->school_id = $request->school_id;
        $schoolSupervisor->school_supervisor_type_id =
            $request->school_supervisor_type_id;

        $flag = $schoolSupervisor->save();

        if (!$flag) {
            DB::rollBack();
            return response()->json("", 500);
        }

        $schoolBranchSupervisor = new SchoolBranchSupervisor();

        $schoolBranchSupervisor->school_branch_id = $request->school_branch_id;
        $schoolBranchSupervisor->school_supervisor_id = $schoolSupervisor->id;

        $schoolBranchSupervisor->save();

        DB::commit();
        return response()->json("", 201);
    }

    public function editSupervisor(Request $request)
    {
        $this->validate($request, [
            "supervisor_id" => "required|exists:supervisors,id",
            "supervisor_mobile_number" => "required|min:10|max:14",
            "supervisor_name" => "required|min:4",
            "school_supervisor_type_id" =>
                "required|exists:schools_supervisors_types,id",
            "school_branch_id" => "required|exists:schools_branches,id",
            "school_branch_id" => "bail|nullable",
        ]);

        DB::beginTransaction();

        try {
            $supervisor = Supervisor::findOrFail($request->supervisor_id);
            $user = User::findOrFail($supervisor->id); // assuming same ID

            // Update User
            $user->phone = $request->supervisor_mobile_number;
            $user->credential = $request->supervisor_mobile_number;
            $user->password = Hash::make($request->supervisor_mobile_number);
            $user->save();

            // Update Supervisor
            $supervisor->name = $request->supervisor_name;
            $supervisor->save();

            // Update SchoolSupervisor
            $schoolSupervisor = SchoolSupervisor::where(
                "supervisor_id",
                $supervisor->id
            )->firstOrFail();
            $schoolSupervisor->school_supervisor_type_id =
                $request->school_supervisor_type_id;
            $schoolSupervisor->save();

            // Update SchoolBranchSupervisor
            $schoolBranchSupervisor = SchoolBranchSupervisor::where(
                "school_supervisor_id",
                $schoolSupervisor->id
            )->firstOrFail();
            $schoolBranchSupervisor->school_branch_id =
                $request->school_branch_id;
            $schoolBranchSupervisor->save();

            DB::commit();
            return response()->json(
                ["message" => "Supervisor updated successfully"],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "error" => "Something went wrong",
                    "details" => $e->getMessage(),
                ],
                500
            );
        }
    }
}
