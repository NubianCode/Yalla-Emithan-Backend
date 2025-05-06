<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\SchoolHomework;
use App\Models\SchoolHomeworkSolution;
use App\Models\SchoolLesson;
use App\Models\AcademicYear;
use App\Models\SchoolAppointment;
use App\Models\SchoolNotification;
use App\Models\SchoolStudent;
use App\Models\SchoolBranchRoomSchedule;
use App\Models\SchoolClass;
use App\Models\SchoolTeacher;
use App\Models\SchoolBranchRoomScheduleSubject;
use App\Models\SchoolBranchRoomScheduleDay;
use App\Models\SchoolCost;
use App\Models\SchoolCostType;
use App\Models\Currency;
use App\Models\SchoolRegistration;
use App\Models\SchoolStudentAttendance;
use App\Models\SchoolBranch;
use App\Models\SchoolBranchRoom;
use App\Models\SchoolRegistrationType;
use App\Models\SchoolRegistrationInstallment;
use App\Models\State;
use App\Models\SchoolSupervisor;
use App\Models\Religion;
use App\Models\SchoolSupervisorType;
use App\Models\SchoolBranchCost;
use App\Models\SchoolNotificationType;
use App\Models\SchoolNotificationStudent;
use App\Models\SchoolNotificationBranchRoom;
use Carbon\Carbon;
use DB;

class SchoolController extends Controller
{
    private $notification;
    public function __construct()
    {
        $this->middleware("auth:api", [
            "except" => ["getSchools", "getSchoolsV2"],
        ]);

        $this->notification = new Notification();
    }

    public function getStudentsByKey(Request $request)
    {
        // Validate that the 'key' is provided, but allow it to be null
        $this->validate($request, [
            "key" => "nullable|required|string",
            "school_id" => "bail|required|exists:schools,id",
        ]);

        // Get the key from the request
        $key = $request->input("key");

        // Check if the key exists and then perform the query
        $students = SchoolStudent::where("school_id", $request->school_id)
            ->where(function ($query) use ($key) {
                // Apply 'where' condition for both full_name or id
                $query
                    ->where("full_name", "like", "%{$key}%")
                    ->orWhere("id", "like", "%{$key}%");
            })
            ->orderBy("id", "desc") // Order by id descending

            ->paginate(50); // Paginate the results, 50 per page
        return response()->json($students); // Return the results as JSON
    }

    public function getAppointments(Request $request)
    {
        $this->validate($request, [
            "class_id" => "bail|required|exists:schools_classes,id",
            "school_id" => "bail|required|exists:schools,id",
        ]);

        $academicYear = AcademicYear::orderBy("id", "desc")->first();

        $appointments = SchoolAppointment::where(
            "academic_year_id",
            $academicYear->id
        ) // Always required
            ->where(function ($query) use ($request) {
                // Grouping conditions together
                $query
                    ->where("appointment_type_id", "1") // First condition

                    ->where("school_id", $request->school_id) // Second condition
                    ->orWhereHas("appointmentClass", function ($query) use (
                        $request
                    ) {
                        // Or the related model condition
                        $query->where("class_id", $request->class_id); // Filter on the related model
                    });
            })
            ->orderBy("id", "desc")
            ->paginate(50);

        return $appointments;
    }

    public function getRegistrations(Request $request)
    {
        $this->validate($request, [
            "school_id" => "bail|required|exists:schools,id",
            "school_branch_id" =>
                "nullable|sometimes|integer|exists:schools_branches,id",
        ]);

        $academicYear = AcademicYear::orderBy("id", "desc")->first();

        $branches = SchoolBranch::where(
            "school_id",
            $request->school_id
        )->get();
        $rooms;
        $types = SchoolRegistrationType::get();
        $religions = Religion::get();
        $currencies = Currency::get();
        $branches = [];

        $user = auth()->user();

        $supervisor = SchoolSupervisor::where("school_id", $request->school_id)
            ->where("supervisor_id", $user->id)
            ->first();

        $registrations;

        if ($supervisor->school_supervisor_type_id == 4) {
            $rooms = SchoolBranchRoom::whereHas("branch", function (
                $query
            ) use ($request) {
                $query->where("school_id", $request->school_id);
            })
                ->with("classs.level")
                ->get();

            $branches = SchoolBranch::where(
                "school_id",
                $request->school_id
            )->get();
            if ($request->school_branch_id == "") {
                $registrations = SchoolRegistration::where(
                    "academic_year_id",
                    $academicYear->id
                )
                    ->with([
                        "type",
                        "student",
                        "room.classs.level",
                        "room.branch",
                        "installments" => function ($query) {
                            $query->orderByDesc("id", "desc"); // Orders installments by ID in descending order
                        },
                        "installments.supervisor",
                        "supervisor",
                        "currency",
                    ])
                    ->orderBy("id", "desc")
                    ->paginate(50);
            } else {
                $registrations = SchoolRegistration::where(
                    "academic_year_id",
                    $academicYear->id
                )
                    ->whereHas("room.branch", function ($query) use ($request) {
                        $query->where(
                            "school_branch_id",
                            $request->school_branch_id
                        );
                    })
                    ->with([
                        "type",
                        "student",
                        "room.classs.level",
                        "room.branch",
                        "installments" => function ($query) {
                            $query->orderByDesc("id", "desc"); // Orders installments by ID in descending order
                        },
                        "installments.supervisor",
                        "supervisor",
                        "currency",
                    ])
                    ->orderBy("id", "desc")
                    ->paginate(50);
            }
        } else {
            $rooms = SchoolBranchRoom::where(
                "school_branch_id",
                $request->school_branch_id
            )
                ->whereHas("branch", function ($query) use ($request) {
                    $query->where("school_id", $request->school_id);
                })
                ->with("classs.level")
                ->get();
            $registrations = SchoolRegistration::where(
                "academic_year_id",
                $academicYear->id
            )
                ->whereHas("room.branch", function ($query) use ($request) {
                    $query->where(
                        "school_branch_id",
                        $request->school_branch_id
                    );
                })
                ->with([
                    "type",
                    "student",
                    "room.classs.level",
                    "room.branch",
                    "installments" => function ($query) {
                        $query->orderByDesc("id");
                    },
                    "installments.supervisor",
                    "supervisor",
                    "currency",
                ])
                ->orderBy("id", "desc")
                ->paginate(50);
        }

        foreach ($registrations as $registration) {
            $registration->is_me =
                $registration->supervisor_id == $supervisor->id;
            foreach ($registration->installments as $installment) {
                $installment->is_me =
                    $installment->supervisor_id == $supervisor->id;
            }
        }

        return response()->json(
            [
                "registrations" => $registrations,
                "branches" => $branches,
                "rooms" => $rooms,
                "currencies" => $currencies,
                "types" => $types,
                "religions" => $religions,
                "branches" => $branches,
            ],
            200
        );
    }

    public function getNotifications(Request $request)
    {
        $this->validate($request, [
            "school_branch_room_id" =>
                "bail|required|exists:schools_branches_rooms,id",
        ]);

        $academicYear = AcademicYear::orderBy("id", "desc")->first();

        $notifications = SchoolNotification::where(
            "academic_year_id",
            $academicYear->id
        )
            ->orderBy("id", "desc")

            ->paginate(50);

        return $notifications;
    }

    public function getSchoolNotifications(Request $request)
    {
        $this->validate($request, [
            "school_id" => "bail|required|exists:schools,id",
            "key" => "nullable|string",
        ]);

        // Get the current academic year
        $academicYear = AcademicYear::orderBy("id", "desc")->first();

        $notifications = SchoolNotification::where(
            "academic_year_id",
            $academicYear->id
        )
            ->where("school_id", $request->school_id)
            ->when($request->filled("key"), function ($query) use ($request) {
                $key = "%" . $request->key . "%";
                $query->where(function ($q) use ($key) {
                    $q->where("notification", "like", $key)->orWhere(
                        "id",
                        "like",
                        $key
                    );
                });
            })
            ->with("room.room.classs.level", "student.student", "supervisor")
            ->orderBy("id", "desc")
            ->paginate(50);

        $rooms = SchoolBranchRoom::whereHas("branch", function ($query) use (
            $request
        ) {
            $query->where("school_id", $request->school_id);
        })
            ->with("classs.level")
            ->get();

        $notificationsTypes = SchoolNotificationType::get();

        return response()->json(
            [
                "notifications" => $notifications,
                "rooms" => $rooms,
                "notifications_types" => $notificationsTypes,
            ],
            200
        );
    }

    public function getSchools(Request $request)
    {
        $schools = School::where("status_id", 2)
            ->orderBy("id", "desc")
            ->paginate(50);

        return $schools;
    }

    public function getSchoolsV2(Request $request)
    {
        $schools = School::where("status_id", 1)
            ->orderBy("id", "desc")
            ->paginate(50);

        return $schools;
    }

    public function getCurriculumProgress(Request $request)
    {
        $this->validate($request, [
            "class_id" => "bail|required|exists:schools_classes,id",
        ]);

        $lessons = SchoolLesson::where("class_id", $request->class_id)
            ->with("video.subject")
            ->get();

        return response()->json(["lessons" => $lessons], 200);
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

    public function getStudents(Request $request)
    {
        $this->validate($request, [
            "school_id" => "required|exists:schools,id",
            "school_branch_id" =>
                "nullable|sometimes|integer|exists:schools_branches,id",
            "key" => "nullable|string",
        ]);

        $user = auth()->user();

        $supervisor = SchoolSupervisor::where("school_id", $request->school_id)
            ->where("supervisor_id", $user->id)
            ->first();

        $query = SchoolStudent::where("school_id", $request->school_id)
            ->with([
                "room.classs.level",
                "registrations.academicYear",
                "registrations.currency",
                "registrations.room.classs.level",
            ])
            ->orderBy("id", "desc");

        // If supervisor is of type 4 (with restriction)
        $query->when($request->filled("school_branch_id"), function ($q) use (
            $request
        ) {
            $q->whereHas("room.branch", function ($q2) use ($request) {
                $q2->where("school_branch_id", $request->school_branch_id);
            });
        });

        // Search by key (full name or national number)
        $query->when($request->filled("key"), function ($q) use ($request) {
            $q->where(function ($subQuery) use ($request) {
                $subQuery
                    ->where("full_name", "like", "%" . $request->key . "%")
                    ->orWhere(
                        "national_number",
                        "like",
                        "%" . $request->key . "%"
                    );
            });
        });

        $students = $query->paginate(50);

        $branches = SchoolBranch::where(
            "school_id",
            $request->school_id
        )->get();

        return response()->json([
            "students" => $students,
            "branches" => $branches,
        ]);
    }

    public function updateNationalNumber(Request $request)
    {
        // Validate the request
        $this->validate($request, [
            "student_id" => "bail|required|exists:schools_students,id",
            "national_number" => "required|integer",
            "pdf" => "nullable|mimes:pdf|max:10240", // Adjust the file size limit as needed
        ]);

        // Find the client by ID
        $client = SchoolStudent::find($request->student_id);

        // Update the q_id
        $client->national_number = $request->input("national_number");

        // Handle PDF upload and deletion
        if ($request->hasFile("pdf")) {
            // Delete the old PDF if it exists
            if ($client->file_url != "") {
                // Assuming the file is stored in the 'public' disk
                $oldFilePath =
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/ids/" .
                    $client->file_url;
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            // Upload the new PDF using normal PHP file handling
            $pdfFile = $request->file("pdf");
            $pdfName = time() . "." . $pdfFile->getClientOriginalExtension(); // Adjust the path as needed
            // Move the file to the desired location
            $pdfFile->move(
                "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/ids/",
                $pdfName
            );

            // Save the new file path in the database
            $client->file_url = $pdfName;
        }
        $client->save();
    }

    public function getSchedules(Request $request)
    {
        $this->validate($request, [
            "school_id" => "bail|required|exists:schools,id",
        ]);

        $schedules = SchoolBranchRoomSchedule::with([
            "days.subjects" => function ($query) {
                $query->where("status_id", "1")->orderBy("time", "asc");
            },
            "days.subjects.subject",
            "days.subjects.teacher",
            "days.day",
            "room.classs.level",
            "room.classs.subjects",
        ])
            ->where("school_id", $request->school_id)
            ->orderBy("id", "desc")
            ->paginate(50);

        $classes = SchoolBranchRoom::with(
            "classs.level",
            "classs.subjects"
        )->get();

        $teachers = SchoolTeacher::with("teacher")->get();

        return response()->json([
            "schedules" => $schedules,
            "classes" => $classes,
            "teachers" => $teachers,
        ]);
    }

    public function addSchoolBranchRoomScheduleSubject(Request $request)
    {
        $this->validate($request, [
            "school_branch_room_schedule_day_id" =>
                "bail|required|exists:schools_branches_rooms_schedules_days,id",
            "subject_id" => "bail|required|exists:subjects,id",
            "teacher_id" => "bail|required|exists:schools_teachers,id",
            "duration" => "required",
            "time" => "required",
        ]);

        $scheduleSubject = new SchoolBranchRoomScheduleSubject();
        $scheduleSubject->school_branch_room_schedule_day_id =
            $request->school_branch_room_schedule_day_id;
        $scheduleSubject->subject_id = $request->subject_id;
        $scheduleSubject->teacher_id = $request->teacher_id;
        $scheduleSubject->duration = $request->duration;
        $scheduleSubject->time = $request->time;

        $scheduleSubject->save();

        return response()->json($scheduleSubject, 201);
    }
    public function deleteSchoolBranchRoomScheduleSubject(Request $request)
    {
        $this->validate($request, [
            "school_branch_room_schedule_subject_id" =>
                "bail|required|exists:schools_branches_rooms_schedules_subjects,id",
        ]);

        $subject = SchoolBranchRoomScheduleSubject::find(
            $request->school_branch_room_schedule_subject_id
        );
        $subject->status_id = "2";
        $subject->save();
    }

    public function editSchoolBranchRoomScheduleSubject(Request $request)
    {
        $this->validate($request, [
            "school_branch_room_schedule_subject_id" =>
                "bail|required|exists:schools_branches_rooms_schedules_subjects,id",
        ]);

        $subject = SchoolBranchRoomScheduleSubject::find(
            $request->school_branch_room_schedule_subject_id
        );
        $subject->subject_id = $request->subject_id;
        $subject->teacher_id = $request->teacher_id;
        $subject->duration = $request->duration;
        $subject->time = $request->time;
        $subject->save();
    }

    public function addSchedule(Request $request)
    {
        $this->validate($request, [
            "school_id" => "required|exists:schools,id",
            "school_branch_room_id" =>
                "required|exists:schools_branches_rooms,id",
        ]);

        $existingSchedule = SchoolBranchRoomSchedule::where(
            "school_id",
            $request->school_id
        )
            ->where("school_branch_room_id", $request->school_branch_room_id)
            ->first();

        if ($existingSchedule) {
            return response()->json(
                ["message" => "Schedule already exists for this branch room."],
                400
            );
        }

        DB::beginTransaction();

        try {
            $schedule = new SchoolBranchRoomSchedule();
            $schedule->school_id = $request->school_id;
            $schedule->school_branch_room_id = $request->school_branch_room_id;
            $schedule->save();

            $days = [];
            for ($i = 1; $i <= 7; $i++) {
                $days[] = [
                    "school_branch_room_schedule_id" => $schedule->id,
                    "day_id" => $i,
                ];
            }

            SchoolBranchRoomScheduleDay::insert($days);

            DB::commit();

            return response()->json(
                ["message" => "Schedule created successfully."],
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                ["message" => "An error occurred: " . $e->getMessage()],
                500
            );
        }
    }

    public function sendNotification(Request $request)
    {
        $this->validate($request, [
            "school_id" => "required|integer|exists:schools,id",
            "school_notification_type_id" => "required|integer|in:1,2,3,4",
            "notification" => "required|string|max:900",
            "school_student_id" =>
                "nullable|integer|exists:schools_students,id",
            "school_branch_room_id" =>
                "nullable|integer|exists:schools_branches_rooms,id",
        ]);

        $user = auth()->user();

        $supervisor = SchoolSupervisor::where("school_id", $request->school_id)
            ->where("supervisor_id", $user->id)
            ->first();
        $academicYear = AcademicYear::orderBy("id", "desc")->first();

        DB::beginTransaction();

        try {
            $notification = SchoolNotification::create([
                "school_id" => $request["school_id"],
                "school_notification_type_id" =>
                    $request["school_notification_type_id"],
                "school_supervisor_id" => $supervisor->id,
                "academic_year_id" => $academicYear->id,
                "notification" => $request["notification"],
            ]);

            if (
                $notification->school_notification_type_id == 2 &&
                !empty($request["school_branch_room_id"])
            ) {
                SchoolNotificationBranchRoom::create([
                    "school_notification_id" => $notification->id,
                    "school_branch_room_id" =>
                        $request["school_branch_room_id"],
                ]);
            } elseif (
                ($notification->school_notification_type_id == 3 ||
                    $notification->school_notification_type_id == 4) &&
                !empty($request["school_student_id"])
            ) {
                SchoolNotificationStudent::create([
                    "school_notification_id" => $notification->id,
                    "school_student_id" => $request["school_student_id"],
                ]);
            }

            DB::commit();

            // ✅ WhatsApp Notification Logic (after successful commit)
            try {
                $school = School::findOrFail($request->school_id);
                $message =
                    "إشعار من " .
                    $school->ar_name .
                    "\n\n" .
                    $request->notification;

                $room;
                $students = SchoolStudent::where(
                    "id",
                    $request->school_student_id
                )->get();

                if ($notification->school_notification_type_id == 1) {
                    $room = "school_" . $request->school_id;
                } elseif ($notification->school_notification_type_id == 2) {
                    $room = "room_" . $request->school_branch_room_id;
                } else {
                    $room = "student_" . $students[0]->student_id;
                }

                if ($notification->school_notification_type_id > 2) {
                    $reciver = User::find($students[0]->student_id);
                    $this->notification->sendSinglFullAppNotification(
                        $reciver->firebase,
                        "pushNotification",
                        [
                            "type" => "8",
                            "room" => $room,
                            "title" => $school->ar_name,
                            "image" => $school->image,
                            "body" => $request->notification,
                            "notification" => $notification,
                        ]
                    );
                } else {
                    $this->notification->sendGroupFullAppNotification(
                        $room,
                        "pushNotification",
                        [
                            "type" => "8",
                            "room" => $room,
                            "title" => $school->ar_name,
                            "image" => $school->image,
                            "body" => $request->notification,
                            "notification" => $notification,
                        ]
                    );
                }

                // Fetch students depending on notification type
                if ($request->school_notification_type_id == 1) {
                    $students = SchoolStudent::where(
                        "school_id",
                        $request->school_id
                    )->get();
                } elseif ($request->school_notification_type_id == 2) {
                    $students = SchoolStudent::where(
                        "school_branch_room_id",
                        $request->school_branch_room_id
                    )->get();
                } else {
                    $students = SchoolStudent::where(
                        "school_id",
                        $request->school_id
                    )->get(); // fallback
                }

                // Extract phone numbers
                $phoneNumbers = collect($students)
                    ->pluck(
                        $request->school_notification_type_id == 4
                            ? "parent_whatsapp_mobile_number"
                            : "student_whatsapp_mobile_number"
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                // Send in one request
                $this->notification->requestSocket(
                    [
                        "instanceId" => $school->whatsapp_instance_id,
                        "token" => $school->whatsapp_token,
                        "phoneNumbers" => $phoneNumbers,
                        "message" => $message,
                    ],
                    "sendBulkWhatsAppMessages"
                );
            } catch (\Exception $e) {
                \Log::error(
                    "Failed to send WhatsApp message: " . $e->getMessage()
                );
            }

            return response()->json(
                [
                    "message" => "Notification sent successfully",
                    "notification_id" => $notification->id,
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    "message" => "Error sending notification",
                    "error" => $e->getMessage(),
                ],
                500
            );
        }
    }

    public function deleteNotification(Request $request)
    {
        $this->validate($request, [
            "school_notification_id" =>
                "required|integer|exists:schools_notifications,id",
        ]);

        DB::beginTransaction();

        try {
            $notification = SchoolNotification::findOrFail(
                $request->school_notification_id
            );

            // Delete related rows (if exist)
            SchoolNotificationBranchRoom::where(
                "school_notification_id",
                $notification->id
            )->delete();
            SchoolNotificationStudent::where(
                "school_notification_id",
                $notification->id
            )->delete();

            // Delete the notification itself
            $notification->delete();

            DB::commit();

            return response()->json(
                [
                    "message" => "Notification deleted successfully",
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    "message" => "Error deleting notification",
                    "error" => $e->getMessage(),
                ],
                500
            );
        }
    }

    public function editNotification(Request $request)
    {
        $this->validate($request, [
            "school_notification_id" =>
                "required|integer|exists:schools_notifications,id",
            "notification" => "required|string|max:900",
            "school_notification_type_id" => "required|integer|in:1,2,3,4",
            "school_branch_room_id" =>
                "nullable|integer|exists:schools_branches_rooms,id",
            "school_student_id" =>
                "nullable|integer|exists:schools_students,id",
        ]);

        DB::beginTransaction();

        try {
            $notification = SchoolNotification::findOrFail(
                $request->school_notification_id
            );
            $oldType = $notification->school_notification_type_id;

            // Update notification and type
            $notification->update([
                "notification" => $request->notification,
                "school_notification_type_id" =>
                    $request->school_notification_type_id,
            ]);

            // Clear old relations if type changed
            if ($oldType !== $request->school_notification_type_id) {
                SchoolNotificationBranchRoom::where(
                    "school_notification_id",
                    $notification->id
                )->delete();
                SchoolNotificationStudent::where(
                    "school_notification_id",
                    $notification->id
                )->delete();
            }

            // Add new relation based on new type
            if (
                $request->school_notification_type_id == 2 &&
                $request->filled("school_branch_room_id")
            ) {
                SchoolNotificationBranchRoom::create([
                    "school_notification_id" => $notification->id,
                    "school_branch_room_id" => $request->school_branch_room_id,
                ]);
            } elseif (
                in_array($request->school_notification_type_id, [3, 4]) &&
                $request->filled("school_student_id")
            ) {
                SchoolNotificationStudent::create([
                    "school_notification_id" => $notification->id,
                    "school_student_id" => $request->school_student_id,
                ]);
            }

            DB::commit();

            // ✅ Send WhatsApp message
            try {
                $school = School::findOrFail($notification->school_id);
                $message =
                    "تعديل لإشعار من " .
                    $school->ar_name .
                    "\n\n" .
                    $request->notification;

                if ($request->school_notification_type_id == 1) {
                    $students = SchoolStudent::where(
                        "school_id",
                        $notification->school_id
                    )->get();
                } elseif ($request->school_notification_type_id == 2) {
                    $students = SchoolStudent::where(
                        "school_branch_room_id",
                        $request->school_branch_room_id
                    )->get();
                } else {
                    $students = SchoolStudent::where(
                        "id",
                        $request->school_student_id
                    )->get();
                }

                $phoneNumbers =
                    $request->school_notification_type_id == 4
                        ? $students
                            ->pluck("parent_whatsapp_mobile_number")
                            ->filter()
                            ->unique()
                            ->toArray()
                        : $students
                            ->pluck("student_whatsapp_mobile_number")
                            ->filter()
                            ->unique()
                            ->toArray();

                $this->notification->sendBulkWhatsAppMessages(
                    $school->whatsapp_instance_id,
                    $school->whatsapp_token,
                    $phoneNumbers,
                    $message
                );
            } catch (\Exception $e) {
                \Log::error(
                    "Failed to resend WhatsApp message: " . $e->getMessage()
                );
            }

            return response()->json(
                [
                    "message" =>
                        "Notification updated and WhatsApp message resent",
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    "message" => "Error updating notification",
                    "error" => $e->getMessage(),
                ],
                500
            );
        }
    }

    public function getSchoolCosts(Request $request)
    {
        $this->validate($request, [
            "school_id" => "bail|required|exists:schools,id",
            "school_branch_id" => "bail|nullable",
        ]);

        $costTypes = SchoolCostType::get();
        $currencies = Currency::get();
        $branches = SchoolBranch::where(
            "school_id",
            $request->school_id
        )->get();

        $academicYear = AcademicYear::orderBy("id", "desc")->first();
        $user = auth()->user();
        $supervisor = SchoolSupervisor::where("school_id", $request->school_id)
            ->where("supervisor_id", $user->id)
            ->first();
        $costs;
        if ($supervisor->school_supervisor_type_id == 4) {
            if ($request->school_branch_id == "-1") {
                $costs = SchoolCost::where("school_id", $request->school_id)
                    ->where("academic_year_id", $academicYear->id)
                    ->doesntHave("branchId")
                    ->with(["schoolCostType", "employee", "currency", "branch"])
                    ->when($request->key, function ($query) use ($request) {
                        // Apply filters when `key` is provided
                        $query
                            ->where("id", "like", "%" . $request->key . "%")
                            ->orWhereHas("schoolCostType", function (
                                $query
                            ) use ($request) {
                                $query->where(
                                    "ar_name",
                                    "like",
                                    "%" . $request->key . "%"
                                );
                            })
                            ->orWhere(
                                "comment",
                                "like",
                                "%" . $request->key . "%"
                            );
                    })
                    ->orderBy("id", "desc")
                    ->paginate(50);
            } elseif ($request->school_branch_id == "") {
                $costs = SchoolCost::where("school_id", $request->school_id)
                    ->where("academic_year_id", $academicYear->id)
                    ->with(["schoolCostType", "employee", "currency", "branch"])
                    ->when($request->key, function ($query) use ($request) {
                        // Apply filters when `key` is provided
                        $query
                            ->where("id", "like", "%" . $request->key . "%")
                            ->orWhereHas("schoolCostType", function (
                                $query
                            ) use ($request) {
                                $query->where(
                                    "ar_name",
                                    "like",
                                    "%" . $request->key . "%"
                                );
                            })
                            ->orWhere(
                                "comment",
                                "like",
                                "%" . $request->key . "%"
                            );
                    })
                    ->orderBy("id", "desc")
                    ->paginate(50);
            } else {
                $costs = SchoolCost::where("school_id", $request->school_id)
                    ->where("academic_year_id", $academicYear->id)
                    ->whereHas("branchId", function ($query) use ($request) {
                        $query->where(
                            "school_branch_id",
                            $request->school_branch_id
                        );
                    })
                    ->with(["schoolCostType", "employee", "currency", "branch"])
                    ->when($request->key, function ($query) use ($request) {
                        $query
                            ->where("id", "like", "%" . $request->key . "%")
                            ->orWhereHas("schoolCostType", function (
                                $query
                            ) use ($request) {
                                $query->where(
                                    "ar_name",
                                    "like",
                                    "%" . $request->key . "%"
                                );
                            })
                            ->orWhere(
                                "comment",
                                "like",
                                "%" . $request->key . "%"
                            );
                    })
                    ->orderBy("id", "desc")
                    ->paginate(50);
            }
        } elseif ($supervisor->school_supervisor_type_id == 3) {
            $costs = SchoolCost::where("school_id", $request->school_id)
                ->where("academic_year_id", $academicYear->id)
                ->whereHas("branchId", function ($query) use ($request) {
                    $query->where(
                        "school_branch_id",
                        $request->school_branch_id
                    );
                })
                ->with(["schoolCostType", "employee", "currency", "branch"])
                ->when($request->key, function ($query) use ($request) {
                    $query
                        ->where("id", "like", "%" . $request->key . "%")
                        ->orWhereHas("schoolCostType", function ($query) use (
                            $request
                        ) {
                            $query->where(
                                "ar_name",
                                "like",
                                "%" . $request->key . "%"
                            );
                        })
                        ->orWhere("comment", "like", "%" . $request->key . "%");
                })
                ->orderBy("id", "desc")
                ->paginate(50);
        } else {
            $costs = SchoolCost::where("school_id", $request->school_id)
                ->where("academic_year_id", $academicYear->id)
                ->where("employee_id", $supervisor->id)
                ->whereHas("branchId", function ($query) use ($request) {
                    $query->where(
                        "school_branch_id",
                        $request->school_branch_id
                    );
                })
                ->with(["schoolCostType", "employee", "currency", "branch"])
                ->when($request->key, function ($query) use ($request) {
                    $query
                        ->where("id", "like", "%" . $request->key . "%")
                        ->orWhereHas("schoolCostType", function ($query) use (
                            $request
                        ) {
                            $query->where(
                                "ar_name",
                                "like",
                                "%" . $request->key . "%"
                            );
                        })
                        ->orWhere("comment", "like", "%" . $request->key . "%");
                })
                ->orderBy("id", "desc")
                ->paginate(50);
        }

        return response()->json(
            [
                "costs" => $costs,
                "costs_types" => $costTypes,
                "currencies" => $currencies,
                "branches" => $branches,
            ],
            200
        );
    }

    public function getSupervisors(Request $request)
    {
        $this->validate($request, [
            "school_id" => "bail|required|exists:schools,id",
            "school_branch_id" =>
                "nullable|sometimes|integer|exists:schools_branches,id",
            "key" => "bail|nullable",
        ]);

        $types = SchoolSupervisorType::where("id", "!=", 4)->get();

        $branches = SchoolBranch::where(
            "school_id",
            $request->school_id
        )->get();

        $user = auth()->user();

        $supervisor = SchoolSupervisor::where("school_id", $request->school_id)
            ->where("supervisor_id", $user->id)
            ->first();

        $supervisors;
        if ($supervisor->school_supervisor_type_id == 4) {
            if ($request->school_branch_id == "") {
                $supervisors = SchoolSupervisor::where(
                    "school_id",
                    $request->school_id
                )
                    ->where("school_supervisor_type_id", "!=", 4)
                    ->with("type", "data", "status", "branch.branch")
                    ->orderBy("id", "desc")
                    ->paginate(50);
            } else {
                $supervisors = SchoolSupervisor::where(
                    "school_id",
                    $request->school_id
                )
                    ->whereHas("branch", function ($query) use ($request) {
                        $query->where(
                            "school_branch_id",
                            $request->school_branch_id
                        );
                    })
                    ->where("school_supervisor_type_id", "!=", 4)
                    ->with("type", "data", "status", "branch.branch")
                    ->orderBy("id", "desc")
                    ->paginate(50);
            }
        } else {
            $supervisors = SchoolSupervisor::where(
                "school_id",
                $request->school_id
            )
                ->where("school_supervisor_type_id", "!=", 4)
                ->where("id", "!=", $supervisor->id)
                ->whereHas("branch", function ($query) use ($supervisor) {
                    $query->where(
                        "school_branch_id",
                        $supervisor->branch->school_branch_id
                    );
                })
                ->with("type", "data", "status", "branch.branch")
                ->orderBy("id", "desc")
                ->paginate(50);
        }

        return response()->json(
            [
                "supervisors" => $supervisors,
                "types" => $types,
                "branches" => $branches,
            ],
            200
        );
    }

    public function addSchoolCost(Request $request)
    {
        // Validate the request
        $this->validate($request, [
            "school_id" => "required|exists:schools,id",
            "school_branch_id" =>
                "nullable|sometimes|integer|exists:schools_branches,id",
            "school_cost_type_id" => "required|exists:schools_costs_types,id",
            "currency_id" => "required|exists:currencies,id",
            "cost" => "required",
        ]);

        // Start a transaction
        DB::beginTransaction();

        try {
            $user = auth()
                ->user()
                ->load("schoolSupervisor")->schoolSupervisor;
            $academicYear = AcademicYear::orderBy("id", "desc")->first();

            // Create SchoolCost
            $schoolCost = SchoolCost::create([
                "school_id" => $request->school_id,
                "academic_year_id" => $academicYear->id,
                "currency_id" => $request->currency_id,
                "school_cost_type_id" => $request->school_cost_type_id,
                "cost" => $request->cost,
                "comment" => $request->comment ?? "-",
                "employee_id" => $user->id,
            ]);

            // Create SchoolBranchCost if branch_id is provided
            if (!empty($request->school_branch_id)) {
                SchoolBranchCost::create([
                    "school_cost_id" => $schoolCost->id,
                    "school_branch_id" => $request->school_branch_id,
                ]);
            }

            // Commit transaction
            DB::commit();

            return response()->json("", 201);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            return response()->json(
                ["error" => "Failed to save school cost"],
                500
            );
        }
    }

    public function editSchoolCost(Request $request)
    {
        // Validate the request
        $this->validate($request, [
            "school_cost_id" => "required|exists:schools_costs,id",
            "school_branch_id" =>
                "nullable|sometimes|integer|exists:schools_branches,id",
            "school_cost_type_id" => "required|exists:schools_costs_types,id",
            "currency_id" => "required|exists:currencies,id",
            "cost" => "required",
            "comment" => "required",
        ]);

        DB::beginTransaction();

        try {
            $schoolCost = SchoolCost::findOrFail($request->school_cost_id);

            $schoolCost->cost = $request->cost;
            $schoolCost->comment = $request->comment;
            $schoolCost->school_cost_type_id = $request->school_cost_type_id;
            $schoolCost->currency_id = $request->currency_id;
            $schoolCost->save();

            // Handle SchoolBranchCost logic
            $branchRelation = SchoolBranchCost::where(
                "school_cost_id",
                $schoolCost->id
            )->first();

            if (empty($request->school_branch_id)) {
                // Delete if exists
                if ($branchRelation) {
                    $branchRelation->delete();
                }
            } else {
                if ($branchRelation) {
                    // Update existing
                    $branchRelation->school_branch_id =
                        $request->school_branch_id;
                    $branchRelation->save();
                } else {
                    // Create new
                    SchoolBranchCost::create([
                        "school_cost_id" => $schoolCost->id,
                        "school_branch_id" => $request->school_branch_id,
                    ]);
                }
            }

            DB::commit();

            return response()->json(
                ["message" => "School cost updated successfully"],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                ["error" => "Failed to update school cost"],
                500
            );
        }
    }

    public function getAttendances(Request $request)
    {
        $this->validate($request, [
            "date" => "bail|nullable",
            "school_id" => "bail|required|exists:schools,id",
            "school_branch_room_id" => "bail|nullable",
        ]);

        $classes = SchoolBranchRoom::whereHas("branch", function ($query) use (
            $request
        ) {
            $query->where("school_id", $request->school_id);
        })
            ->with(["classs.level"])
            ->get();

        $academicYear = AcademicYear::orderBy("id", "desc")->first();

        $students = SchoolStudent::where(
            "school_branch_room_id",
            $request->school_branch_room_id == ""
                ? $classes[0]->id
                : $request->school_branch_room_id
        )
            ->with([
                "attendances" => function ($query) use ($academicYear) {
                    // Fetch all attendances for the specified academic year
                    $query->where("academic_year_id", $academicYear->id);
                },
            ])
            ->withCount([
                "attendances as attendance_count" => function ($query) use (
                    $request
                ) {
                    $query->whereDate(
                        "created_at",
                        $request->date == "" ? now() : $request->date
                    );
                },
            ])
            ->get();
        $academicYear->end_date = now()->toDateString();
        return response()->json(
            [
                "students" => $students,
                "date" =>
                    $request->date == ""
                        ? now()->toDateString()
                        : $request->date,
                "classes" => $classes,
                "academic_year" => $academicYear,
                "this_month" => Carbon::now()->month,
                "this_day" => Carbon::now()->day,
            ],
            200
        );
    }

    public function editAttendance(Request $request)
    {
        $this->validate($request, [
            "attendances" => "array|bail|nullable",
            "date" => "bail|nullable",
            "school_branch_room_id" =>
                "bail|required|exists:schools_classes,id",
        ]);

        DB::beginTransaction();

        SchoolStudentAttendance::where(
            "school_branch_room_id",
            $request->school_branch_room_id
        )
            ->whereDate(
                "created_at",
                $request->date == "" ? now() : $request->date
            )
            ->delete();

        // Extract the attendances array from the request
        $attendances = $request->input("attendances");

        $academicYear = AcademicYear::orderBy("id", "desc")->first();

        $dataToInsert = [];
        foreach ($attendances as $attendance) {
            if ($request->date == "") {
                $dataToInsert[] = [
                    "school_branch_room_id" =>
                        $attendance["school_branch_room_id"],
                    "school_student_id" => $attendance["school_student_id"],
                    "academic_year_id" => $academicYear->id,
                ];
            } else {
                $dataToInsert[] = [
                    "school_branch_room_id" =>
                        $attendance["school_branch_room_id"],
                    "school_student_id" => $attendance["school_student_id"],
                    "created_at" => $request->date,
                    "academic_year_id" => $academicYear->id,
                ];
            }
        }

        $flag = SchoolStudentAttendance::insert($dataToInsert);
        if (!$flag) {
            DB::rollBack();
            return response()->json("", 500);
        }

        DB::commit();
        return response()->json("", 200);
    }

    public function editRegistration(Request $request)
    {
        $this->validate($request, [
            "school_registration_id" =>
                "required|exists:schools_registrations,id",
            "school_student_id" => "required|exists:schools_students,id",
            "school_registration_type_id" =>
                "required|exists:schools_registrations_types,id",
            "school_branch_room_id" =>
                "required|exists:schools_branches_rooms,id",
            "currency_id" => "required|exists:currencies,id",
            "registration_fee" => "required|numeric|min:0",
        ]);

        $registration = SchoolRegistration::findOrFail(
            $request->school_registration_id
        );

        $registration->student_id = $request->school_student_id;
        $registration->school_registration_type_id =
            $request->school_registration_type_id;
        $registration->school_branch_room_id = $request->school_branch_room_id;
        $registration->currency_id = $request->currency_id;
        $registration->registration_fee = $request->registration_fee;

        $registration->save();

        return response()->json(
            [
                "message" => "Registration updated successfully.",
            ],
            200
        );
    }

    public function registerNewStudent(Request $request)
    {
        // Validate the incoming request
        $this->validate($request, [
            "full_name" => "bail|required|string|max:500",
            "mother_full_name" => "bail|required|string|max:500",
            "gender" => "bail|required|integer|in:1,2", // Assuming 1: Male, 2: Female
            "birthday" => "bail|required|date",
            "parent_mobile_number" => "bail|required|string|max:15",
            "parent_whatsapp_mobile_number" => "bail|required|string|max:15",
            "student_whatsapp_mobile_number" => "bail|required|string|max:15",
            "national_number" => "bail|required|string|max:100",
            "country_id" => "bail|required|integer|exists:countries,id",
            "state_id" => "bail|required|integer|exists:states,id",
            "religion_id" => "bail|required|integer|exists:religions,id",
            "school_branch_room_id" =>
                "bail|required|integer|exists:schools_branches_rooms,id",
            "school_id" => "bail|required|exists:schools,id",
            "school_registration_type_id" =>
                "bail|required|exists:schools_registrations_types,id",
            "currency_id" => "bail|required|integer|exists:currencies,id",
            "fee" => "bail|required|integer|min:1",
            "first_payment_amount" => "bail|nullable|integer|min:0",
            "file_url" => "bail|nullable|file|mimes:jpeg,png,pdf,jpg|max:10240", // Validate file upload
        ]);

        $supervisor = auth()
            ->user()
            ->load("schoolSupervisor")->schoolSupervisor;

        DB::beginTransaction();
        try {
            // Step 1: Get the latest academic year
            $academicYear = AcademicYear::orderBy("id", "desc")->first();

            // If no academic year is found, return an error response
            if (!$academicYear) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "No academic year found.",
                    ],
                    404
                );
            }

            // Handle file upload for National ID
            $fileUrl = null; // Default to null
            if ($request->hasFile("file_url")) {
                $fileName =
                    uniqid() . "." . $request->file("file_url")->extension(); // Generate unique filename
                // Move the file to the directory
                if (
                    !$request->file("file_url")->move(
                        "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/national_ids/", // Update the path for national ID
                        $fileName
                    )
                ) {
                    // Rollback if file upload fails
                    DB::rollBack();
                    return response()->json(
                        ["error" => "Failed to upload national ID file."],
                        500
                    );
                }

                $fileUrl = "national_ids/" . $fileName; // Save just the filename in DB, not full path
            }

            // Step 2: Insert the student into `schools_students`
            $student = SchoolStudent::create([
                "school_id" => $request->school_id,
                "full_name" => $request->full_name,
                "mother_full_name" => $request->mother_full_name,
                "gender" => $request->gender,
                "birthday" => $request->birthday,
                "parent_mobile_number" => $request->parent_mobile_number,
                "parent_whatsapp_mobile_number" =>
                    $request->parent_whatsapp_mobile_number,
                "student_whatsapp_mobile_number" =>
                    $request->student_whatsapp_mobile_number,
                "national_number" => $request->national_number,
                "country_id" => $request->country_id,
                "state_id" => $request->state_id,
                "religion_id" => $request->religion_id,
                "school_branch_room_id" => $request->school_branch_room_id,
                "file_url" => $fileUrl, // Save the file URL in the database
            ]);

            // Step 3: Insert into `schools_subscriptions`
            $registration = SchoolRegistration::create([
                "school_id" => $request->school_id,
                "academic_year_id" => $academicYear->id, // Using the latest academic year
                "school_branch_room_id" => $request->school_branch_room_id,
                "student_id" => $student->id,
                "school_registration_type_id" =>
                    $request->school_registration_type_id,
                "currency_id" => $request->currency_id,
                "registration_fee" => $request->fee,
                "supervisor_id" => $supervisor->id,
            ]);

            // Step 4: If first_payment_amount is provided, insert into `schools_registrations_installments`
            if ($request->first_payment_amount !== null) {
                SchoolRegistrationInstallment::create([
                    "school_registration_id" => $registration->id,
                    "supervisor_id" => $supervisor->id,
                    "amount" => $request->first_payment_amount,
                ]);
            }

            // Commit the transaction
            DB::commit();

            return response()->json(
                [
                    "status" => "success",
                    "message" => "Student registered successfully",
                    "student" => $student,
                ],
                201
            );
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();
            return response()->json(
                ["status" => "error", "message" => $e->getMessage()],
                500
            );
        }
    }

    public function registerExistingStudent(Request $request)
    {
        // Validate the incoming request
        $this->validate($request, [
            "student_id" => "bail|required|integer|exists:schools_students,id", // Ensure student exists
            "school_id" => "bail|required|exists:schools,id",
            "school_registration_type_id" =>
                "bail|required|exists:schools_registrations_types,id",
            "currency_id" => "bail|required|integer|exists:currencies,id",
            "fee" => "bail|required|integer|min:1",
            "first_payment_amount" => "bail|nullable|integer|min:0",
        ]);

        $supervisor = auth()
            ->user()
            ->load("schoolSupervisor")->schoolSupervisor;

        DB::beginTransaction();
        try {
            // Step 1: Get the latest academic year
            $academicYear = AcademicYear::orderBy("id", "desc")->first();

            // If no academic year is found, return an error response
            if (!$academicYear) {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "No academic year found.",
                    ],
                    404
                );
            }

            // Step 3: Insert into `schools_subscriptions` (If the student is already enrolled in this academic year, handle that logic as needed)
            $registration = SchoolRegistration::create([
                "school_id" => $request->school_id,
                "academic_year_id" => $academicYear->id, // Using the latest academic year
                "school_branch_room_id" => $request->school_branch_room_id,
                "student_id" => $request->student_id,
                "school_registration_type_id" =>
                    $request->school_registration_type_id,
                "currency_id" => $request->currency_id,
                "registration_fee" => $request->fee,
                "supervisor_id" => $supervisor->id,
            ]);

            // Step 4: If first_payment_amount is provided, insert into `schools_registrations_installments`
            if ($request->first_payment_amount !== "") {
                SchoolRegistrationInstallment::create([
                    "school_registration_id" => $registration->id,
                    "supervisor_id" => $supervisor->id,
                    "amount" => $request->first_payment_amount,
                ]);
            }

            // Commit the transaction
            DB::commit();

            return response()->json(
                [
                    "status" => "success",
                    "message" => "Student registered successfully",
                ],
                201
            );
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();
            return response()->json(
                ["status" => "error", "message" => $e->getMessage()],
                500
            );
        }
    }

    public function addSchoolInstallment(Request $request)
    {
        $this->validate($request, [
            "school_registration_id" =>
                "required|exists:schools_registrations,id",
            "amount" => "required|numeric|min:1",
        ]);

        $supervisor = auth()
            ->user()
            ->load("schoolSupervisor")->schoolSupervisor;

        if (!$supervisor) {
            return response()->json(
                ["error" => "Unauthorized or Supervisor not found"],
                403
            );
        }

        $installment = SchoolRegistrationInstallment::create([
            "school_registration_id" => $request->school_registration_id,
            "amount" => $request->amount,
            "supervisor_id" => $supervisor->id,
        ]);

        $installment->supervisor = $installment->load("supervisor");
        $installment->created_at = now();

        return response()->json(
            [
                "message" => "Installment added successfully",
                "installment" => $installment,
            ],
            201
        );
    }

    public function editSchoolInstallment(Request $request)
    {
        $this->validate($request, [
            "id" => "required|exists:schools_registrations_installments,id",
            "amount" => "required|numeric|min:1",
        ]);

        $installment = SchoolRegistrationInstallment::with("supervisor")->find(
            $request->id
        );

        if (!$installment) {
            return response()->json(["error" => "Installment not found"], 404);
        }

        $installment->update(["amount" => $request->amount]);

        return response()->json(
            [
                "message" => "Installment updated successfully",
                "installment" => $installment,
            ],
            200
        );
    }

    public function deleteSchoolInstallment(Request $request)
    {
        $this->validate($request, [
            "id" => "required|exists:schools_registrations_installments,id",
        ]);

        $installment = SchoolRegistrationInstallment::find($request->id);

        if (!$installment) {
            return response()->json(["error" => "Installment not found"], 404);
        }

        $installment->delete();

        return response()->json(
            [
                "message" => "Installment deleted successfully",
                "installment" => $installment,
            ],
            200
        );
    }
}
