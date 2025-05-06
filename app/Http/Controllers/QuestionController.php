<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\QuestionText;
use App\Models\Answer;
use App\Models\Subject;
use App\Models\QuestionImage;
use App\Models\Complaint;
use DB;
use Image;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    

    public function addQuestion(Request $request)
    {
        $this->validate($request, [
            'question_body' => 'bail|required|min:1',   
            'question_type_id' => 'bail|required|exists:questions_types,id',
            'lesson_id' => "bail|required|exists:lessons,id",
            'is_ture' => 'bail|required'
        ]);
        
        $picName = "";
        
         if($request->hasFile('pic')) {
             $pic = $request->pic;
             $picName= time().rand(100,1000).$pic->getClientOriginalExtension();

             if (!$pic->move('/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/questions/pics/', $picName)) {
                return response()->json("pic error", 500);
            }
            

        }
        
        DB::beginTransaction();
        
        $question = new Question();
        $user = auth()->user();

        $question->question_type_id = $request->question_type_id;
        $question->lesson_id = $request->lesson_id;
        $question->entry_id = $user->id;
        
        $flag = $question->save();
        
        if($flag) {
            
            $questionText = new QuestionText();
            
            $questionText->body = $request->question_body;
            $questionText->id = $question->id;
            
            if($picName != "") {
                
                $questionImage = new QuestionImage();
                
                $questionImage->id = $question->id;
                $questionImage->image = $picName;
                
                $flag = $questionImage->save();
                
                if(!$flag) {
                    DB::rollBack();
                    return response()->json("", 500);
                }
            }
            
            
            $flag = $questionText->save();
            
            if($flag) {
                
                if($request->question_type_id == "1") {
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->answer5 == "صح" ? 1 : 0;
                    $answer->answer = $request->answer5;
                    
                    $flag = $answer->save();
                    
                    if($flag) {
                        DB::commit();
                        return response()->json("", 201);
                    }
                    else {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                }
                else {
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->is_ture == 1 ? 1 : 0;
                    $answer->answer = $request->answer1;
                    
                    $flag = $answer->save();
                    
                    if(!$flag) {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->is_ture == 2 ? 1 : 0;
                    $answer->answer = $request->answer2;
                    
                    $flag = $answer->save();
                    
                    if(!$flag) {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->is_ture == 3 ? 1 : 0;
                    $answer->answer = $request->answer3;
                    
                    $flag = $answer->save();
                    
                    if(!$flag) {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->is_ture == 4 ? 1 : 0;
                    $answer->answer = $request->answer4;
                    
                    $flag = $answer->save();
                    
                    if($flag) {
                        DB::commit();
                        return response()->json("", 201);
                    }
                    else {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                    
                }
            }
            else {
                DB::rollBack();
                return response()->json("", 500);
            }
            
        }
        else {
            DB::rollBack();
            return response()->json("", 500);
        }
        
    }
    
    public function getQuestion(Request $request) {
         
        if($request->question_id) {
            $questions = Question::where('id',$request->question_id)->with('text','answers','entry')->orderBy('id','desc')->paginate(10);
        return $questions;
        }
        else {
            $questions = Question::where('entry_id' , 'like' ,'%'.$request->entry_id.'%')->where('lesson_id',$request->lesson_id)->where('date', 'like' , '%'.$request->date.'%')->with('text','answers','entry')->orderBy('id','desc')->paginate(10);
        return $questions;
            
        }
    }
    
    public function getQuestionsComplaints(Request $request) {
         
        
        if($request->question_id) {
            $questions = Complaint::where('complaint_type_id' , 3)->where('id',$request->question_id)->with('type','student','question.text','question.lesson' ,'question.chapter.subject','question.answers','question.entry')->orderBy('id','desc')->paginate(10);
        return $questions;
        }
        else {
            $questions = Complaint::where('complaint_type_id' , 3)->where('user_id' , 'like' ,'%'.$request->entry_id.'%')->with('type','student','question.text','question.lesson', 'question.chapter.subject','question.answers','question.entry')->orderBy('id','desc')->paginate(10);
        return $questions;
            
        }
    }
    
    public function deleteQuestion(Request $request) {
        $this->validate($request , [
            'question_id' => 'bail|required|exists:questions,id',
            'pass' =>'bail|required'
        ]);
        if($request->pass != '809980') {
         return response()->json("", 500);   
        }
        DB::beginTransaction();
        
        $flag = QuestionText::destroy($request->question_id);
        
        if(!$flag) {
            DB::rollBack();
            return response()->json("", 500);
        }
        
        $flag = Answer::where('question_id',$request->question_id)->delete();
        
        if(!$flag) {
            DB::rollBack();
            return response()->json("", 500);
        }
        
        Question::destroy($request->question_id);
        DB::commit();
    }
    
    public function editQuestion(Request $request) {
        $this->validate($request , [
            'question_id' => 'bail|required|exists:questions,id',
            'question_type_id' => 'bail|required|exists:questions_types,id',
            'question_body' => 'bail|required',
            'is_ture' => 'bail|required'
        ]);
        
        DB::beginTransaction();
        
        $question = Question::find($request->question_id);
        $question->question_type_id = $request->question_type_id;
        
        $flag = $question->save();
        
        if($flag) {
            
            $questionText = QuestionText::find($request->question_id);
            $questionText->body = $request->question_body;
            
            $flag = $questionText->save();
            
            if($flag) {
                $question->answers()->delete();
                
                if($request->question_type_id == "1") {
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->answer5 == "صح" ? 1 : 0;
                    $answer->answer = $request->answer5;
                    
                    $flag = $answer->save();
                    
                    if($flag) {
                        DB::commit();
                        return response()->json("", 200);
                    }
                    else {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                }
                else {
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->is_ture == 1 ? 1 : 0;
                    $answer->answer = $request->answer1;
                    
                    $flag = $answer->save();
                    
                    if(!$flag) {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->is_ture == 2 ? 1 : 0;
                    $answer->answer = $request->answer2;
                    
                    $flag = $answer->save();
                    
                    if(!$flag) {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->is_ture == 3 ? 1 : 0;
                    $answer->answer = $request->answer3;
                    
                    $flag = $answer->save();
                    
                    if(!$flag) {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                    $answer = new Answer();
                    $answer->question_id = $question->id;
                    $answer->is_true = $request->is_ture == 4 ? 1 : 0;
                    $answer->answer = $request->answer4;
                    
                    $flag = $answer->save();
                    
                    if($flag) {
                        DB::commit();
                        return response()->json("", 200);
                    }
                    else {
                        DB::rollBack();
                        return response()->json("", 500);
                    }
                    
                    
                }
                
            }
            else {
                DB::rollBack();
                return response()->json("", 500);
            }
            
            
        }
        
        else {
            DB::rollBack();
                return response()->json("", 500);
        }
        
        
    }
    
    public function editQuestionBody(Request $request) {
        
        $question = QuestionText::find($request->id);
        $question->body = $request->body;
        $flag = $question->save();
        
        if($flag) {
            return response()->json("", 200);
        }
        else {
            return response()->json("", 200);
        }
    }
    
    public function editRightAnswer(Request $request) {
        DB::beginTransaction();
        
        $answers = json_decode($request->answers, true);
        foreach($answers as $answer) {
            $temp = Answer::find($answer[0]);
            $temp->is_true = $answer[1];
            $flag = $temp->save();
            if(!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }
        }
        
        DB::commit();
        return response()->json("", 200);
    }
    public function editAnswers(Request $request) {
        DB::beginTransaction();

            $temp = Answer::find($request->id1);
            $temp->answer = $request->answer1;
            $flag = $temp->save();
            if(!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }
            
            $temp = Answer::find($request->id2);
            $temp->answer = $request->answer2;
            $flag = $temp->save();
            if(!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }
            
            $temp = Answer::find($request->id3);
            $temp->answer = $request->answer3;
            $flag = $temp->save();
            if(!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }
            
            $temp = Answer::find($request->id4);
            $temp->answer = $request->answer4;
            $flag = $temp->save();
            if(!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }
        
        
        DB::commit();
        return response()->json("", 200);
    }
    
    public function print(Request $request) {
        
        $subject = Subject::with('questions.lesson','questions.chapter','questions.text', 'questions.image', 'questions.answers')->find($request->subject_id);
        
        return response()->json(['subject'=>$subject], 200 ,[], JSON_NUMERIC_CHECK);
        
    }
    
    
}
