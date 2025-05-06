<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lesson;
use App\Models\QuestionComplaint;
use DB;

class LessonController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    

    public function addLesson(Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|min:4',
            'chapter_id' => 'bail|required|exists:chapters,id'
        ]);

        $lesson = new Lesson();

        $lesson->name = $request->name;
        $lesson->chapter_id = $request->chapter_id;

        $flag = $lesson->save();

        if ($flag) {
                return response()->json("", 201);
            }
        else {
            return response()->json("", 500);
        }
    }
    
    public function getLessons(Request $request) {
         
        if($request->lesson_id) {
            $lesson = Lesson::where('id',$request->lesson_id)->where('chapter_id',$request->chapter_id)->withCount('questions')->paginate(10);
        return $lesson;
        }
        else {
            $lesson = Lesson::where('name' , 'like' , '%'.$request->name.'%')->where('chapter_id',$request->chapter_id)->withCount('questions')->paginate(10);
        return $lesson;
            
        }
    }
    
    public function editLesson(Request $request) {
        $this->validate($request , [
            'lesson_id' => 'bail|required|exists:lessons,id',
            'lesson_name' => 'bail|required|min:4',
        ]);

        $lesson = Lesson::find($request->lesson_id) ;
        $lesson->name = $request->lesson_name;

        $flag = $lesson->save();

        if($flag) {
            return response()->json("",200);
        }
    }
    public function deleteLesson(Request $request) {
    // Validate the lesson_id in the request
    $this->validate($request, [
        'lesson_id' => 'bail|required|exists:lessons,id',
        'pass' =>'bail|required'
    ]);
    
    if($request->pass != '809980') {
         return response()->json("", 500);   
        }

    // Begin a transaction to ensure all or nothing is deleted
    DB::beginTransaction();

    try {
        // Find the Lesson by its ID
        $lesson = Lesson::with('questions')->find($request->lesson_id);

        if ($lesson) {
            // Start by deleting all related lessons
                // Delete all related questions for this lesson
                foreach ($lesson->questions as $question) {
                    // Delete related question text, images, and answers
                    $question->text()->delete();  // Assuming a 'questionText' relationship exists
                    $question->image()->delete();  // Assuming a 'questionImages' relationship exists
                    $question->answers()->delete();  // Assuming an 'answers' relationship exists
                    $questionComplaints = QuestionComplaint::where('question_id',$question->id)->get();
                    
                    foreach ($questionComplaints as $complaint) {
                        // Properly destroy related complaints and complaints
                        Complaint::destroy($complaint->complaint_id);  // Assuming 'complaint_id' is the correct field
                        $complaint->delete();  // Delete the complaint itself
                    }
                    $question->delete();  // Delete the question itself
                }
                $lesson->delete();  // Delete the lesson itself



            // Commit the transaction if everything is successful
            DB::commit();

            // Return a success message or response
            return response()->json(['message' => 'Lesson and all related records deleted successfully.'], 200);
        } else {
            // If Lesson not found, rollback the transaction
            DB::rollBack();
            return response()->json(['message' => 'Lesson not found.'], 404);
        }
    } catch (\Exception $e) {
        // If an error occurs, rollback the transaction
        DB::rollBack();

        // Optionally log the error message for debugging purposes
        \Log::error("Error deleting Lesson: " . $e->getMessage());

        // Return a failure response
        return response()->json(['message' => 'An error occurred while deleting the Lesson.'], 500);
    }
}

public function emptyLesson(Request $request) {
    // Validate the lesson_id in the request
    $this->validate($request, [
        'lesson_id' => 'bail|required|exists:lessons,id',
        'pass' =>'bail|required'
    ]);
    
    if($request->pass != '809980') {
         return response()->json("", 500);   
        }

    // Begin a transaction to ensure all or nothing is deleted
    DB::beginTransaction();

    try {
        // Find the Lesson by its ID
        $lesson = Lesson::with('questions')->find($request->lesson_id);

        if ($lesson) {
            // Start by deleting all related lessons
                // Delete all related questions for this lesson
                foreach ($lesson->questions as $question) {
                    // Delete related question text, images, and answers
                    $question->text()->delete();  // Assuming a 'questionText' relationship exists
                    $question->image()->delete();  // Assuming a 'questionImages' relationship exists
                    $question->answers()->delete();  // Assuming an 'answers' relationship exists
                    $questionComplaints = QuestionComplaint::where('question_id',$question->id)->get();
                    
                    foreach ($questionComplaints as $complaint) {
                        // Properly destroy related complaints and complaints
                        Complaint::destroy($complaint->complaint_id);  // Assuming 'complaint_id' is the correct field
                        $complaint->delete();  // Delete the complaint itself
                    }
                    $question->delete();  // Delete the question itself
                }


            // Commit the transaction if everything is successful
            DB::commit();

            // Return a success message or response
            return response()->json(['message' => 'Lesson and all related records deleted successfully.'], 200);
        } else {
            // If Lesson not found, rollback the transaction
            DB::rollBack();
            return response()->json(['message' => 'Lesson not found.'], 404);
        }
    } catch (\Exception $e) {
        // If an error occurs, rollback the transaction
        DB::rollBack();

        // Optionally log the error message for debugging purposes
        \Log::error("Error deleting Lesson: " . $e->getMessage());

        // Return a failure response
        return response()->json(['message' => 'An error occurred while deleting the Lesson.'], 500);
    }
}
    
    
}
