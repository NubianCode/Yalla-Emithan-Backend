<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolBranch;
use App\Models\SchoolBranchRoom;
use App\Models\Classs;
use App\Models\SchoolStudent;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api");
    }
    
    public function addBranch(Request $request) {
        $this->validate($request, [
            "school_id" => "bail|required|exists:schools,id",
            "name" => "required|bail",
        ]);
        
        $schoolBranch = new SchoolBranch();
        
        $schoolBranch->school_id = $request->school_id;
        $schoolBranch->name = $request->name;
        
        $schoolBranch->save();
        
        return response()->json(
            ["message" => "Branch added successfully"],
            201
        );
    }
    
    public function editBranch(Request $request) {
        $this->validate($request, [
            "school_branch_id" => "bail|required|exists:schools_branches,id",
            "name" => "required|bail",
        ]);
        
        $schoolBranch = SchoolBranch::find($request->school_branch_id);
        
        $schoolBranch->name = $request->name;
        
        $schoolBranch->save();
    }

    public function getBranches(Request $request)
    {
        $this->validate($request, [
            "school_id" => "bail|required|exists:schools,id",
        ]);

        $classes = Classs::where("status_id", "1")
            ->with("level")
            ->orderBy("rank")
            ->get();

        $branches = SchoolBranch::where("school_id", $request->school_id)
            ->with([
                "rooms" => function ($q) {
                    $q->orderBy("id", "desc");
                },
                "rooms.students",
                "rooms.classs.level",
            ])
            ->orderBy("id", "desc")
            ->paginate(50);

        return response()->json([
            "branches" => $branches,
            "classes" => $classes,
        ]);
    }

    public function addRoomAPI(Request $request)
    {
        $this->validate($request, [
            "school_branch_id" => "bail|required|exists:schools_branches,id",
            "class_id" => "bail|required|exists:classes,id",
            "name" => "nullable|bail",
        ]);

        $room = new SchoolBranchRoom();
        $room->school_branch_id = $request->school_branch_id;
        $room->class_id = $request->class_id;
        $room->name = $request->name;
        $room->save();

        $room->load("classs.level");
        $room->students = [];

        return response()->json(
            ["message" => "Room added successfully", "room" => $room],
            201
        );
    }

    // Edit (Update)
    public function editRoomAPI(Request $request)
    {
        $this->validate($request, [
            "school_branch_room_id" =>
                "bail|required|exists:schools_branches,id",
            "class_id" => "bail|required|exists:classes,id",
            "name" => "nullable|bail",
        ]);

        $room = SchoolBranchRoom::find($request->school_branch_room_id);

        $room->class_id = $request->class_id;
        $room->name = $request->name;
        $room->save();

        $room->load(["classs.level", "students"]);

        return response()->json(
            ["message" => "Room edited successfully", "room" => $room],
            200
        );
    }

    // Delete
    public function deleteRoomAPI(Request $request)
    {
        $this->validate($request, [
            "school_branch_room_id" =>
                "bail|required|exists:schools_branches_rooms,id",
        ]);
        $room = SchoolBranchRoom::findOrFail($request->school_branch_room_id);
        $room->delete();

        return response()->json(["message" => "Room deleted successfully"]);
    }
    
    public function changeStudentRoom(Request $request)
    {
        $this->validate($request, [
            "school_branch_room_id" => "bail|required|exists:schools_branches_rooms,id",
            "school_student_id" => "bail|required|exists:schools_students,id",
        ]);
        
        
        $student = SchoolStudent::findOrFail($request->school_student_id);
        $student->school_branch_room_id = $request->school_branch_room_id;
        
        $student->save();

        return response()->json(["message" => "student has added to room successfully" , 'student' => $student]);
    }
}
