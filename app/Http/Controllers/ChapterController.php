<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chapter;
use App\Models\QuestionComplaint;
use App\Models\Complaint;
use DB;

class ChapterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    

    public function addChapter(Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|min:4',
            'subject_id' => 'bail|required|exists:subjects,id'
        ]);

        $chapter = new Chapter();

        $chapter->name = $request->name;
        $chapter->subject_id = $request->subject_id;

        $flag = $chapter->save();

        if ($flag) {
                return response()->json("", 201);
            }
        else {
            return response()->json("", 500);
        }
    }
    
    public function getChapters(Request $request) {
         
        if($request->chapter_id) {
            $chapters = Chapter::where('id',$request->chapter_id)->where('subject_id',$request->subject_id)->withCount('lessons','questions')->paginate(10);
        return $chapters;
        }
        else {
            $chapters = Chapter::where('name' , 'like' , '%'.$request->name.'%')->where('subject_id',$request->subject_id)->withCount('lessons','questions')->paginate(10);
        return $chapters;
            
        }
    }
    
    public function editChapter(Request $request) {
        $this->validate($request , [
            'chapter_id' => 'bail|required|exists:chapters,id',
            'chapter_name' => 'bail|required|min:4',
        ]);

        $chapter = Chapter::find($request->chapter_id) ;
        $chapter->name = $request->chapter_name;

        $flag = $chapter->save();

        if($flag) {
            return response()->json("",200);
        }
    }
    
    public function deleteChapter(Request $request) {
    // Validate the chapter_id in the request
    $this->validate($request, [
        'chapter_id' => 'bail|required|exists:chapters,id',
        'pass' =>'bail|required'
    ]);
    
    if($request->pass != '809980') {
         return response()->json("", 500);   
        }

    // Begin a transaction to ensure all or nothing is deleted
    DB::beginTransaction();

    try {
        // Find the chapter by its ID
        $chapter = Chapter::with('lessons.questions')->find($request->chapter_id);

        if ($chapter) {
            // Start by deleting all related lessons
            foreach ($chapter->lessons as $lesson) {
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
            }

            // Now delete the chapter
            $chapter->delete();  // Delete the chapter

            // Commit the transaction if everything is successful
            DB::commit();

            // Return a success message or response
            return response()->json(['message' => 'Chapter and all related records deleted successfully.'], 200);
        } else {
            // If chapter not found, rollback the transaction
            DB::rollBack();
            return response()->json(['message' => 'Chapter not found.'], 404);
        }
    } catch (\Exception $e) {
        // If an error occurs, rollback the transaction
        DB::rollBack();

        // Optionally log the error message for debugging purposes
        \Log::error("Error deleting chapter: " . $e->getMessage());

        // Return a failure response
        return response()->json(['message' => 'An error occurred while deleting the chapter.'], 500);
    }
}
    
    
}
