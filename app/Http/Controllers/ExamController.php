<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Result;
use App\Models\PaymentMode;
use App\Models\ResultAnswer;
use App\Models\QuestionComplaint;
use App\Models\Classs;
use App\Models\Complaint;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\StudentExam;
use DB;

class ExamController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api", ["except" => ["getExamV2"]]);
    }

    public function getExam(Request $request)
    {
        $user = auth()->user();

        $paymentMode = PaymentMode::find(1);

        $active = Subscription::where("student_id", $user->id)
            ->orderBy("id", "desc")
            ->first();
        $active = $active != null && $active->status_id != 2;

        if ($paymentMode->is_free == 0 && !$active) {
            $count = StudentExam::where("device_id", $request->device_id)
                ->orWhere("student_id", $user->id)
                ->limit(10)
                ->count();
            if ($count >= 5) {
                return response()->json("", 411);
            }
        }

        $request->lessons = json_decode($request->lessons, true);
        $exam = Question::inRandomOrder()
            ->whereIn("lesson_id", $request->lessons)
            ->orderBy("id", "DESC")
            ->with("lesson", "chapter", "text", "image", "answers")
            ->limit($request->limit)
            ->get();

        $studentExam = new StudentExam();
        $studentExam->class_id = $request->class_id;
        $studentExam->student_id = $user->id;
        $studentExam->device_id = $request->device_id;

        $studentExam->save();

        return response()->json(
            ["questions" => $exam],
            200,
            [],
            JSON_NUMERIC_CHECK
        );
    }

    public function getExamV2(Request $request)
    {
        $user = auth()->user();

        $paymentMode = PaymentMode::find(1);

        if(false) {
        //if($paymentMode->is_free == 0 && $request->country_code != 'sd') {
            if ($user == null) {
            return response()->json("", 411);
        }else  {
            $active = Subscription::where("student_id", $user->id)
            ->where('subscription_package_id','!=','3')
                ->orderBy("id", "desc")
                ->first();
                
            $active = $active != null && $active->status_id != 2;
            
            $classIsFree = Classs::find($request->class_id);
            
            if ($classIsFree->is_free == 0 && !$active) {
                $count = StudentExam::where("device_id", $request->device_id)
                    ->orWhere("student_id", $user->id)
                    ->whereDate('date', today()) // Filter by today's date
                    ->limit(3)
                    ->count();
                if ($count > 3) {
                    return response()->json("", 411);
                }
            }
        }
        }

        $request->lessons = json_decode($request->lessons, true);
        $exam = Question::inRandomOrder()
            ->whereIn("lesson_id", $request->lessons)
            ->orderBy("id", "DESC")
            ->with("lesson", "chapter", "text", "image", "answers")
            ->limit($request->limit)
            ->get();

        $studentExam = new StudentExam();
        $studentExam->class_id = $request->class_id;
        $studentExam->student_id = $user == null ? 1 : $user->id;
        $studentExam->device_id = $request->device_id;
        $studentExam->subject_id = $request->subject_id;
        $studentExam->questions_limit = $request->limit;

        $studentExam->save();

        return response()->json(
            ["questions" => $exam],
            200,
            [],
            JSON_NUMERIC_CHECK
        );
    }

    public function getMatchQuestions(Request $request)
    {
        $exam = Question::inRandomOrder()
            ->orderBy("id", "DESC")
            ->with("lesson", "chapter.subject", "text", "image", "answers")
            ->limit($request->limit)
            ->get();
        return response()->json(
            ["questions" => $exam],
            200,
            [],
            JSON_NUMERIC_CHECK
        );
    }

    public function getTest(Request $request)
    {
        $exam = Question::whereHas("chapter", function ($qur) use ($request) {
            $qur->where("subject_id", $request->subject_id);
        })
            ->with("text", "image", "answers")
            ->with("lesson", "chapter")
            ->get();

        return response()->json(
            ["questions" => $exam],
            200,
            [],
            JSON_NUMERIC_CHECK
        );
    }

    public function addResult(Request $request)
    {
        $this->validate($request, [
            "subject_id" => "bail|required|exists:subjects,id",
            "result" => "bail|required",
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

                if (!$flag) {
                    DB::rollBack();
                    return response()->json("", 500);
                }
            }
            DB::commit();
            return response()->json("", 200);
        } else {
            return response()->json("", 500);
            DB::rollBack();
        }
    }

    public function addQuestionComplaint(Request $request)
    {
        $this->validate($request, [
            "complaint" => "bail|required",
            "question_id" => "bail|required|exists:questions,id",
            "question_complaint_type_id" =>
                "bail|required|exists:questions_complaints_types,id",
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
            $questionComplaint->question_complaint_type_id =
                $request->question_complaint_type_id;

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
