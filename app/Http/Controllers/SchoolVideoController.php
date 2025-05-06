<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\SchoolVideoView;

class SchoolVideoController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api");
    }
    
    public function getSchoolVideos(Request $request) {
    $this->validate($request, [
        "school_id" => "bail|required|exists:schools,id",
        "class_id" => "bail|required|exists:classes,id",
    ]);

    $classId = $request->class_id;
    $studentId = $request->student_id;
    
    $user = auth()->user();

    $videos = Subject::where('class_id', $request->class_id)
    ->with([
        'videosChpaters' => function($query) use ($request) {
            $query->where('school_id', $request->school_id)
                  ->with('videos');
        },
        'progresses' => function($query) use ($user) {
            $query->where('student_id', $user->id)->limit(1);
        }
    ])
    ->get();

    return response()->json($videos);
}

public function watchSchoolVideo(Request $request) {
    $this->validate($request, [
        "school_video_id" => "bail|required|exists:schools_videos,id",
    ]);
    
    $student = auth()->user();
    
    SchoolVideoView::create([
        'school_video_id' => $request->school_video_id,
        'student_id' => $student->id,
    ]);

    return response()->json([
        "status" => true,
        "message" => "Video view recorded successfully."
    ],201);
}


    
}