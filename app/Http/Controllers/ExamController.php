<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Result;
use App\Models\ResultAnswer;
use App\Models\QuestionComplaint;
use App\Models\Complaint;
use DB;

class ExamController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getExam(Request $request)
    {
        $request->lessons = json_decode($request->lessons, true);
        $exam = Question::whereIn('lesson_id', $request->lessons)->with('text', 'image', 'answers')->orderBy('id', 'DESC')->limit($request->limit)->get();
        return response()->json(['questions'=>$exam], 200);
    }

    public function addResult(Request $request)
    {
        $this->validate($request, [
            'subject_id' => 'bail|required|exists:subjects,id',
            'result' => 'bail|required',
        ]);

        $request->answers = json_decode($request->answers, true);

        $user = auth()->user();

        DB::beginTransaction();

        $result = new Result();

        $result->student_id = $user->id;
        $result->subject_id = $request->subject_id;
        $result->result = $request->result;

        $flag = $result->save();

        if ($flag) {
            foreach ($request->answers as $answer) {
                $resultAnswer = new ResultAnswer();

                $resultAnswer->result_id = $result->id;
                $resultAnswer->lesson_id = $answer[0];
                $resultAnswer->is_true = $answer[1];
                
                $flag = $resultAnswer->save();

                if ($flag) {
                    DB::commit();
                    return response()->json("", 200);
                } else {
                    return response()->json("", 500);
                    DB::rollBack();
                }
            }
        } else {
            return response()->json("", 500);
            DB::rollBack();
        }
    }

    public function addQuestionComplaint(Request $request)
    {
        $this->validate($request, [
            'complaint' => 'bail|required|min:20',
            'question_id' => 'bail|required|exists:questions,id',
            'question_complaint_type_id' => 'bail|required|exists:questions_complaints_types,id'
        ]);
        DB::beginTransaction();

        $complaint = new Complaint();

        $complaint->user_id = auth()->user()->id;
        $complaint->complaint = $request->complaint;
        $complaint->complaint_type_id = "3";

        $flag = $complaint->save();

        if ($flag) {
            $questionComplaint = new QuestionComplaint();

            $questionComplaint->id = $complaint->id;
            $questionComplaint->question_id = $request->question_id;
            $questionComplaint->question_complaint_type_id = $request->question_complaint_type_id;
            
            $flag = $questionComplaint->save();

            if ($flag) {
                DB::commit();
                return response()->json("", 201);
            }
        } else {
            return response()->json("", 500);
            DB::rollBack();
        }
    }
}
