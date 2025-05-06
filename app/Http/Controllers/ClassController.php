<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classs;
use App\Models\LudoStudent;

class ClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api',['except' => ['getAllClasses','getClassesQuestions']]);
    }

    

    public function addClass(Request $request)
    {
        $this->validate($request, [
            'ar_name' => 'bail|required|min:4',
            'en_name' => 'bail|required',
            'level_id' => 'bail|required|exists:levels,id',
            'price' => 'bail|required'
        ]);

        $class = new Classs();

        $class->ar_name = $request->ar_name;
        $class->en_name = $request->en_name;
        $class->level_id = $request->level_id;
        $class->price = $request->price;

        $flag = $class->save();

        if ($flag) {
                return response()->json("", 201);
            }
        else {
            return response()->json("", 500);
        }
    }
    
    public function getClasses(Request $request) {
         
        if($request->class_id) {
            $class = Classs::where('level_id',$request->level_id)->where('id',$request->class_id)->withCount('subjects')->orderBy('id','desc')->paginate(10);
        return $class;
        }
        else {
            $class = Classs::where('level_id',$request->level_id)->where('ar_name' , 'like' , '%'.$request->name.'%')->withCount('subjects')->orderBy('id','desc')->paginate(10);
        return $class;
            
        }
    }
    
    public function getAllClasses(Request $request) {
        $class = Classs::with('level')->get();
        return response()->json(["classes" =>  $class],200);
    }
    
    public function editClass(Request $request) {
        $this->validate($request , [
            'class_id' => 'bail|required|exists:classes,id',
            'class_name' => 'bail|required|min:4',
            'price' => 'bail|required'
        ]);

        $class = Classs::find($request->class_id) ;
        $class->ar_name = $request->class_name;
        $class->price = $request->price;

        $flag = $class->save();

        if($flag) {
            return response()->json("",200);
        }
        
    }
    
    public function getClassesQuestions(Request $request) {
        
        $user = auth()->user();
        
        $questions = Classs::with('questions.text','questions.answers','questions.chapter.subject','level', 'questions.image')->find($request->class_id);
        
        LudoStudent::insert(['class_id' =>$request->class_id,'student_id' =>$user->id]);
        
        return response()->json(['questions'=>$questions->questions], 200 ,[], JSON_NUMERIC_CHECK);
    }
    
    
}
