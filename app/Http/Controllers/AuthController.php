<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\Code;
use Illuminate\Support\Facades\Hash;
use App\Models\NotePayment;
use App\Models\Note;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\classs;
use DB;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login' , 'sendCodeToVisitor' , 'sendCodeToUser' , 'resetPassword' , 'checkCode' , 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|min:10|max:14',
            'password' => 'required|string|min:4',
        ]);

        $credentials = $request->only('phone', 'password');

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function selectJbo(Request $request)
    {
        $user = auth()->user();
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $name = auth()->user()->load('student')->student->name;
        return response()->json([
            'token' => $token,
            'name' => $name
        ]);
    }

    public function sendCodeToUser(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|min:10|max:14|exists:users,phone',
        ]);

        $destinations = $request->phone;
        $numbers = '12345678910111236542301236985452315245552012369874563201423698745';
        $code = substr(str_shuffle(str_repeat($numbers, 5)), 0, 5);
        //$client = new HttpClient(); //GuzzleHttp\Client
        //$response = $client->request('GET', 'http://196.202.134.90/SMSbulk/webacc.aspx?user=Dr.spare&pwd=0963263869&smstext='.$number.'&Sender=Dr.Spare&Nums='.$destinations.'');

        $flag = Code::updateOrCreate(['phone' => $request->phone], ['code' => $code]);
        if ($flag) {
            return response()->json("", 200);
        } else {
            return response()->json("", 500);
        }
    }

    public function sendCodeToVisitor(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|min:10|max:14|unique:users',
        ]);

        $destinations = $request->phone;
        $numbers = '12345678910111236542301236985452315245552012369874563201423698745';
        $code = substr(str_shuffle(str_repeat($numbers, 5)), 0, 5);
        //$client = new HttpClient(); //GuzzleHttp\Client
        //$response = $client->request('GET', 'http://196.202.134.90/SMSbulk/webacc.aspx?user=Dr.spare&pwd=0963263869&smstext='.$number.'&Sender=Dr.Spare&Nums='.$destinations.'');

        $flag = Code::updateOrCreate(['phone' => $request->phone], ['code' => $code]);
        if ($flag) {
            return response()->json("", 200);
        } else {
            return response()->json("", 500);
        }
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            'phone' => 'bail|required|unique:users',
            'password' => 'bail|required|min:4',
            'name' => 'bail|required|min:4',
            'code' => 'bail|required'
        ]);

        $code = Code::firstWhere('code', $request->code);

        if (!$code) {
            return response()->json("", 401);
        }

        DB::beginTransaction();
        $flag = $code->delete();

        if ($flag) {
            $user = new User();
            $user->password = Hash::make($request->password);
            $user->phone = $request->phone;

        
            $flag = $user->save();

            if ($flag) {
                $student = new Student();
                $student->name = $request->name;
                $student->id = $user->id;
                
                $flag = $student->save();
                
                if ($flag) {
                    $request->password = $user->password;
                    DB::commit();
                    return $this->login($request);
                }
            } else {
                DB::rollBack();
                return response()->json("", 500);
            }
        } else {
            DB::rollBack();
            return response()->json("", 500);
        }
    }


    public function resetPassword(Request $request)
    {
        $this->validate($request, [
            'phone' => 'bail|required|exists:users,phone',
            'code' => 'bail|required',
            'password' =>'bail|required|min:4'
        ]);

        $code = Code::firstWhere('code', $request->code);

        if (!$code) {
            return response()->json("", 401);
        }

        $flag = $code->delete();

        $user = User::firstWhere('phone', $request->phone);

        $user->password = Hash::make($request->password);

        $flag = $user->save();

        if ($flag) {
            $flag = $code->delete();
            return response()->json("", 200);
        } else {
            $this->logout();
            return response()->json("", 500);
        }
    }


    public function checkCode(Request $request)
    {
        $this->validate($request, [
            'code' => 'bail|required',
        ]);

        $code = Code::firstWhere('code', $request->code);

        if (!$code) {
            return response()->json("", 401);
        } else {
            return response()->json("", 200);
        }
    }

    public function sendCode(Request $request)
    {
        $this->validate($request, [
                'phone' => 'required|min:10|max:14',
            ]);
    
        $destinations = $request->phone;
        $numbers = '12345678910111236542301236985452315245552012369874563201423698745';
        $code = substr(str_shuffle(str_repeat($numbers, 5)), 0, 5);
        //$client = new HttpClient(); //GuzzleHttp\Client
        //$response = $client->request('GET', 'http://196.202.134.90/SMSbulk/webacc.aspx?user=Dr.spare&pwd=0963263869&smstext='.$number.'&Sender=Dr.Spare&Nums='.$destinations.'');
    
        $flag = Code::updateOrCreate(['phone' => $request->phone], ['code' => $code]);
        if ($flag) {
            return response()->json("", 200);
        } else {
            return response()->json("", 500);
        }
    }

    public function notePayment(Request $request)
    {
        $this->validate($request, [
            'code' => 'bail|required',
            'note_id' => 'bail|required|exists:notes,id',
            'payment_type_id' => 'bail|required|exists:payments_types,id'
        ]);

        $code = Code::firstWhere('code', $request->code);

        if (!$code) {
            return response()->json("", 401);
        }

        DB::beginTransaction();

        $flag = $code->delete();

        $user = auth()->user();

        $payment = new Payment();

        $note = Note::find($request->note_id);

        $payment->price = $note->price;
        $payment->student_id = $user->id;
        $payment->payment_type_id = $request->payment_type_id;
        

        $flag = $payment->save();
        if ($flag) {
            $notePayment = new NotePayment();

            $notePayment->id = $payment->id;
            $notePayment->note_id = $request->note_id;

            $flag = $notePayment->save();

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

    public function classPayment(Request $request)
    {
        $this->validate($request, [
            'code' => 'bail|required',
            'class_id' => 'bail|required|exists:classes,id',
            'payment_type_id' => 'bail|required|exists:payments_types,id',
            'subscribe_package_id' => "bail|required|exists:subscribes_packages,id"
        ]);

        $code = Code::firstWhere('code', $request->code);

        if (!$code) {
            return response()->json("", 401);
        }

        DB::beginTransaction();

        $flag = $code->delete();

        $user = auth()->user();

        $payment = new Payment();

        $class = Classs::find($request->class_id);

        $payment->price = $class->price;
        $payment->student_id = $user->id;
        $payment->payment_type_id = $request->payment_type_id;
        

        $flag = $payment->save();
        if ($flag) {
            $subscription = new Subscription();

            $subscription->id = $payment->id;
            $subscription->class_id = $class->id;
            $subscription->subscription_package_id = $request->subscribe_package_id;

            $flag = $subscription->save();

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
}
