<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;

class ExamController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getExam(Request $request) { 
        $request->lessons = json_decode($request->lessons, true);
        $exam = Question::whereIn('lesson_id', $request->lessons)->with('text','image','answers')->limit($request->limit)->get();
        return response()->json(['questions'=>$exam],200);
    }

}
