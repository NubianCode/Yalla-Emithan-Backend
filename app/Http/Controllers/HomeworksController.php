<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{
    SchoolHomework,
    SchoolClass,
    SchoolBranchRoom,
    SchoolStudent,
    Subject,
    SchoolSupervisor,
    SchoolBranch,
    School,
    AcademicYear,
    SchoolHomeworkSolution
};

class HomeworksController extends Controller
{
    private $notification;

    public function __construct()
    {
        $this->middleware("auth:api");
        $this->notification = new Notification();
    }
    
    public function getStudentHomeworks(Request $request) {
        
        $this->validate($request, [
            "school_branch_room_id" => "required|exists:schools_branches_rooms,id",
        ]);
        
        $academicYear = AcademicYear::orderBy('id','desc')->first();
        
        $homeworks = SchoolHomework::where('school_branch_room_id',$request->school_branch_room_id)->with(['subject', 'homeworkSolutions.status'])
        ->where('academic_year_id', $academicYear->id)
        ->orderBy('id', 'desc')->paginate(50);
        
        return $homeworks;
    }

    public function getHomeworks(Request $request)
    {
        $this->validate($request, [
            "school_id" => "required|exists:schools,id",
            "key" => "nullable",
            "school_branch_id" => "nullable|exists:schools_branches,id",
        ]);

        $user = auth()->user();

        $academicYear = AcademicYear::orderBy("id", "desc")->first();

        $supervisor = SchoolSupervisor::where("school_id", $request->school_id)
            ->where("supervisor_id", $user->id)
            ->first();

        // Homework filtering
        $query = SchoolHomework::with([
            "room.classs.level",
            "subject",
            "homeworkSolutions.student",
            "homeworkSolutions.status",
            "supervisor",
        ])
            ->where("academic_year_id", $academicYear->id)
            ->orderBy("id", "desc");

        if ($request->filled("school_branch_id")) {
            $query->whereHas("room.branch", function ($query) use ($request) {
                $query->where("id", $request->school_branch_id);
            });
        } else {
            $query->where("school_id", $request->school_id);
        }

        if ($request->filled("key")) {
            $query->where("id", $request->key);
        }

        $homeworks = $query->paginate(50);

        // Branches
        $branches = SchoolBranch::where("school_id", $request->school_id)
            ->with("rooms.classs.level", "rooms.classs.subjects")
            ->get();

        return response()->json([
            "homeworks" => $homeworks,
            "branches" => $branches,
        ]);
    }

    public function AddHomework(Request $request)
    {
        // Check that at least one of homework or image is present
        if (empty($request->homework) && !$request->hasFile("image")) {
            return response()->json(
                [
                    "error" => "يجب إدخال نص الواجب أو إرفاق صورة",
                ],
                422
            );
        }

        // Validate the request
        $this->validate($request, [
            "school_branch_room_id" =>
                "required|integer|exists:schools_branches_rooms,id",
            "school_id" => "required|integer|exists:schools,id",
            "subject_id" => "required|integer|exists:subjects,id",
            "homework" => "nullable|string",
            "expire_date" => "required|date",
            "image" => "nullable|image|max:2048",
        ]);

        DB::beginTransaction();

        try {
            // Handle image upload
            $imageName = null;
            if ($request->hasFile("image")) {
                $imageName =
                    uniqid() . "." . $request->file("image")->extension();
                $uploaded = $request->image->move(
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/homeworks/",
                    $imageName
                );

                if (!$uploaded) {
                    DB::rollBack();
                    return response()->json(["error" => "فشل رفع الصورة"], 500);
                }
            }

            $academicYear = AcademicYear::orderBy("id", "desc")->first();
            $user = auth()
                ->user()
                ->load("schoolSupervisor")->schoolSupervisor;

            $homework = SchoolHomework::create([
                "school_branch_room_id" => $request->school_branch_room_id,
                "school_id" => $request->school_id,
                "subject_id" => $request->subject_id,
                "homework" => $request->homework,
                "expire_date" => $request->expire_date,
                "image" => $imageName,
                "supervisor_id" => $user->id,
                "academic_year_id" => $academicYear->id,
            ]);

            $school = School::find($request->school_id);
            $students = SchoolStudent::where(
                "school_branch_room_id",
                $request->school_branch_room_id
            )->get();
            $subject = Subject::find($request->subject_id);

            $homeworkMessage = "📚 واجب جديد\nواجب رقم {$homework->id} في مادة {$subject->ar_name}\n{$request->homework}";

// Build an array of WhatsApp numbers
$phoneNumbers = $students->pluck('student_whatsapp_mobile_number')->filter()->values()->toArray();

if ($homework->image) {
    $requestBody = [
        'instanceId'   => $school->whatsapp_instance_id,
        'token'        => $school->whatsapp_token,
        'phoneNumbers' => $phoneNumbers,
        'imageUrl'     => "https://yalla-emtihan.com/yalla-emtihan/public/homeworks/" . $homework->image,
        'caption'      => $homeworkMessage,
    ];

    // Send all images in one request
    $this->requestSocket($requestBody, 'sendBulkWhatsAppImages');
} else {
    $requestBody = [
        'instanceId'   => $school->whatsapp_instance_id,
        'token'        => $school->whatsapp_token,
        'phoneNumbers' => $phoneNumbers,
        'message'      => $homeworkMessage,
    ];

    // Send all messages in one request
    $this->requestSocket($requestBody, 'sendBulkWhatsAppMessages');
}

            DB::commit();

            return response()->json(
                [
                    "message" => "تم إضافة الواجب بنجاح",
                    "homework_id" => $homework->id,
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "error" => "حدث خطأ أثناء إضافة الواجب",
                    "details" => $e->getMessage(),
                ],
                500
            );
        }
    }

    public function editHomework(Request $request)
    {
        $this->validate($request, [
            "school_homework_id" => "required|exists:schools_homeworks,id",
            "school_branch_room_id" =>
                "required|integer|exists:schools_branches_rooms,id",
            "subject_id" => "required|integer|exists:subjects,id",
            "homework" => "nullable|string",
            "expire_date" => "required|date",
            "image" => "nullable|image|max:2048",
        ]);

        DB::beginTransaction();

        try {
            $homework = SchoolHomework::findOrFail(
                $request->school_homework_id
            );

            // Delete old image if new one is uploaded
            if ($request->hasFile("image")) {
                if ($homework->image) {
                    $oldPath = "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/homeworks/{$homework->image}";
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $imageName =
                    uniqid() . "." . $request->file("image")->extension();
                $uploaded = $request->image->move(
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/homeworks/",
                    $imageName
                );

                if (!$uploaded) {
                    DB::rollBack();
                    return response()->json(["error" => "فشل رفع الصورة"], 500);
                }

                $homework->image = $imageName;
            }

            // Update other fields
            $homework->homework = $request->homework;
            $homework->subject_id = $request->subject_id;
            $homework->school_branch_room_id = $request->school_branch_room_id;
            $homework->expire_date = $request->expire_date;
            $homework->save();

            // Notify students
            $students = SchoolStudent::where(
                "school_branch_room_id",
                $homework->school_branch_room_id
            )->get();
            $school = School::find($homework->school_id);
            $subject = Subject::find($homework->subject_id);

            $homeworkMessage = "✏️ تعديل على واجب\nواجب رقم {$homework->id} في مادة {$subject->ar_name}\n{$homework->homework}";

            foreach ($students as $student) {
                $chatId = $student->student_whatsapp_mobile_number;

                // If homework has an image, send it first
                if ($homework->image) {
                    $imageUrl =
                        "https://yalla-emtihan.com/yalla-emtihan/public/homeworks/" .
                        $homework->image;

                    $this->notification->sendWhatsAppImage(
                        $school->whatsapp_instance_id,
                        $school->whatsapp_token,
                        $chatId,
                        $imageUrl,
                        $homeworkMessage
                    );
                } else {
                    // Then send the message
                    $this->notification->sendWhatsAppMessage(
                        $school->whatsapp_instance_id,
                        $school->whatsapp_token,
                        $chatId,
                        $homeworkMessage
                    );
                }
            }

            DB::commit();

            return response()->json(
                [
                    "message" => "تم تعديل الواجب بنجاح",
                    "homework_id" => $homework->id,
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "error" => "حدث خطأ أثناء تعديل الواجب",
                    "details" => $e->getMessage(),
                ],
                500
            );
        }
    }

    public function addSolution(Request $request)
    {
        // Validate the incoming request
        $this->validate($request, [
            "homework_id" => "required|exists:schools_homeworks,id", // Validate homework existence
            "image" => "required|image|max:10240", // Validate the image file (max 10MB)
        ]);

        $image = $request->file("image");

        // Start a transaction to ensure atomic operations
        DB::beginTransaction();

        try {
            // Generate a unique filename for the image
            $imageName = uniqid() . "." . $request->file("image")->extension();

            // Move the image to the desired directory
            if (
                !$image->move(
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/homework_solutions/",
                    $imageName
                )
            ) {
                // Rollback if image upload fails
                DB::rollBack();
                return response()->json(
                    ["error" => "Failed to upload image."],
                    500
                );
            }

            // Create the homework solution record
            $solution = SchoolHomeworkSolution::create([
                "school_homework_id" => $request->homework_id,
                "student_id" => auth()->user()->id, // Get the authenticated user's ID
                "image" => $imageName,
            ]);

            // Update the status of the homework
            SchoolHomework::where("id", $request->homework_id)
                ->limit(1)
                ->update(["status_id" => 3]);

            // Commit the transaction if everything is successful
            DB::commit();

            // Return the created solution as a response
            return response()->json($solution, 201);
        } catch (\Exception $e) {
            // In case of any exception, rollback the transaction
            DB::rollBack();

            // Return an error response
            return response()->json(
                ["error" => "An error occurred: " . $e->getMessage()],
                500
            );
        }
    }

    public function updateSolution(Request $request)
    {
        // Validate the incoming request
        $this->validate($request, [
            "solution_id" => "required|exists:schools_homeworks_solutions,id", // Validate homework existence
            "image" => "required|image|max:10240", // Validate the image file (max 10MB)
        ]);

        $image = $request->file("image");

        // Start a transaction to ensure atomic operations
        DB::beginTransaction();

        try {
            // Find the existing solution
            $solution = SchoolHomeworkSolution::findOrFail(
                $request->solution_id
            );

            // Delete the old image file if it exists
            $oldImagePath =
                "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/homework_solutions/" .
                $solution->image;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }

            // Generate a unique filename for the new image
            $imageName = uniqid() . "." . $image->extension();

            // Move the new image to the desired directory
            if (
                !$image->move(
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/homework_solutions/",
                    $imageName
                )
            ) {
                // Rollback if image upload fails
                DB::rollBack();
                return response()->json(
                    ["error" => "Failed to upload image."],
                    500
                );
            }

            // Update the homework solution record with the new image
            $solution->update(["image" => $imageName]);

            // Update the status of the homework (if necessary)
            SchoolHomework::where("id", $request->homework_id)
                ->limit(1)
                ->update(["status_id" => 3]);

            // Commit the transaction if everything is successful
            DB::commit();

            // Return the updated solution as a response
            return response()->json($solution, 200);
        } catch (\Exception $e) {
            // In case of any exception, rollback the transaction
            DB::rollBack();

            // Return an error response
            return response()->json(
                ["error" => "An error occurred: " . $e->getMessage()],
                500
            );
        }
    }

    public function markSolution(Request $request)
    {
        $this->validate($request, [
            "school_solution_id" =>
                "required|exists:schools_homeworks_solutions,id", // Validate homework existence
            "status_id" => "required|exists:homeworks_status,id", // Validate the image file (max 10MB)
        ]);

        $solution = SchoolHomeworkSolution::findOrFail(
            $request->school_solution_id
        );

        $solution->status_id = $request->status_id;

        $solution->save();
    }
}
