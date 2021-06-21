<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Complaint;
use App\Models\Level;
class StudentController extends Controller
{

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['getLevels']]);
    }

    public function changeName(Request $request) {

        $this->validate($request, [
            'name' => 'bail|required|min:4',
        ]);

        $user = auth()->user();

        $flag = Student::find($user->id)->update(['name'=> $request->name]);
        
        if($flag) {
            return response()->json("", 200);
        }
        else {
            $this->logout();
            return response()->json("", 500);
        }

    }

    public function addComplaint(Request $request)  {
        $this->validate($request, [
            'complaint' => 'bail|required|min:20',
            'complaint_type_id' => 'bail|required|exists:complaints_types,id'
        ]);

        $complaint = new Complaint();

        $complaint->user_id = auth()->user()->id;
        $complaint->complaint = $request->complaint;
        $complaint->complaint_type_id = $request->complaint_type_id;

        $flag = $complaint->save();

        if($flag) {
            return response()->json("", 201);
        }
        else {
            $this->logout();
            return response()->json("", 500);
        }


    }

    public function getLevels(Request $request) {
        return response()->json(['levels' => Level::with('classes.subjects.chapters.lessons')->get()], 200);
    }


}
