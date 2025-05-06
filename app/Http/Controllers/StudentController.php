<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Complaint;
use App\Models\Level;
use App\Models\NotePayment;
use App\Models\Payment;
use App\Models\Result;
use App\Models\ResultAnswer;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\StudentStudentClass;
use App\Models\User;
use App\Models\MBookRequest;
use App\Models\Subscription;
use App\Models\SubscriptionLiveClass;
use Illuminate\Support\Facades\Log;
use DateTime;
use Carbon\Carbon;
use DB;
use App\Models\Withdrawal;

class StudentController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware("auth:api", [
            "except" => [
                "getLevels",
                "getLevelsV2",
                "subscribe",
                "updateLobby",
                "getTop10V2"
            ],
        ]);
        $this->notification = new Notification();
    }

    private $notification;

    public function subscribe(Request $request)
    {
        if ($request->event["type"] == "EXPIRATION") {
            $subscription = Subscription::where(
                "aliases",
                $request->event["aliases"][0]
            )->first();
            $subscription->status_id = 2;
            $subscription->save();
            return response()->json("", 200);
        }
        // Convert milliseconds to seconds
        $seconds = $request->event["expiration_at_ms"] / 1000;

        // Create a DateTime object
        $date = new DateTime();
        $date->setTimestamp($seconds);

        // Format the date if needed
        $formattedDate = $date->format("Y-m-d H:i:s");

        $subscription = new Subscription();

        $subscription->subscription_package_id =
            $request->event["subscriber_attributes"]["subscription_package_id"][
                "value"
            ];
        $subscription->student_id =
            $request->event["subscriber_attributes"]["student_id"]["value"];
        $subscription->transaction_id = $request->event["transaction_id"];
        $subscription->aliases = $request->event["aliases"][0];
        $subscription->price = $request->event["price"];
        $subscription->expire_date =
            $request->event["subscriber_attributes"]["subscription_package_id"][
                "value"
            ] == "3"
                ? Carbon::now()
                : $formattedDate;
        $subscription->currency = $request->event["currency"];

        $subscription->save();

        if (
            $request->event["subscriber_attributes"]["subscription_package_id"][
                "value"
            ] == "3"
        ) {
            $subscriptionLiveClass = new SubscriptionLiveClass();

            $subscriptionLiveClass->subscription_id = $subscription->id;
            $subscriptionLiveClass->live_class_id =
                $request->event["subscriber_attributes"]["live_class_id"][
                    "value"
                ];
            $subscriptionLiveClass->student_id =
                $request->event["subscriber_attributes"]["student_id"]["value"];

            $subscriptionLiveClass->save();

            $flag = $this->notification->requestSocket(
                [
                    "room" =>
                        $request->event["subscriber_attributes"]["room"][
                            "value"
                        ],
                    "price" => $subscription->price / 2,
                ],
                "income"
            );
        }
    }

    public function getIncome(Request $request)
    {
        $user = auth()->user();
        $income = Subscription::where("subscription_package_id", "3")
            ->whereHas("classs", function ($query) use ($user) {
                $query->where("teacher_id", $user->id);
            })
            ->select("price") // Select the price column
            ->sum("price");
        return response()->json(["income" => $income / 2], 200);
    }

    public function changeName(Request $request)
    {
        $this->validate($request, [
            "name" => "bail|required|min:4",
        ]);

        $user = auth()->user();

        $flag = Student::find($user->id)->update(["name" => $request->name]);

        if ($flag) {
            return response()->json("", 200);
        } else {
            $this->logout();
            return response()->json("", 500);
        }
    }

    public function addComplaint(Request $request)
    {
        $this->validate($request, [
            "complaint" => "bail|required|min:20",
            "complaint_type_id" => "bail|required|exists:complaints_types,id",
        ]);

        $complaint = new Complaint();

        $complaint->user_id = auth()->user()->id;
        $complaint->complaint = $request->complaint;
        $complaint->complaint_type_id = $request->complaint_type_id;

        $flag = $complaint->save();

        if ($flag) {
            return response()->json("", 201);
        } else {
            return response()->json("", 500);
        }
    }

    public function selectAvatar(Request $request)
    {
        $this->validate($request, [
            "avatar" => "bail|required",
        ]);

        $user = auth()->user();
        $student = Student::find($user->id);

        $student->avatar = $request->avatar;

        $flag = $student->save();

        if ($flag) {
            return response()->json("", 200);
        } else {
            return response()->json("", 500);
        }
    }

    public function getLevels(Request $request)
    {
        $user = auth()->user();
        $levels = Level::with([
            "classes" => function ($query) {
                $query->where("status_id", 1)->has("subjects");
            },
            "classes.subjects.chapters.lessons",
            "classes.subjects.oldExams",
            "classes.subjects.notes",
            "classes.subjects.videos" => function ($query) {
                $query->orderBy("date", "asc"); // Order videos by date in descending order
            },
        ])->get();
        foreach ($levels as $level) {
            foreach ($level->classes as $class) {
                foreach ($class->subjects as $subject) {
                    $subscription = new Subscription();
                    $subscription->id = 29;
                    $subscription->class_id = $class->id;
                    $subscription->subscription_package_id = 1;
                    $subscription->subscription_status_id = 1;
                    $subject->subscription = $subscription;
                    $subject->price = $class->price;
                    $mBookRequest = new MBookRequest();
                    $mBookRequest->id = 29;
                    $mBookRequest->student_id = 27;
                    $mBookRequest->class_id = $class->id;
                    $mBookRequest->subscribe_package_id = 1;
                    $mBookRequest->mbook_request_status_id = 2;
                    $mBookRequest->pic = "1647275650252.png";
                    $mBookRequest->date = "2022-03-14 16:34:10";
                    $subject->mBook_request = $mBookRequest;
                    foreach ($subject->notes as $note) {
                        $note->payment = NotePayment::where(
                            "note_id",
                            $note->id
                        )
                            ->whereHas("payment", function ($qur) use ($user) {
                                $qur->where("student_id", "1");
                            })
                            ->orderBy("id", "desc")
                            ->first();
                    }
                }
            }
        }

        return response()->json(
            ["levels" => $levels],
            200,
            [],
            JSON_NUMERIC_CHECK
        );
    }

    public function getLevelsV2(Request $request)
    {
        $user = auth()->user();
        $levels = Level::with([
            "classes" => function ($query) {
                $query->where("status_id", 1)->has("subjects");
            },
            "classes.subjects.chapters.lessons",
            "classes.subjects.oldExams",
            "classes.subjects.notes",
            "classes.subjects.videos" => function ($query) {
                $query->orderBy("date", "asc"); // Order videos by date in descending order
            },
        ])->get();
        return response()->json(['levels' => $levels], 200);
    }

    public function getPayments(Request $request)
    {
        $user = auth()->user();
        $payments = Subscription::where("student_id", $user->id)
            ->orderBy("id", "desc")
            ->with(["status", "subscriptionPackage", "classs.teacher"])
            ->paginate(50);
        return response()->json($payments, 200, [], JSON_NUMERIC_CHECK);
    }

    public function getReports(Request $request)
    {
        $this->validate($request, [
            "date" => "bail|required",
        ]);

        $user = auth()->user();
        $report = new Result();

        $report->totalExams = Result::where("student_id", $user->id)
            ->whereMonth("date", $request->date)
            ->count();

        $report->totalQuestions = ResultAnswer::whereHas("result", function (
            $qur
        ) use ($request, $user) {
            $qur->where("student_id", $user->id)->whereMonth(
                "date",
                $request->date
            );
        })->count();

        $report->totalChapters = Chapter::whereHas(
            "lessons.resultAnswers.result",
            function ($qur) use ($request, $user) {
                $qur->where("student_id", $user->id)->whereMonth(
                    "date",
                    $request->date
                );
            }
        )->count();

        $report->totalLessons = Lesson::whereHas(
            "resultAnswers.result",
            function ($qur) use ($request, $user) {
                $qur->where("student_id", $user->id)->whereMonth(
                    "date",
                    $request->date
                );
            }
        )->count();

        $report->totalRightAnswers = ResultAnswer::whereHas("result", function (
            $qur
        ) use ($request, $user) {
            $qur->where("student_id", $user->id)->whereMonth(
                "date",
                $request->date
            );
        })
            ->where("is_true", 1)
            ->count();

        $report->totalRightAnswers = ResultAnswer::whereHas("result", function (
            $qur
        ) use ($request, $user) {
            $qur->where("student_id", $user->id)->whereMonth(
                "date",
                $request->date
            );
        })
            ->where("is_true", 1)
            ->count();

        $report->totalWrongAnswers = ResultAnswer::whereHas("result", function (
            $qur
        ) use ($request, $user) {
            $qur->where("student_id", $user->id)->whereMonth(
                "date",
                $request->date
            );
        })
            ->where("is_true", 0)
            ->count();

        $report->bestFiveSubjects = Subject::whereHas(
            "chapters.lessons.resultAnswers.result",
            function ($qur) use ($request, $user) {
                $qur->where("student_id", $user->id)->whereMonth(
                    "date",
                    $request->date
                );
            }
        )
            ->withCount([
                "chapters" => function ($query) {
                    $query->whereHas("lessons.resultAnswers.result");
                },
            ])
            ->orderBy("chapters_count", "DESC")
            ->limit(5)
            ->get();

        $report->worstFiveSubjects = Subject::whereHas(
            "chapters.lessons.resultAnswers.result",
            function ($qur) use ($request, $user) {
                $qur->where("student_id", $user->id)->whereMonth(
                    "date",
                    $request->date
                );
            }
        )
            ->withCount("chapters")
            ->orderBy("chapters_count", "ASC")
            ->limit(5)
            ->get();

        $report->bestFiveChapters = Chapter::whereHas(
            "lessons.resultAnswers.result",
            function ($qur) use ($request, $user) {
                $qur->where("student_id", $user->id)->whereMonth(
                    "date",
                    $request->date
                );
            }
        )
            ->withCount("lessons")
            ->orderBy("lessons_count", "DESC")
            ->limit(5)
            ->get();

        $report->worstFiveChapters = Chapter::whereHas(
            "lessons.resultAnswers.result",
            function ($qur) use ($request, $user) {
                $qur->where("student_id", $user->id)->whereMonth(
                    "date",
                    $request->date
                );
            }
        )
            ->withCount("lessons")
            ->orderBy("lessons_count", "ASC")
            ->limit(5)
            ->get();

        return response()->json($report, 200, [], JSON_NUMERIC_CHECK);
    }

    //Dashboard********************************************

    public function getStudents(Request $request)
    {
        if ($request->student_id) {
            $students = Student::where("id", $request->student_id)
                ->with("user")
                ->withCount("subscriptions")
                ->orderBy("id", "desc")
                ->paginate(10);
            return $students;
        } else {
            $students = Student::where(
                "name",
                "like",
                "%" . $request->student_name . "%"
            )
                ->with("user")
                ->withCount("subscriptions")
                ->orderBy("id", "desc")
                ->paginate(10);
            return $students;
        }
    }

    public function changeStudentStatus(Request $request)
    {
        $this->validate($request, [
            "status_id" => "bail|required|exists:status,id",
            "student_id" => "bail|required|exists:students,id",
        ]);

        $student = User::find($request->student_id);
        $student->status_id = $request->status_id;

        $flag = $student->save();

        if ($flag) {
            return response()->json("", 200);
        }
    }

    public function getWithdrawals(Request $request)
    {
        $user = auth()->user();

        $withdrawal = Withdrawal::where("teacher_id", $user->id)
            ->orderBy("id", "DESC")
            ->paginate(10 * $request->page_counter);

        return response()->json($withdrawal, 200);
    }

    public function updateLobby(Request $request)
    {
        Student::whereIn("id", $request->ids)->update([
            "lobby" => $request->lobby,
        ]);
    }

    public function updateStudentClass(Request $request)
    {
        // Validate the incoming request
        $this->validate($request, [
            "student_class_id" => "required|exists:students_classes,id",
        ]);

        $student = auth()
            ->user()
            ->load("student")->student;

        // Get the authenticated user
        $studentId = $student->id;

        // Use the upsert method to add or update the record
        StudentStudentClass::updateOrCreate(
            ["student_id" => $studentId],
            ["student_class_id" => $request->student_class_id]
        );

        $student->top10 = 0;
        $student->right_answers = 0;

        $student->save();

        return response()->json(
            [
                "message" => "Student class updated successfully.",
            ],
            200
        );
    }

    public function addResult(Request $request)
    {
        $this->validate($request, [
            "student_class_id" => "required|exists:students_classes,id",
        ]);

        $student = auth()->user();
        $studentId = $student->id;

        // Create a new result entry with current date
        Result::create([
            "student_class_id" => $request->student_class_id,
            "right_answers" => $request->right_answers,
            "student_id" => $studentId,
            "date" => now(),
        ]);

        // Update student's right answers
        $model = $student->load("student")->student;

        // Get top 10 students this month
        $topResults = Result::select(
            "student_id",
            DB::raw("SUM(right_answers) as total_right_answers")
        )
            ->where("student_class_id", $request->student_class_id)
            ->where("date", ">=", now()->startOfMonth())
            ->where("date", "<=", now()->endOfMonth())
            ->groupBy("student_id")
            ->orderBy("total_right_answers", "desc")
            ->take(10)
            ->get();

        $index = $topResults->search(function ($topResult) use ($studentId) {
            return $topResult->student_id == $studentId;
        });

        $position = $index !== false ? $index + 1 : 0;

        if ($position > 0 && $model->top10 !== $position) {
            // Reset previous top 10s
            Student::whereHas("classs", function ($query) use ($request) {
                $query->where("student_class_id", $request->student_class_id);
            })
                ->where("top10", ">", 0)
                ->update(["top10" => 0]);

            // Update top10 ranking and right_answers
            $studentIds = $topResults->pluck("student_id");
            $caseStatement = collect($studentIds)
                ->map(function ($id, $key) {
                    return "WHEN id = $id THEN " . ($key + 1);
                })
                ->implode(" ");

            // Create a case statement for right_answers
            $rightAnswersStatement = collect($topResults)
                ->map(function ($result) {
                    return "WHEN id = {$result->student_id} THEN {$result->total_right_answers}";
                })
                ->implode(" ");

            DB::table("students")
                ->whereIn("id", $studentIds)
                ->update([
                    "top10" => DB::raw("CASE $caseStatement END"),
                    "right_answers" => DB::raw(
                        "CASE $rightAnswersStatement END"
                    ),
                ]);
        }

        return response()->json(["message" => $topResults]);
    }
    
    public function addResultV2(Request $request)
    {
        $this->validate($request, [
            "student_class_id" => "required|exists:students_classes,id",
        ]);

        $student = auth()->user();
        $studentId = $student->id;

        // Create a new result entry with current date
        Result::create([
            "student_class_id" => $request->student_class_id,
            "right_answers" => $request->right_answers,
            "student_id" => $studentId,
            "date" => now(),
        ]);
    }

    public function getTop10(Request $request)
    {
        $this->validate($request, [
            "student_class_id" => "required|exists:students_classes,id",
        ]);

        $topResults = Student::whereHas("classs", function ($query) use (
            $request
        ) {
            $query->where("student_class_id", $request->student_class_id);
        })
            ->where("top10", ">", 0)
            ->orderBy("top10")
            ->limit(10)
            ->get();

        $me = Student::find(auth()->user()->id);

        return response()->json(["top10" => $topResults, "me" => $me]);
    }

    public function getTop10V2(Request $request)
    {
        $this->validate($request, [
            "student_class_id" => "required|exists:students_classes,id",
        ]);
        // Get the current date for filtering by this month
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Get the class_id from the request
        $classId = $request->student_class_id;

        // Get the top 10 students with the highest sum of right_answers for this month and class
        $topStudents = Result::where("student_class_id", $classId)
            ->whereYear("date", $currentYear)
            ->whereMonth("date", $currentMonth)
            ->select(
                "student_id",
                DB::raw("SUM(right_answers) as total_right_answers")
            )
            ->with('student')
            ->groupBy("student_id")
            ->orderByDesc("total_right_answers")
            ->take(10)
            ->get();

        // Get the sum of right_answers for the authenticated user in the given class for this month
        $userRightAnswers = Result::where("student_class_id", $classId)
            ->where("student_id", auth()->user()->id)
            ->whereYear("date", $currentYear)
            ->whereMonth("date", $currentMonth)
            ->sum("right_answers");

        // Return both the top students and the sum for the authenticated user
        return response()->json([
            "top_students" => $topStudents,
            "user_right_answers" => $userRightAnswers,
        ]);
    }
}
