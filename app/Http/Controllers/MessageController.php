<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use App\Models\Friend;
use App\Models\Block;
use App\Models\User;
use App\Models\Message;
use App\Models\Student;
use App\Models\Conversation;
use App\Services\FirebaseNotificationService;
use DB;
use App\Models\Notification as Model;

class MessageController extends Controller
{
    
    private $notification;
    
    public function __construct()
    {
        $this->middleware("auth:api",['except'=>['updateStatus']]);
        $this->notification = new Notification();
    }

    public function getFriends()
    {
        $user = auth()->user();
        $friends = Friend::where("student_id1", $user->id)
            ->with("friend")
             ->join('students', 'friends.student_id2', '=', 'students.id') // Adjust this line if your relationship is different
        ->orderByDesc('students.is_online')  // This orders by online status (true first)
    ->orderByDesc('students.id')
            ->paginate(50);
        return $friends;
    }
    
    public function getStudents(Request $request)
{
        
    $user = auth()->user();

    // Get the students, excluding the authenticated user and friends
    if($request->student_class_id)
    $students = Student::where('id', '<>', $user->id)
    ->whereHas('classs', function($query) use ($request) {
        $query->where('student_class_id', $request->student_class_id);
    })
    ->where('is_teacher' , 0)
    ->where('name','like',$request->key.'%')
        ->with(['friends' => function ($query) use ($user) {
            $query->where('student_id2', $user->id)->first();
        }])
        ->with(['friendRequests' => function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                  ->where('notification_type_id', 5)->first();
        }])
        ->orderBy('id','desc')
        ->paginate(20);
    else
    $students = Student::where('id', '<>', $user->id)
    ->where('name','like',$request->key.'%')
        ->with(['friends' => function ($query) use ($user) {
            $query->where('student_id2', $user->id)->first();
        }])
        ->with(['friendRequests' => function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                  ->where('notification_type_id', 5)->first();
        }])
        ->orderBy('id','desc')
        ->paginate(20);

    // Add the hasFriendRequest property to each student
    $students->getCollection()->transform(function ($student) {
        $student->has_friend_request = $student->friendRequests->isNotEmpty();
        $student->friend_already = $student->friends->isNotEmpty();
        return $student;
    });

    return $students;
}


    public function getMessages()
    {
        $user = auth()->user();
        $messages = Conversation::where(function ($query) use ($user) {
            $query
                ->where("student_id1", $user->id)
                ->orWhere("student_id2", $user->id);
        })
        ->has('messages')
        ->with([
            'messages' => function ($query) {
                $query->orderBy('id', 'DESC'); // Order messages by id in descending order
            },
        ])
        ->withCount([
            'messages as user_message_count' => function ($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                      ->whereExists(function ($subQuery) use ($user) {
                          $subQuery->select(DB::raw(1))
                                   ->from('friends')
                                   ->whereColumn('friends.seen_at', '<', 'messages.created_at')
                                   ->where('friends.student_id1', '<>',$user->id)
                                   ->whereColumn('friends.conversation_id', 'messages.conversation_id');
                      });
            }
        ])
        ->with([
            'friend' => function ($query) use ($user) {
                $query->where('student_id1', $user->id); // Filter friend
            },
        ])
        ->with("student1", "student2")
        ->orderBy("updated_at", "desc")
        ->paginate(50);

            
        return $messages;
    }
    
    public function getConversationByFriendId(Request $request)
    {
        $this->validate($request, [
            "student_id" => "bail|required|exists:students,id",
        ]);
        
        $user = auth()->user();
        
        $conv = Conversation::where(function ($query) use ($user, $request) {
            $query
                ->where("student_id1", $user->id)
                ->Where("student_id2", $request->student_id);
        })
            ->orWhere(function ($query) use ($user, $request) {
                $query
                   ->where("student_id2", $user->id)
                   ->Where("student_id1", $request->student_id);
            })
        ->has('messages')
        ->with([
            'messages' => function ($query) {
                $query->orderBy('id', 'DESC'); // Order messages by id in descending order
            },
        ])
        ->withCount([
            'messages as user_message_count' => function ($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                      ->whereExists(function ($subQuery) use ($user) {
                          $subQuery->select(DB::raw(1))
                                   ->from('friends')
                                   ->whereColumn('friends.seen_at', '<', 'messages.created_at')
                                   ->where('friends.student_id1', '<>',$user->id)
                                   ->whereColumn('friends.conversation_id', 'messages.conversation_id');
                      });
            }
        ])
        ->with([
            'friend' => function ($query) use ($user) {
                $query->where('student_id1', $user->id); // Filter friend
            },
        ])
        ->with("student1", "student2")
        ->first();

            
        return $conv;
    }
    
    public function sendMessage(Request $request) {
        
         $this->validate($request, [
            "message" => "bail|required",
            "conversation_id" => "bail|required|exists:conversations,id",
            "receiver_id" => "bail|required|exists:students,id",
        ]);
        
        
        $user = auth()->user();
        
        $weAreFriend = Friend::where('student_id1',$user->id)->where('student_id2',$request->receiver_id)->first();
        
        if($weAreFriend == null) {
            return response()->json("they are not friend", 500);
        }
        
        $blocked = BLock::where('client_id1',$user->id)->where('client_id2',$request->receiver_id)->first();
        
        if($blocked != null) {
            return response()->json("blocked", 500);
        }
        $message = new Message();
        
        $message->conversation_id = $request->conversation_id;
        $message->message = $request->message;
        $message->sender_id = $user->id;
        
        $message->save();
            
            $receiver = User::find($request->receiver_id);
            $user->student = $user->load('student')->student;
            
            
            $model = new Model();
                $model->client_id = $request->receiver_id;
                $model->sender_id = $user->id;
                $model->post_id = 1;
                $model->notification_type_id = "7";
                $model->notification = $request->message;
                
            $firebaseService = app(FirebaseNotificationService::class);
                $firebaseService->sendNotification($receiver->firebase, [
                    "type" => "7",
                    "notification" => $model,
                    "title" => $user->student->name,
                    "image" => $user->student->profile_image,
                    "body" => $request->message,
                    "client" => $user->student,
            ]);
            
            $conv = Conversation::where('id',$request->conversation_id)
            ->with([
                "messages" => function ($query) use ($user){
                    $query->latest()->take(10); // Limit to the latest 50 messages
                },
            ])
            ->with([
                'friend' => function ($query) use ($user){
                    $query->where('student_id1',$user->id); // Order messages by id in descending order
                },
            ])
            ->withCount([
            'messages as user_message_count' => function ($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                      ->whereExists(function ($subQuery) use ($user) {
                          $subQuery->select(DB::raw(1))
                                   ->from('friends')
                                   ->whereColumn('friends.seen_at', '<', 'messages.created_at')
                                   ->where('friends.student_id1','<>', $user->id)
                                   ->whereColumn('friends.conversation_id', 'messages.conversation_id');
                      });
            }
        ])
            ->with("student1", "student2")->first();
            
            $conv->updated_at = date('Y-m-d H:i:s'); // Set the `updated_column` to the current timestamp
            $conv->save();
            
            $conv->messages = $conv->messages == null ? [] : $conv->messages->toArray();
            
            $this->notification->requestSocket(['room' => 'student_'.$request->receiver_id,'conv' => $conv->toArray(), 'conversation_id'=> $request->conversation_id],'pushMessage');
            
            return response()->json($message, 200);
    }
    
    public function seen(Request $request) {
        
        $this->validate($request, [
            "receiver_id" => "bail|required|exists:students,id",
        ]);
        
        $user = auth()->user();
        $friend = Friend::where('student_id1' , $request->receiver_id)->where('student_id2',$user->id)->first();
        
        if($friend == null) {
            return response()->json("error", 500);
        }
        
        $date = now();
        $friend->seen_at = $date;
        
        $friend->save();
        
        $this->notification->requestSocket(['room' => 'student_'.$request->receiver_id,'seen_at' => $friend->seen_at->format('Y-m-d H:i:s'), 'conversation_id'=> $friend->conversation_id],'pushSeen');
        
    }
    
    public function updateStatus(Request $request) {
        
        $this->validate($request, [
            "online_status" => "bail|required",
            "student_id" => "bail|required|exists:students,id",
        ]);
        
        $affectedRows = Student::where('id', $request->student_id)
        ->update(['is_online' => $request->online_status]);
        
    }
}
