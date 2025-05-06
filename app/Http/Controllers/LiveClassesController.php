<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LiveClass;
use App\Models\LiveClassBaned;
use App\Models\Student;
use App\Models\Notification as Model;
use DB;
use App\Services\FirebaseNotificationService;

class LiveClassesController extends Controller
{

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api',['except' => ['getLiveClassByUuid','getLiveClassesV2']]);
        $this->notification = new Notification();

    }
    
    private $notification;

    //Dashboard********************************************
    public function getLiveClasses(Request $request)
    {
        
      $user = auth()->user();

$liveClasses = LiveClass::where('status_id', '1')
    ->with(['teacher', 'subscription' => function ($query) use ($user) {
        $query->where('student_id', $user->id);
    }])
    ->orderBy('date', 'DESC')
    ->paginate(10);
        

        return $liveClasses;
    }
    
    public function getLiveClassesV2(Request $request)
{
    // Fetch live classes with their associated teacher
    $user = auth()->user();
    
    $liveClasses;
    
    if($user) {
        $liveClasses = LiveClass::where('status_id', '1')
    ->with(['teacher', 'subscription' => function ($query) use ($user) {
        $query->where('student_id', $user->id);
    }])
    ->orderBy('date', 'DESC')
    ->paginate(10);
    }
    else {
        $liveClasses = LiveClass::where('status_id', '1')
    ->with(['teacher'])
    ->orderBy('date', 'DESC')
    ->paginate(10);
    }
    
    // Fetch teachers separately
    $teachers = Student::where('is_teacher', 1)
        ->with('teacher.subjects')
        ->get();
    
    // Construct response array
    $response = [
        'liveClasses' => $liveClasses,
        'teachers' => $teachers
    ];

    return $response;
}

    
    public function createLiveClass(Request $request) {
        
        $this->validate($request, ['room' => 'bail|required' ,'class_name' => 'bail|required' , 'pass' => 'nullable|string']);
        
        $user = auth()->user();
        $liveClass = new LiveClass();
        
        DB::beginTransaction();
        
        $liveClass->uuid = $request->room;
        $liveClass->teacher_id = $user->id;
        $liveClass->status_id = '1';
        $liveClass->class_name = $request->class_name;
        $liveClass->pass = $request->pass;
        
        $liveClass->save();
        
        $liveClass->load('teacher');

        
         $flag = $this->notification->requestSocket(['room' => $request->room , 'teacher_id' => $user->id] , 'createLiveClass');
        

        if($flag->getStatusCode() == 200) {
            DB::commit();
            $user->student = $user->load('student')->student;
            $firebaseService = app(FirebaseNotificationService::class);
            $firebaseService->sendToAll( ['image' => $user->student->profile_image, 'live_class' => $liveClass , 'type' => '4', 'title'=> 'بث مباشر' ,'body' => 'قام الاستاذ '.$user->student->name.' ببدء بث مباشر لحصة بعنوان : '.$request->class_name]);
            return response()->json('',201);
        }
        else {
            DB::rollBack();
            return response()->json("", 500);
        }
        
    }
    
    public function endLiveClass(Request $request) {
        
        $this->validate($request, ['room' => 'bail|required' ]);
        
         LiveClass::where('uuid',$request->room)->first()->update(['status_id'=>2]);
        
            $this->notification->requestSocket(['room' => $request->room] , 'endLiveClass');
    }
    
    public function getLiveClassByUuid(Request $request) {
        $this->validate($request, ['uuid' => 'bail|required' ]);
        
        return LiveClass::where('uuid',$request->uuid)->first();
        
    }
    
}


