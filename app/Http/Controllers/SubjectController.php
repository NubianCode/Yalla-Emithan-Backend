<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\OldExam;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api");
    }

    public function addSubject(Request $request)
    {
        $this->validate($request, [
            "ar_name" => "bail|required|min:4",
            "en_name" => "bail|required",
            "pic" => "bail|required",
            "class_id" => "bail|required|exists:classes,id",
            "book" => "bail|required",
        ]);

        $bookName = "";

        if ($request->hasFile("book")) {
            $book = $request->book;
            $bookName = time() . rand(100, 1000) . ".pdf";

            if (
                !$request
                    ->file("book")
                    ->move(
                        "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/docs/books/",
                        $bookName
                    )
            ) {
                return response()->json("book error", 500);
            }
        }

        $subject = new Subject();

        $subject->ar_name = $request->ar_name;
        $subject->en_name = $request->en_name;
        $subject->pic = $request->pic;
        $subject->class_id = $request->class_id;
        $subject->book = $bookName;

        $flag = $subject->save();

        if (!$flag) {
            return response()->json("", 500);
        }
        
        $classSubject = new ClassSubject();
        
        $classSubject->class_id = $request->class_id;
        $classSubject->subject_id = $subject->id;
        
        $classSubject->save();
        
        return response()->json("", 201);
        
        
    }

    public function getSubjects(Request $request)
    {
        if ($request->subject_id) {
            $subject = Subject::whereHas('classes', function ($qur) use ($request) {
                        $qur->where('id', $request->class_id);
                    })
                ->where("id", $request->subject_id)
                ->withCount(["chapters", "lessons"])
                ->withCount("chapters.lessons")
                ->withCount("videos")
                ->paginate(30);
            return $subject;
        } else {
            $subject = Subject::where(
                "ar_name",
                "like",
                "%" . $request->name . "%"
            )
                ->whereHas('classes', function ($qur) use ($request) {
                        $qur->where('class_id', $request->class_id);
                    })
                ->withCount(["chapters", "lessons", "questions", "oldExams" , "videos"])
                ->paginate(30);
            return $subject;
        }
    }

    public function editSubject(Request $request)
    {
        $this->validate($request, [
            "subject_id" => "bail|required|exists:subjects,id",
            "subject_name" => "bail|required|min:4",
            "pic" => "bail|required",
        ]);

        $subject = Subject::find($request->subject_id);
        $subject->ar_name = $request->subject_name;

        $flag = $subject->save();

        if ($flag) {
            return response()->json("", 200);
        }
    }
    
    public function editExam(Request $request)
    {
        // Validate the incoming request, for example, checking file type, size, etc.
        $this->validate($request, [
            "exam_id" => "required", // Assuming subject_id is required
            "name" => "required|string", // Assuming name is required
        ]);

        $examObject = OldExam::find($request->exam_id);

        if ($request->hasFile("video")) {
            $exam = $request->exam;

            if (
                !unlink(
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/videos/" .
                        $examObject->url
                ) |
                !$exam->move(
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/videos/",
                    $examObject->url
                )
            ) {
                return response()->json("file error", 500);
            }
            
        }
        $examObject->ar_name = $request->name;
        $examObject->save();
    }

    public function addBook(Request $request)
    {
        $this->validate($request, [
            "subject_id" => "bail|required|exists:subjects,id",
            "book" => "bail|required",
        ]);

        $subject = Subject::find($request->subject_id);
        $bookName = "";

        if ($subject->book == "") {
            if ($request->hasFile("book")) {
                $book = $request->book;
                $bookName = time() . rand(100, 1000) . ".pdf";

                if (
                    !$request
                        ->file("book")
                        ->move(
                            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/docs/books/",
                            $bookName
                        )
                ) {
                    return response()->json("book error", 500);
                }
            }
        } else {
            if ($request->hasFile("book")) {
                unlink(
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/docs/books/" .
                        $subject->book
                );
                $book = $request->book;
                $bookName = time() . rand(100, 1000) . ".pdf";

                if (
                    !$request
                        ->file("book")
                        ->move(
                            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/docs/books/",
                            $bookName
                        )
                ) {
                    return response()->json("book error", 500);
                }
            }
        }

        $subject->book = $bookName;
        $subject->save();
    }

    public function getExams(Request $request)
    {
        $this->validate($request, [
            "subject_id" => "bail|required|exists:subjects,id",
        ]);

        $exams = OldExam::where("subject_id", $request->subject_id)->orderBy('id','desc')->paginate(
            10
        );
        return $exams;
    }

    public function addExam(Request $request)
    {
        $this->validate($request, [
            "subject_id" => "bail|required|exists:subjects,id",
            "name" => "bail|required",
            "exam" => "bail|required",
        ]);

        $examName = "";

        if ($request->hasFile("exam")) {
            $examName = time() . rand(100, 1000) . ".pdf";

            if (
                !$request
                    ->file("exam")
                    ->move(
                        "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/docs/books/",
                        $examName
                    )
            ) {
                return response()->json("book error", 500);
            }
        }

        $exam = new OldExam();

        $exam->ar_name = $request->name;
        $exam->en_name = $request->name;
        $exam->exam = $examName;
        $exam->subject_id = $request->subject_id;

        $exam->save();
    }
    
    public function deleteExam(Request $request)
    {
        $this->validate($request, [
            "exam_id" => "bail|required|exists:old_exams,id",
        ]);
        
        $exam = OldExam::find($request->exam_id);
        if($exam->exam != "") {
                   $path = "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/docs/books/" .
                    $exam->exam;
                   if (file_exists($path)) {
                       unlink($path);
                   }
                    
               }
        OldExam::destroy($request->exam_id);
    }
}
