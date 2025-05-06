<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolStudent;
use App\Models\Code;
use Illuminate\Support\Facades\Hash;
use App\Models\NotePayment;
use App\Models\Note;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\WatchedVideo;
use App\Models\Classs;
use App\Models\Friend;
use App\Models\PaymentMode;
use App\Models\StudentStudentClass;
use App\Models\MBookRequest;
use App\Models\Version;
use App\Models\Country;
use Twilio\Rest\Client;
use DB;
use Spatie\Async\Pool;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */

    private $maintenance = 0;

    public function __construct()
    {
        $this->middleware("auth:api", [
            "except" => [
                "visitor",
                "login",
                "loginV2",
                "loginV3",
                "whatsappMessage",
                "accountantLogin",
                "sendCodeToVisitor",
                "sendCodeToUser",
                "resetPassword",
                "checkCode",
                "register",
                "supervisorLogin",
                "adminLogin",
                "test",
                "sendMessageToTopic",
                "sendMessageToSupervisor",
                "sendMessageToDriver",
                "sendLocation",
            ],
        ]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function visitor()
    {
        $isFree = PaymentMode::find(1)->is_free == 1;
        $versions = Version::pluck("version");
        $countries = Country::pluck("code");

        return response()->json(
            [
                "is_free" => $isFree,
                "versions" => $versions,
                "maintenance" => $this->maintenance,
                "countries" => $countries,
            ],
            200
        );
    }
    public function login(Request $request)
    {
        return response()->json(
            json_decode('{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC95YWxsYS1lbXRpaGFuLmNvbVwvYmFja2VuZFwvYXV0aFwvbG9naW4iLCJpYXQiOjE2NzY1MTY1MjIsImV4cCI6MTY3OTE0NDUyMiwibmJmIjoxNjc2NTE2NTIyLCJqdGkiOiJ2b1VUVnUxQTA2YUZVRmlWIiwic3ViIjoyNywicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.56HqtYSW6jDFXzJZYfuWTiHUoiIjV2EgZk_tMOA1_K4",
    "client": {
        "id": 27,
        "name": "علي عبدالله",
        "profile_image": "default_profile.png"
    },
    "version": "2.0.3"
}'),
            200
        );
    }

    public function loginV2(Request $request)
    {
        $this->validate($request, [
            "credential" => "bail|required",
            "firebase" => "bail|nullable",
            "device_id" => "bail|required",
            "register_type_id" => "bail|required",
        ]);

        $request->request->add([
            "user_type_id" => 1,
            "status_id" => 1,
            "password" => "sdf",
        ]);

        $credentials = $request->only(
            "credential",
            "status_id",
            "user_type_id",
            "password"
        );

        if (!($token = auth()->attempt($credentials))) {
            DB::beginTransaction();

            $profileImageName = "default_profile.png";

            if (
                ($request->register_type_id != 1) &
                $request->file("profile_image")
            ) {
                $profileImageName = time() . rand(100, 1000) . ".png";
                if (
                    !$request
                        ->file("profile_image")
                        ->move(
                            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/profile_images/",
                            $profileImageName
                        )
                ) {
                    return response()->json("profile image error", 500);
                }
            }

            $user = new User();

            $user->user_type_id = 1;
            $user->register_type_id = $request->register_type_id;
            $user->credential = $request->credential;
            $user->phone =
                $request->register_type_id == 1 ? $request->credential : "-";
            $user->password = Hash::make("sdf");
            $user->firebase = $request->firebase;

            $flag = $user->save();

            if (!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }

            $student = new Student();
            $student->name =
                $request->register_type_id == 1
                    ? "المستخدم #" . $user->id
                    : $request->name;
            $student->id = $user->id;
            $student->profile_image = $profileImageName;

            $flag = $student->save();
            $student->is_free = PaymentMode::find(1)->is_free == 1;

            if (!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }

            $student->watched_videos = "0";

            $request->password = $user->password;
            $token = auth()->fromUser($user);
            $version = Version::orderBy("id", "desc")->first();
            DB::commit();
            return response()->json(
                [
                    "version" => $version->version,
                    "token" => $token,
                    "client" => $student,
                ],
                201
            );
        }

        return $this->respondWithToken(
            $token,
            $request->firebase,
            $request->device_id
        );
    }

    public function loginV3(Request $request)
    {
        $this->validate($request, [
            "credential" => "bail|required",
            "firebase" => "bail|nullable",
            "device_id" => "bail|required",
            "register_type_id" => "bail|required",
        ]);

        $request->request->add([
            "user_type_id" => 1,
            "status_id" => 1,
            "password" => "sdf",
        ]);

        $credentials = $request->only(
            "credential",
            "status_id",
            "user_type_id",
            "password"
        );

        if (!($token = auth()->attempt($credentials))) {
            if (User::where("credential", $request->credential)->exists()) {
                return response()->json(
                    ["error" => "User already exists."],
                    409
                ); // 409 Conflict
            }
            DB::beginTransaction();

            $profileImageName = "default_profile.png";

            if (
                ($request->register_type_id != 1) &
                $request->file("profile_image")
            ) {
                $profileImageName =
                    time() .
                    rand(100, 1000) .
                    "." .
                    $request->file("profile_image")->extension();
                if (
                    !$request
                        ->file("profile_image")
                        ->move(
                            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/profile_images/",
                            $profileImageName
                        )
                ) {
                    return response()->json("profile image error", 500);
                }
            }

            $user = new User();

            $user->user_type_id = 1;
            $user->register_type_id = $request->register_type_id;
            $user->credential = $request->credential;
            $user->phone =
                $request->register_type_id == 1 ? $request->credential : "-";
            $user->password = Hash::make("sdf");
            $user->firebase = $request->firebase;

            $flag = $user->save();

            if (!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }

            $student = new Student();
            $student->name =
                $request->register_type_id == 1
                    ? "المستخدم #" . $user->id
                    : $request->name;
            $student->id = $user->id;
            $student->profile_image = $profileImageName;
            $student->lobby = 0;

            $flag = $student->save();
            $student->is_free = PaymentMode::find(1)->is_free == 1;

            if (!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }

            $student->watched_videos = 0;
            $student->active = false;

            $request->password = $user->password;
            $token = auth()->fromUser($user);
            $version = Version::orderBy("id", "desc")->first();
            $student->versions = Version::pluck("version");
            $student->countries = Country::pluck("code");
            DB::commit();
            return response()->json(
                [
                    "version" => $version->version,
                    "token" => $token,
                    "client" => $student,
                ],
                201
            );
        }

        return $this->respondWithToken(
            $token,
            $request->firebase,
            $request->device_id
        );
    }

    public function setupProfile(Request $request)
    {
        $this->validate($request, ["name" => "bail|required"]);

        $student = auth()
            ->user()
            ->load("student")->student;

        if ($request->file("profile_image")) {
            $profileImageName =
                $student->profile_image == "default_profile.png"
                    ? time() .
                        rand(100, 1000) .
                        "." .
                        $request->file("profile_image")->extension()
                    : $student->profile_image;
            $student->profile_image = $profileImageName;
            if (
                !$request
                    ->file("profile_image")
                    ->move(
                        "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/profile_images/",
                        $profileImageName
                    )
            ) {
                return response()->json("profile image error", 500);
            }
        }

        $student->name = $request->name;

        $student->save();

        return response()->json($student, 200);
    }

    public function setupProfileV2(Request $request)
    {
        $this->validate($request, [
            "name" => "bail|required",
            "student_class_id" => "required|exists:students_classes,id",
        ]);

        $student = auth()
            ->user()
            ->load("student")->student;

        if ($request->file("profile_image")) {
            $profileImageName =
                $student->profile_image == "default_profile.png"
                    ? time() .
                        rand(100, 1000) .
                        "." .
                        $request->file("profile_image")->extension()
                    : $student->profile_image;
            $student->profile_image = $profileImageName;
            if (
                !$request
                    ->file("profile_image")
                    ->move(
                        "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/profile_images/",
                        $profileImageName
                    )
            ) {
                return response()->json("profile image error", 500);
            }
        }

        $student->name = $request->name;
        StudentStudentClass::updateOrCreate(
            ["student_id" => $student->id],
            ["student_class_id" => $request->student_class_id]
        );

        $student->save();

        $student->classs = $student->load("classs.studentClass")->classs;
        $student->is_free = PaymentMode::find(1)->is_free == 1;
        $student->active = false;

        return response()->json($student, 200);
    }

    public function editName(Request $request)
    {
        $this->validate($request, ["name" => "bail|required"]);

        $student = auth()
            ->user()
            ->load("student")->student;

        $student->name = $request->name;

        $student->save();
    }

    public function deleteAccount(Request $request)
    {
        $student = auth()
            ->user()
            ->load("student")->student;

        $student->name = "client #" . $student->id;
        $student->profile_image = "default_profile.png";

        $student->save();

        auth()->logout();

        return response()->json(["message" => "Successfully logged out"]);
    }

    public function editProfileImage(Request $request)
    {
        $this->validate($request, ["profile_image" => "bail|required"]);

        $student = auth()
            ->user()
            ->load("student")->student;

        $profileImageName =
            time() .
            rand(100, 1000) .
            "." .
            $request->file("profile_image")->extension();
        $student->profile_image = $profileImageName;
        if (
            !$request
                ->file("profile_image")
                ->move(
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/profile_images/",
                    $profileImageName
                )
        ) {
            return response()->json("profile image error", 500);
        }

        $student->save();

        return response()->json($student, 200);
    }

    public function test(Request $request)
    {
        $pushNotification = new Notification();
        return $pushNotification->sendNotificationByToken($request->firebase, [
            "title" => "hi",
            "body" => "hi",
        ]);
    }

    public function accountantLogin(Request $request)
    {
        $this->validate($request, [
            "phone" => "required|min:10|max:14",
            "password" => "required|string|min:4",
        ]);

        $request->request->add(["user_type_id" => 2, "status_id" => 1]);

        $request->phone = "+249928111022";

        $credentials = $request->only(
            "phone",
            "password",
            "user_type_id",
            "status_id"
        );

        if (!($token = auth()->attempt($credentials))) {
            return response()->json(["error" => "Unauthorized"], 401);
        }

        return $this->supervisorRespondWithToken($token);
    }

    public function supervisorLogin(Request $request)
    {
        $this->validate($request, [
            "phone" => "required|min:10|max:14",
            "password" => "required|string|min:4",
        ]);

        $request->request->add(["user_type_id" => 2, "status_id" => 1]);

        $credentials = $request->only(
            "phone",
            "password",
            "user_type_id",
            "status_id"
        );

        if (!($token = auth()->attempt($credentials))) {
            return response()->json(["error" => "Unauthorized"], 401);
        }

        return $this->supervisorRespondWithToken($token);
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
        $user = auth()->user();
        $user->firebase = null;
        auth()->logout();

        return response()->json(["message" => "Successfully logged out"]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        return $this->respondWithToken(
            auth()->refresh(),
            $request->firebase,
            $request->device_id == null ? "kkk" : $request->device_id
        );
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $firebase, $device_id)
    {
        $user = auth()->user();
        $user->token = $token;
        $user->firebase = $firebase;
        $user->save();

        $client = $user->load("student.schoolStudent.school", "student.schoolStudent.room.classs.level" , "student.schoolStudent.room.classs.subjects")->student;
        $client->watched_videos = WatchedVideo::where("student_id", $user->id)
            ->orWhere("device_id", $device_id)
            ->count();

        $version = Version::find(1);
        $client->versions = Version::pluck("version");
        $client->countries = Country::pluck("code");
        $client->is_free = PaymentMode::find(1)->is_free == 1;

        $active = Subscription::where("student_id", $user->id)
            ->orderBy("id", "desc")
            ->first();
        $client->active =
            $active != null && $active->status_id != 2 ? true : false;
        $client->friends = Friend::where("student_id1", $client->id)
            ->pluck("student_id2")
            ->toArray();
        $client->classs = $client->load("classs.studentClass")->classs;
        $client->maintenance = $this->maintenance;

        return response()->json([
            "token" => $token,
            "client" => $client,
            "version" => $version->version,
        ]);
    }

    protected function supervisorRespondWithToken($token)
    {
        $user = auth()->user();
        $user->load(['supervisor.schoolSupervisor.branch']);
        $name = $user->supervisor->name;
        
        $school = $user->supervisor->schoolSupervisor == null ? null : $user->supervisor->schoolSupervisor->school_id;
        $type = $user->supervisor->schoolSupervisor == null ? null : $user->supervisor->schoolSupervisor->school_supervisor_type_id;
        $branch = $user->supervisor->schoolSupervisor == null || $user->supervisor->schoolSupervisor->branch == null ? null : $user->supervisor->schoolSupervisor->branch->school_branch_id;

        
        $user_type_id = $user->supervisor->supervisor_type_id;
        return response()->json([
            "token" => $token,
            "name" => $name,
            "school" => $school,
            "type" => $type,
            "branch" => $branch,
            "user_type_id" => $user_type_id,
        ]);
    }

    public function sendCodeToUser(Request $request)
    {
        $this->validate($request, [
            "phone" => "required|min:10|max:14|exists:users,phone",
            "lang" => "required",
        ]);

        $destinations = $request->phone;
        $numbers =
            "12345678910111236542301236985452315245552012369874563201423698745";
        $code = substr(str_shuffle(str_repeat($numbers, 5)), 0, 5);
        $msg =
            $request->lang == "ar"
                ? "كود التفعيل الخاص بيك في تطبيق يلا إمتحان هو " . $code
                : "Your activation code in Yalla Emithan app is " . $code;
        $client = new HttpClient();
        $response = $client->request(
            "GET",
            "http://196.202.134.90/dsms/webacc.aspx?user=Nubian&pwd=c@123&smstext=" .
                $msg .
                "&Sender=Y-Emtihan&Nums=" .
                substr($request->phone, 1)
        );
        if ($response->getBody() != "OK") {
            return response()->json("", 500);
        }
        $flag = Code::updateOrCreate(
            ["phone" => $request->phone],
            ["code" => $code]
        );
        if ($flag) {
            return response()->json("", 200);
        } else {
            return response()->json("", 500);
        }
    }

    public function sendCodeToVisitor(Request $request)
    {
        $this->validate($request, [
            "phone" => "required|min:10|max:14|unique:users",
            "lang" => "required",
        ]);

        $destinations = $request->phone;
        $numbers =
            "12345678910111236542301236985452315245552012369874563201423698745";
        $code = substr(str_shuffle(str_repeat($numbers, 5)), 0, 5);
        $msg =
            $request->lang == "ar"
                ? "كود التفعيل الخاص بيك في تطبيق يلا إمتحان هو " . $code
                : "Your activation code in Yalla Emithan app is " . $code;
        $client = new HttpClient();
        $response = $client->request(
            "GET",
            "http://196.202.134.90/dsms/webacc.aspx?user=Nubian&pwd=c@123&smstext=" .
                $msg .
                "&Sender=Y-Emtihan&Nums=" .
                substr($request->phone, 1)
        );
        if ($response->getBody() != "OK") {
            return response()->json("", 500);
        }
        $flag = Code::updateOrCreate(
            ["phone" => $request->phone],
            ["code" => $code]
        );
        if ($flag) {
            return response()->json("", 200);
        } else {
            return response()->json("", 500);
        }
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            "phone" => "bail|required|unique:users",
            "password" => "bail|required|min:4",
            "name" => "bail|required|min:4",
            "code" => "bail|required",
        ]);

        $code = Code::firstWhere("code", $request->code);

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
                    // $this->whatsappTextMessage($request->phone,"الطالب ".$request->name."، شكراً لتسجيل حساب في تطبيق يلامتحان، الفديوهات التالية تشرح طريقة استخدام تطبيق يلا امتحان، تمتع بالخدمة.");
                    // $this->whatsappVideoMessage($request->phone,"https://www.yalla-emtihan.com/yalla-emtihan/public/videos/1.mp4");
                    // $this->whatsappVideoMessage($request->phone,"https://www.yalla-emtihan.com/yalla-emtihan/public/videos/2.mp4");
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
            "phone" => "bail|required|exists:users,phone",
            "code" => "bail|required",
            "password" => "bail|required|min:4",
        ]);

        $code = Code::firstWhere("code", $request->code);

        if (!$code) {
            return response()->json("", 401);
        }

        $flag = $code->delete();

        $user = User::firstWhere("phone", $request->phone);

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
        $this->validate($request, ["code" => "bail|required"]);

        $code = Code::firstWhere("code", $request->code);

        if (!$code) {
            return response()->json("", 401);
        } else {
            return response()->json("", 200);
        }
    }

    public function sendCode(Request $request)
    {
        $this->validate($request, [
            "phone" => "required|min:10|max:14",
            "lang" => "required",
        ]);

        $destinations = $request->phone;
        $numbers =
            "12345678910111236542301236985452315245552012369874563201423698745";
        $code = substr(str_shuffle(str_repeat($numbers, 5)), 0, 5);
        $msg =
            $request->lang == "ar"
                ? "كود التفعيل الخاص بيك في تطبيق يلا إمتحان هو " . $code
                : "Your activation code in Yalla Emithan app is " . $code;
        $client = new HttpClient();
        $response = $client->request(
            "GET",
            "http://196.202.134.90/dsms/webacc.aspx?user=Nubian&pwd=c@123&smstext=" .
                $msg .
                "&Sender=Y-Emtihan&Nums=" .
                substr($request->phone, 1)
        );
        if ($response->getBody() != "OK") {
            return response()->json("", 500);
        }
        $flag = Code::updateOrCreate(
            ["phone" => $request->phone],
            ["code" => $code]
        );
        if ($flag) {
            return response()->json("", 200);
        } else {
            return response()->json("", 500);
        }
    }

    public function notePayment(Request $request)
    {
        $this->validate($request, [
            "code" => "bail|required",
            "note_id" => "bail|required|exists:notes,id",
            "payment_type_id" => "bail|required|exists:payments_types,id",
        ]);

        $code = Code::firstWhere("code", $request->code);

        if (!$code) {
            return response()->json("", 411);
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
            "code" => "bail|required",
            "class_id" => "bail|required|exists:classes,id",
            "payment_type_id" => "bail|required|exists:payments_types,id",
            "subscribe_package_id" =>
                "bail|required|exists:subscribes_packages,id",
        ]);

        $code = Code::firstWhere("code", $request->code);

        if (!$code) {
            return response()->json("", 411);
        }

        DB::beginTransaction();

        $flag = $code->delete();

        $user = auth()->user();

        $payment = new Payment();

        $class = Classs::find($request->class_id);

        $payment->price =
            $request->subscribe_package_id == 1
                ? ($class->price * 10) / 100
                : $class->price;
        $payment->student_id = $user->id;
        $payment->payment_type_id = $request->payment_type_id;

        $flag = $payment->save();
        if ($flag) {
            $subscription = new Subscription();

            $subscription->id = $payment->id;
            $subscription->class_id = $class->id;
            $subscription->subscription_package_id =
                $request->subscribe_package_id;

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

    public function addMBookRequest(Request $request)
    {
        $this->validate($request, [
            "pic" => "bail|required",
            "class_id" => "bail|required|exists:classes,id",
            "subscribe_package_id" =>
                "bail|required|exists:subscribes_packages,id",
        ]);

        $picName = "";

        $pic = $request->pic;
        $picName = time() . rand(100, 1000) . ".png";

        if (
            !$request
                ->file("pic")
                ->move(
                    "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/mbook/requests/",
                    $picName
                )
        ) {
            return response()->json("pic error", 500);
        }

        DB::beginTransaction();

        $user = auth()->user();

        $payment = new Payment();

        $class = Classs::find($request->class_id);

        $payment->price =
            $request->subscribe_package_id == 1
                ? ($class->price * 10) / 100
                : $class->price;
        $payment->student_id = $user->id;
        $payment->payment_type_id = 1;

        $flag = $payment->save();
        if ($flag) {
            $subscription = new Subscription();

            $subscription->id = $payment->id;
            $subscription->class_id = $class->id;
            $subscription->subscription_status_id = 2;
            $subscription->subscription_package_id =
                $request->subscribe_package_id;

            $flag = $subscription->save();

            if ($flag) {
                $mBookRequest = new MBookRequest();

                $mBookRequest->id = $payment->id;
                $mBookRequest->pic = $picName;
                $mBookRequest->class_id = $request->class_id;
                $mBookRequest->subscribe_package_id =
                    $request->subscribe_package_id;
                $mBookRequest->student_id = $user->id;

                $flag = $mBookRequest->save();

                if ($flag) {
                    DB::commit();
                    return response()->json("", 201);
                } else {
                    return response()->json("", 500);
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

    public function activeMBookRequest(Request $request)
    {
        $this->validate($request, [
            "id" => "bail|required|exists:subscriptions,id",
        ]);

        DB::beginTransaction();

        $subscription = Subscription::find($request->id);
        $subscription->subscription_status_id = 1;

        $flag = $subscription->save();

        if ($flag) {
            $mBookRequest = MBookRequest::find($request->id);
            $mBookRequest->mbook_request_status_id = 2;

            $flag = $mBookRequest->save();

            if ($flag) {
                DB::commit();

                $student = User::find($mBookRequest->student_id);
                $notification = new Notification();

                $notification->sendNotificationByToken($student->firebase, [
                    "title" => "تم تفعيل الإشتراك",
                    "sound" => "default",
                    "body" =>
                        "لقد تمت عملية تفعيل إشتراكك في يلا امتحان، تمتع بالخدمة",
                ]);

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

    public function disactiveMBookRequest(Request $request)
    {
        $this->validate($request, [
            "id" => "bail|required|exists:subscriptions,id",
        ]);

        DB::beginTransaction();

        $subscription = Subscription::find($request->id);
        $subscription->subscription_status_id = 2;

        $flag = $subscription->save();

        if ($flag) {
            $mBookRequest = MBookRequest::find($request->id);
            $mBookRequest->mbook_request_status_id = 1;

            $flag = $mBookRequest->save();

            if ($flag) {
                DB::commit();

                $student = User::find($mBookRequest->student_id);
                $notification = new Notification();

                $notification->sendNotificationByToken($student->firebase, [
                    "title" => "تم تعطيل الإشتراك",
                    "sound" => "default",
                    "body" =>
                        "لقد تمت عملية تعطيل الخدمة في يلا امتحان لتأكد من صحة بيانات الدفع، رجاءاً الإنتظار قليلاً",
                ]);

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

    // public function smsForAllUsers() {
    //     $students = Student::with('user')->get();
    //     $phones = "";
    //     foreach($students as $student) {
    //         $phones = $phones.substr($student->user->phone, 1).";";
    //     }
    //     $phones = substr($phones,0,-1);
    //     $msg = "عميلنا العزيز ".$student->name."، لقد قمنا بإصدار تحديث جديد أكثر كفاءة و سرعة حتى تستمعوا بخدمة اكثر سهولة، رجاءاً حمل التطبيق من الرابط: https://play.google.com/store/apps/details?id=com.yalla_emtihan.www";
    //     $client = new HttpClient();
    //     $response = $client->request('GET', 'http://196.202.134.90/dsms/webacc.aspx?user=Nubian&pwd=c@123&smstext=' . $msg . '&Sender=Y-Emtihan&Nums=' . $phones);
    //     if ($response->getBody() != "OK")
    //     {
    //         return response()
    //             ->json("", 500);
    //     }
    //     else {
    //         return "yesss";
    //     }
    // }
    public function whatsappMessage(Request $request)
    {
        // $students = Student::whereHas('user', function ($query) {
        //     $query->whereNull('token')->whereNull('firebase');
        // })->with('user')->get();
        // foreach($students as  $student) {
        //     echo $student->name.': '.$student->user->phone.PHP_EOL ;
        // }
        return Hash::make($request->password);
    }

    //  $students = Student::with('user')->get();
    //     foreach($students as $student) {
    //         $msg =$msg = "ميلنا العزيز، لو التطبيق عرض ليك انو النسخة قديمة و طلب منك تنزيل النسخة الجديد كل العليك تعملو انك تمسح التطبيق، و تنزل النسخة الجديدة من هنا https://play.google.com/store/apps/details?id=com.yalla_emtihan.www";
    //         $this->whatsappTextMessage($student->user->phone,$msg);
    //     }
    public function whatsappTextMessage($phone, $text)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.ultramsg.com/instance3854/messages/chat",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>
                "token=60crs7pa6ecq9t83&to=" .
                $phone .
                "&body=" .
                $text .
                "&priority=1&referenceId=",
            CURLOPT_HTTPHEADER => [
                "content-type: application/x-www-form-urlencoded",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        // if ($err)
        // {
        //     echo "cURL Error #:" . $err;
        // }
        // else
        // {
        //     echo $response;
        // }
    }

    public function whatsappVideoMessage($phone, $video)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.ultramsg.com/instance3854/messages/video",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>
                "token=60crs7pa6ecq9t83&to=" .
                $phone .
                "&video=" .
                $video .
                "&priority=1&referenceId=",
            CURLOPT_HTTPHEADER => [
                "content-type: application/x-www-form-urlencoded",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        // if ($err)
        // {
        //     echo "cURL Error #:" . $err;
        // }
        // else
        // {
        //     echo $response;
        // }
    }

    public function sendMessageToTopic(Request $request)
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        $fields = [
            "to" => "/topics/" . $request->topic,
            "data" => [
                "lat" => $request->lat,
                "lng" => $request->lng,
                "id" => $request->id,
            ],
        ];
        $header = [
            "Authorization:key =AAAAVhHuGdU:APA91bHSGNKUXA2YVyB85FOMbn1bM3UlOylHKk_6casByBMfmiwdVUggkXbA62z7uHJGTN95RzrFsZQ-VOY0Lx-9lbpDLSDhA-Jgfiyd9I5aSyU9wbaZXTidaCULpAZhRJ2BYK-HUG3d",
            "Content-Type:application/json",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        if ($result === false) {
            return response()->json("", 500);
        }
        curl_close($ch);

        return response()->json("", 200);
    }

    public function sendMessageToSupervisor(Request $request)
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        $fields = [
            "to" => "/topics/" . $request->topic,
            "notification" => [
                "title" => $request->title,
                "body" => $request->body,
            ],
        ];
        $header = [
            "Authorization:key =AAAAigVVhLg:APA91bFVVJXRl4GrJieO1JjRJD0ywnb6fT1wR8gmxhZvq_EZegdthwDuVTYOzxsrtBto8OZ87nxV8bUsC23RVReVnJzkv2Gijn6mlsMvu3c1IknhnqOjFUj2qWoEjIs36Xh2erXL-B-v",
            "Content-Type:application/json",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        if ($result === false) {
            return response()->json("", 500);
        }
        curl_close($ch);

        return response()->json("", 200);
    }

    public function sendMessageToDriver(Request $request)
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        $fields = [
            "to" => "/topics/" . $request->topic,
            "notification" => [
                "title" => $request->title,
                "body" => $request->body,
            ],
        ];
        $header = [
            "Authorization:key =AAAAywQWd1M:APA91bFNNhz3G6bb-ISL5m84nqrqzANKYvog-bwYd0Da6ZW1RwAuAt2M7EWJGl1vXa4UU8LKKNJVIqAl4v1oYtF6rCf9_zDHwj4PblEx8xzpUnek3XzctYvfebaFyKv5gHfmNpDJGr8g",
            "Content-Type:application/json",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        if ($result === false) {
            return response()->json("", 500);
        }
        curl_close($ch);

        return response()->json("", 200);
    }

    public function sendLocation(Request $request)
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        $fields = [
            "to" => "/topics/" . $request->topic,
            "data" => [
                "lat" => $request->lat,
                "lng" => $request->lng,
                "id" => $request->id,
            ],
        ];
        $header = [
            "Authorization:key =AAAAywQWd1M:APA91bFNNhz3G6bb-ISL5m84nqrqzANKYvog-bwYd0Da6ZW1RwAuAt2M7EWJGl1vXa4UU8LKKNJVIqAl4v1oYtF6rCf9_zDHwj4PblEx8xzpUnek3XzctYvfebaFyKv5gHfmNpDJGr8g",
            "Content-Type:application/json",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        if ($result === false) {
            return response()->json("", 500);
        }
        curl_close($ch);

        return response()->json("", 200);
    }
}
