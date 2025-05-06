<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Supervisor;
use App\Models\SupervisorType;
use App\Models\Student;
use App\Models\Cost;
use App\Models\Result;
use App\Models\Classs;
use App\Models\StudentExam;
use App\Models\Subscription;
use App\Models\Complaint;
use App\Models\Question;
use App\Models\WatchedVideo;
use App\Models\LudoStudent;
use DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SupervisorController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
      private $notification;
    public function __construct()
    {
        $this->middleware("auth:api");
        $this->notification = new Notification();
    }

    //Dashboard********************************************

    public function getSupervisors(Request $request)
    {
        if ($request->supervisor_id) {
            $supervisor = Supervisor::where("id", $request->supervisor_id)
                ->with("user", "supervisorType")
                ->withCount("questions")
                ->orderBy("id", "desc")
                ->paginate(10);
            return $supervisor;
        } else {
            $supervisor = Supervisor::where(
                "name",
                "like",
                "%" . $request->supervisor_name . "%"
            )
                ->with("user", "supervisorType")
                ->withCount("questions")
                ->orderBy("id", "desc")
                ->paginate(10);
            return $supervisor;
        }
    }

    public function changeSupervisorStatus(Request $request)
    {
        $this->validate($request, [
            "status_id" => "bail|required|exists:status,id",
            "supervisor_id" => "bail|required|exists:supervisors,id",
        ]);

        $supervisor = User::find($request->supervisor_id);
        $supervisor->status_id = $request->status_id;
        $supervisor->token = null;

        $flag = $supervisor->save();

        if ($flag) {
            return response()->json("", 200);
        }
    }

    public function addSupervisor(Request $request)
    {
        $this->validate($request, [
            "supervisor_phone" => "required|min:10|max:14",
            "supervisor_password" => "required|string|min:4",
            "supervisor_name" => "bail|required|min:4",
            "supervisor_type_id" => "bail|required|exists:supervisors_types,id",
        ]);

        DB::beginTransaction();

        $user = new User();
        $user->password = Hash::make($request->supervisor_password);
        $user->phone = $request->supervisor_phone;
        $user->credential = $request->supervisor_phone;
        $user->user_type_id = 2;

        $flag = $user->save();

        if ($flag) {
            $supervisor = new Supervisor();
            $supervisor->id = $user->id;
            $supervisor->supervisor_type_id = $request->supervisor_type_id;
            $supervisor->name = $request->supervisor_name;

            $flag = $supervisor->save();

            if ($flag) {
                DB::commit();
                return response()->json("", 201);
            } else {
                DB::rollBack();
                return response()->json("", 500);
            }
        } else {
            DB::rollBack();
            return response()->json("", 500);
        }
    }

    public function editSupervisor(Request $request)
    {
        $this->validate($request, [
            "supervisor_phone" => "bail|required",
            "supervisor_id" => "bail|required|exists:supervisors,id",
            "supervisor_name" => "bail|required|min:4",
            "supervisor_type_id" => "bail|required|exists:supervisors_types,id",
        ]);

        DB::beginTransaction();

        $user = User::find($request->supervisor_id);
        $user->phone = $request->supervisor_phone;

        $flag = $user->save();

        if ($flag) {
            $supervisor = Supervisor::find($request->supervisor_id);
            $supervisor->name = $request->supervisor_name;
            $supervisor->supervisor_type_id = $request->supervisor_type_id;

            $flag = $supervisor->save();

            if ($flag) {
                DB::commit();
                return response()->json("", 200);
            } else {
                DB::rollBack();
                return response()->json("", 500);
            }
        } else {
            DB::rollBack();
            return response()->json("", 500);
        }
    }

    public function getPayments(Request $request)
    {
        
        $payments;
        if($request->id != "") {
         $payments = Subscription::where("student_id", $request->id)
            ->with(
                "student",
                "classs",
                "subscriptionPackage",
                "subscriptionStatus"
            )
            ->orderBy("id", "DESC")
            ->paginate(50);   
        }
        else {
            $payments = Subscription::
            with(
                "student",
                "classs",
                "subscriptionPackage",
                "subscriptionStatus"
            )
            ->orderBy("id", "DESC")
            ->paginate(50);
        }

        return response()->json($payments, 200, [], JSON_NUMERIC_CHECK);
    }
    
    public function addSubscription(Request $request)
    {

            // Validate the incoming request
            $this->validate($request,[
                'student_id' => 'required|integer|exists:students,id', // assuming you have a students table
                'price' => 'required|numeric|min:0',
                'subscription_package_id' => 'required|integer|exists:subscriptions_packages,id', // assuming a subscription_packages table
                'transaction_id' => 'required|string|max:255',
            ]);

            // Determine expiration date based on subscription_package_id
            $expirationDays = $request->subscription_package_id == 2 ? 30 : 7;
            $expireDate = Carbon::now()->addDays($expirationDays);

            // Create the subscription
            $subscription = Subscription::create([
                'student_id' => $request->student_id,
                'price' => $request->price,
                'subscription_package_id' => $request->subscription_package_id,
                'transaction_id' => $request->transaction_id,
                'currency' => 'SD',
                'aliases' => '-1',
                'expire_date' => $expireDate,
            ]);

            // Commit the transaction

            // Return a success response
            return response()->json([
                'message' => 'Subscription added successfully',
                'data' => $subscription
            ], 201);

        
    }

    public function getReports(Request $request)
    {
        
        $response = $this->notification->requestSocket([],'getOnlinePlayers');
        $data = json_decode($response->getBody(), true);
        
        
        // Determine the duration from the request; default to 1 if not provided
        $duration = $request->duration;

        if ($duration == 1) {
        // Calculate based on today's date
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();
    } elseif ($duration == 2) {
        // Calculate based on the entire current month
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
    } elseif ($duration == 3) {
        // Calculate based on the entire current year
        $startDate = Carbon::now()->startOfYear();
        $endDate = Carbon::now()->endOfYear();
    } else {
        // Default to today's date if an invalid duration is provided
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();
    }

        // Total number of students
        $students = Student::count();

        // Students registered in the specified duration
        $studentsInDuration = Student::whereHas("user", function ($query) use (
            $startDate,
            $endDate
        ) {
            $query->whereBetween("date", [$startDate, $endDate]);
        })->count();

        // Number of payments made in the specified duration
        $payments = Subscription::whereBetween("date", [
            $startDate,
            $endDate,
        ])->count();

        // Total amount of payments made in the specified duration
        $paymentsAmount = Subscription::whereBetween("date", [
            $startDate,
            $endDate,
        ])->where('currency','SD')->sum("price");

        // Total costs incurred in the specified duration
        $costs = Cost::whereBetween("date", [$startDate, $endDate])->sum(
            "amount"
        );
        
        $watched_videos = WatchedVideo::whereBetween("date", [
            $startDate,
            $endDate,
        ])->count();
        
        $ludo = LudoStudent::whereBetween("date", [
            $startDate,
            $endDate,
        ])->count();

        // Number of exams conducted in the specified duration
        $exams = StudentExam::whereBetween("date", [
            $startDate,
            $endDate,
        ])->count();

        // Logic to find best selling and best income classes (example logic)
        $bestSellingClasses = 1; // Placeholder, replace with actual logic
        $bestIncomeClasses = 1; // Placeholder, replace with actual logic
        
        
        // Retrieve all classes with count of related exams in the specified duration
    $classes = Classs::with('level')->withCount(['exams' => function ($query) use ($startDate, $endDate) {
        $query->whereBetween("students_exams.date", [$startDate, $endDate]);
    }])->withCount(['ludo' => function ($query) use ($startDate, $endDate) {
        $query->whereBetween("ludo_students.date", [$startDate, $endDate]);
    }])->withCount(['watchedVideos' => function ($query) use ($startDate, $endDate) {
        $query->whereBetween("watcheds_videos.date", [$startDate, $endDate]);
    }])->get();
    
    $studentsMakeAnExam = Student::whereHas('exams', function ($query) use ($startDate, $endDate) {
        $query->whereBetween('date', [$startDate, $endDate]);
    })->get();
    
    $studentsPlayLudo = Student::whereHas('ludo', function ($query) use ($startDate, $endDate) {
        $query->whereBetween('date', [$startDate, $endDate]);
    })->get();
    
    $studentsWatchVideo = Student::whereHas('watchedVideos', function ($query) use ($startDate, $endDate) {
        $query->whereBetween('date', [$startDate, $endDate]);
    })->get();
    
    $questionIds = Question::doesntHave('answers')->pluck('id')->toArray();

        // Return JSON response with all the gathered data
        return response()->json(
            [
                "best_income_classes" => $bestIncomeClasses,
                "best_selling_classes" => $bestSellingClasses,
                "students" => $students,
                "costs" => $costs,
                "students_in_duration" => $studentsInDuration,
                "payments" => $payments,
                "payments_amount" => $paymentsAmount,
                "exams" => $exams,
                "watched_videos" => $watched_videos,
                "ludo" => $ludo,
                "classes" => $classes,
                "students_make_an_exam" => $studentsMakeAnExam,
                "students_play_ludo" =>$studentsPlayLudo,
                "students_watch_video" => $studentsWatchVideo,
                "clients" => $data['clients'],
                "count" => $questionIds
            ],
            200,
            [],
            JSON_NUMERIC_CHECK
        );
    }

    public function getComplaints(Request $request)
    {
        if ($request->question_id) {
            $questions = Complaint::whereIn("complaint_type_id", [1, 2])
                ->where("id", $request->question_id)
                ->with("student", "complaintType")
                ->orderBy("id", "desc")
                ->paginate(10);
            return $questions;
        } else {
            $questions = Complaint::whereIn("complaint_type_id", [1, 2])
                ->where("user_id", "like", "%" . $request->entry_id . "%")
                ->with("student", "complaintType")
                ->orderBy("id", "desc")
                ->paginate(10);
            return $questions;
        }
    }
}
