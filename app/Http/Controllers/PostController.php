<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\Post;
use App\Models\PostText;
use App\Models\PostImage;
use App\Models\Friend;
use App\Models\PostUp;
use App\Models\Comment;
use App\Models\CommentLove;
use App\Models\PostComplaint;
use App\Models\Complaint;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification as Model;
use DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Services\FirebaseNotificationService;

class PostController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    private $notification;

    public function __construct()
    {
        $this->middleware("auth:api", ["except" => ["getPosts","getComments"]]);
        $this->notification = new Notification();
    }

    //Dashboard********************************************
    public function getPosts(Request $request)
{
    $user = auth()->user();

    $query = Post::where("status_id", "1")
        ->with("text", "image", "client.subscriptions", "client.teacher.subjects", "client.blocks", "client.classs.studentClass", "video")
        ->withCount("comments", "ups");

    if ($user) {
        $query->whereDoesntHave("client.blocks", function ($query) use ($user) {
            $query->where("client_id2", $user->id);
        });
    }

    if ($request->student_class_id) {
        $query->where(function($query) use ($request) {
            $query->whereHas('client', function($query) use ($request) {
                $query->where('is_teacher', 1);
            })
            ->orWhereHas('client.classs', function($query) use ($request) {
                $query->where('student_class_id', $request->student_class_id);
            });
        });
    }

    $posts = $query->orderBy("pinned", "DESC")
                   ->orderBy("id", "DESC")
                   ->paginate(50);

    if ($user) {
        foreach ($posts as $post) {
            $post->is_client_post = $post->client_id == $user->id;
            $post->up_by_client = PostUp::where("post_id", $post->id)
                                         ->where("client_id", $user->id)
                                         ->exists();
            $post->is_my_friend = Friend::where(function ($query) use ($user, $post) {
                $query->where("student_id1", $user->id)
                      ->where("student_id2", $post->client_id);
            })->exists();
            $post->friend_request = Model::where("notification_type_id", 5)
                                         ->where("sender_id", $user->id)
                                         ->where("client_id", $post->client_id)
                                         ->exists();

            // Check subscription logic
            if ($post->client->subscriptions->isEmpty()) {
                // If subscriptions are empty, set as false
                $post->subscription = false;
            } else {
                // Check if the last subscription's status_id is 2
                $lastSubscription = $post->client->subscriptions->last(); // Get the last subscription
                if ($lastSubscription && $lastSubscription->status_id == 2) {
                    // If last subscription's status_id is 2, set as false
                    $post->subscription = false;
                } else {
                    // Otherwise, set as true
                    $post->subscription = true;
                }
            }

            // Remove subscriptions from client object after processing
            unset($post->client->subscriptions);  // Unset subscriptions
        }
    } else {
        foreach ($posts as $post) {
            $post->is_client_post = false;
            $post->up_by_client = false;
            $post->friend_request = false;
            $post->subscriptions = false; // No subscriptions for unauthenticated users
        }
    }

    return $posts;
}




    public function getPostsByStudentId(Request $request)
    {
        $posts = Post::where("client_id", $request->student_id)
            ->where("status_id", "1")
            ->with("text", "image", "client.teacher.subjects")
            ->withCount("comments", "ups")
            ->orderBy("id", "DESC")
            ->paginate(10);

        $user = auth()->user();

        foreach ($posts as $post) {
            $post->is_client_post = $post->client_id == $user->id;
            $post->is_my_friend = Friend::where(function ($query) use (
                $user,
                $post
            ) {
                $query
                    ->where("student_id1", $user->id)
                    ->where("student_id2", $post->client_id);
            })
                ->orWhere(function ($query) use ($user, $post) {
                    $query
                        ->where("student_id1", $post->client_id)
                        ->where("student_id2", $user->id);
                })
                ->exists();

            $post->up_by_client =
                PostUp::where("post_id", $post->id)
                    ->where("client_id", $user->id)
                    ->first() != null;

            $post->friend_request =
                Model::where("notification_type_id", 5)
                    ->where("sender_id", $user->id)
                    ->where("client_id", $post->client_id)
                    ->first() != null;
        }

        return $posts;
    }

    public function getNotifications()
    {
        $user = auth()->user();

        $notifications = Model::where("client_id", $user->id)
            ->with("sender", "post.text", "post.image", "post.client")
            ->orderBy("id", "DESC")
            ->paginate(50);

        foreach ($notifications as $notification) {
            if ($notification->post === null) {
                continue;
            }
            $notification->post->loadCount("ups", "comments");
            $notification->post->is_client_post =
                $notification->post->client_id == $user->id;
            $notification->post->up_by_client =
                PostUp::where("post_id", $notification->post->id)
                    ->where("client_id", $user->id)
                    ->first() != null;
            $post = $notification->post;
            $notification->post->is_my_friend = Friend::where(function (
                $query
            ) use ($user, $post) {
                $query
                    ->where("student_id1", $user->id)
                    ->where("student_id2", $post->client_id);
            })
                ->orWhere(function ($query) use ($user, $post) {
                    $query
                        ->where("student_id1", $post->client_id)
                        ->where("student_id2", $user->id);
                })
                ->exists();

            $post->friend_request =
                Model::where("notification_type_id", 5)
                    ->where("sender_id", $user->id)
                    ->where("client_id", $post->client_id)
                    ->first() != null;
        }

        return $notifications;
    }

    public function getComments(Request $request)
    {
        $this->validate($request, ["post_id" => "bail|required"]);

        $user = auth()->user();

        if($user != null) {
            $comments = Comment::where("post_id", $request->post_id)
            ->whereDoesntHave("client.blocks", function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where("client_id2", $user->id);
                });
            })
            ->with("client.teacher.subjects","client.subscriptions")
            ->withCount("loves")
            ->get();

        foreach ($comments as $comment) {
            $comment->loved_by_client =
                CommentLove::where("comment_id", $comment->id)
                    ->where("client_id", $user->id)
                    ->first() != null;

            $comment->is_my_friend = Friend::where(function ($query) use (
                $user,
                $comment
            ) {
                $query
                    ->where("student_id1", $user->id)
                    ->where("student_id2", $comment->client_id);
            })->exists();

            $comment->friend_request =
                Model::where("notification_type_id", 5)
                    ->where("sender_id", $user->id)
                    ->where("client_id", $comment->client_id)
                    ->first() != null;
                    
                    // Check subscription logic
            if ($comment->client->subscriptions->isEmpty()) {
                // If subscriptions are empty, set as false
                $comment->subscription = false;
            } else {
                // Check if the last subscription's status_id is 2
                $lastSubscription = $comment->client->subscriptions->last(); // Get the last subscription
                if ($lastSubscription && $lastSubscription->status_id == 2) {
                    // If last subscription's status_id is 2, set as false
                    $comment->subscription = false;
                } else {
                    // Otherwise, set as true
                    $comment->subscription = true;
                }
            }
            
            unset($comment->client->subscriptions);
        }

        return response()->json(["comments" => $comments], 200);
        }
        else {
            $comments = Comment::where("post_id", $request->post_id)
            ->with("client.teacher.subjects")
            ->withCount("loves")
            ->get();

        foreach ($comments as $comment) {
            $comment->loved_by_client = false;
            $comment->is_my_friend = false;

            $comment->friend_request = false;
        }

        return response()->json(["comments" => $comments], 200);
        }
    }

    public function addPost(Request $request)
    {
        $this->validate($request, [
            "text => required_without_all:image",
            "image" => "required_without_all:text",
        ]);

        DB::beginTransaction();

        $user = auth()->user();

        $post = new Post();

        $post->client_id = $user->id;

        $flag = $post->save();

        if (!$flag) {
            DB::rollBack();
            return response()->json("", 500);
        }

        if ($request->text) {
            $postText = new PostText();

            $postText->id = $post->id;
            $postText->text = $request->text;

            $flag = $postText->save();

            if (!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }

            $post->text = $postText;
        }

        if ($request->file("image")) {
            $imageName =
                time() . rand(100, 1000) .'.'. $request->file("image")->extension();
            $postImage = new PostImage();

            $postImage->id = $post->id;
            $postImage->image = $imageName;

            $post->image = $imageName;
            if (
                !$request
                    ->file("image")
                    ->move(
                        "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/posts_images/",
                        $imageName
                    )
            ) {
                DB::rollBack();
                return response()->json("profile image error", 500);
            }

            $flag = $postImage->save();

            if (!$flag) {
                DB::rollBack();
                return response()->json("", 500);
            }
            $post->image = $postImage;
        }

        $post->up_by_client = false;
        $post->is_client_post = false;
        $post->friend_request = false;
        DB::commit();
        return response()->json($post, 201);
    }

    public function upPost(Request $request)
    {
        $this->validate($request, [
            "post_id" => "bail|required",
            "up" => "bail|required",
            "poster_id" => "bail|required",
            "post" => "bail|required",
            "sender" => "bail|required",
        ]);

        $user = auth()->user();

        if ($request->up == "1") {
            PostUp::updateOrCreate([
                "post_id" => $request->post_id,
                "client_id" => $user->id,
            ]);

            if ($request->poster_id != $user->id) {
                $poster = User::find($request->poster_id);
                $user->load("student");

                $model = new Model();
                $model->client_id = $request->poster_id;
                $model->sender_id = $user->id;
                $model->post_id = $request->post_id;
                $model->notification_type_id = "1";
                $model->notification = "قام برفع منشروك";

                $model->save();

                $model->post = json_decode($request->post, true);
                $model->sender = json_decode($request->sender, true);

                // Initialize FirebaseNotificationService
                $firebaseService = app(FirebaseNotificationService::class);
                $this->notification->sendSinglFullAppNotification($poster->firebase,'pushNotification', [
                    "type" => "",
                    "room" => 'student_'.$poster->id,
                    "title" => $user->student->name,
                    "image" => $user->student->profile_image,
                    "body" => "قام برفع منشورك على يلا امتحان",
                    "client" => $user->student,
                ]);
            }
        } else {
            PostUp::where("post_id", $request->post_id)
                ->where("client_id", $user->id)
                ->delete();
        }
    }

    public function comment(Request $request)
    {
        $this->validate($request, [
            "post_id" => "bail|required",
            "poster_id" => "bail|required",
            "comment" => "bail|required",
        ]);

        $user = auth()->user();

        $comment = new Comment();

        $comment->post_id = $request->post_id;
        $comment->client_id = $user->id;
        $comment->comment = $request->comment;

        $comment->save();

        $comment->friend_request =
            $request->poster_id == $user->id
                ? false
                : Model::where("notification_type_id", 5)
                        ->where("sender_id", $user->id)
                        ->where("client_id", $request->poster_id)
                        ->first() != null;

        if ($request->poster_id != $user->id) {
            $poster = User::find($request->poster_id);
            $user->load("student");

            Model::create([
                "client_id" => $request->poster_id,
                "sender_id" => $user->id,
                "post_id" => $request->post_id,
                "notification_type_id" => "2",
                "notification" => "قام بالتعليق على منشورك",
            ]);

            $this->notification->sendSinglFullAppNotification($poster->firebase,'pushNotification', [
                "type" => "2",
                "room" => 'student_'.$poster->id,
                "title" => $user->student->name,
                "image" => $user->student->profile_image,
                "body" => "قام بالتعليق على منشروك في يلا امتحان",
                "client" => $user->student,
            ]);
        }
        return response()->json($comment, 201);
    }

    public function deletePost(Request $request)
    {
        $this->validate($request, ["post_id" => "bail|required"]);

        Post::where("id", $request->post_id)
            ->first()
            ->update(["status_id" => "2"]);
    }

    public function deleteComment(Request $request)
    {
        $this->validate($request, ["comment_id" => "bail|required"]);

        CommentLove::where("comment_id", $request->comment_id)->delete();
        Comment::where("id", $request->comment_id)
            ->first()
            ->delete();
    }

    public function loveComment(Request $request)
    {
        $this->validate($request, [
            "comment_id" => "bail|required",
            "commenter_id" => "bail|required",
            "love" => "bail|required",
        ]);

        $user = auth()->user();

        if ($request->love == "1") {
            CommentLove::updateOrCreate([
                "comment_id" => $request->comment_id,
                "client_id" => $user->id,
            ]);

            if ($request->commenter_id != $user->id) {
                $commenter = User::find($request->commenter_id);
                $user->load("student");

                Model::create([
                    "client_id" => $request->commenter_id,
                    "sender_id" => $user->id,
                    "post_id" => $request->post_id,
                    "notification_type_id" => "3",
                    "notification" => "قام باالاعجاب بتعليقك",
                ]);

                $this->notification->sendSinglFullAppNotification($commenter->firebase,'pushNotification', [
                    "type" => "3",
                    "room" => 'student_'.$commenter->id,
                    "title" => $user->student->name,
                    "image" => $user->student->profile_image,
                    "body" => "قام بالإعجاب بتعليقك على يلا امتحان",
                    "client" => $user->student,
                ]);
            }
        } else {
            CommentLove::where("comment_id", $request->comment_id)
                ->where("client_id", $user->id)
                ->delete();
        }
    }

    public function reportPost(Request $request)
    {
        $this->validate($request, [
            "complaint" => "bail|required",
            "post_id" => "bail|required|exists:posts,id",
            "complaint_type_id" =>
                "bail|required|exists:questions_complaints_types,id",
        ]);
        DB::beginTransaction();

        $user = auth()->user();

        $complaint = new Complaint();

        $complaint->user_id = auth()->user()->id;
        $complaint->complaint = $request->complaint;
        $complaint->complaint_type_id = $request->complaint_type_id;

        $flag = $complaint->save();

        if ($flag) {
            $postComplaint = new PostComplaint();

            $postComplaint->id = $complaint->id;
            $postComplaint->post_id = $request->post_id;

            $flag = $postComplaint->save();

            if ($flag) {
                DB::commit();
                return response()->json("", 201);
            }
        } else {
            return response()->json("", 500);
            DB::rollBack();
        }
    }

    public function sendFriendRequest(Request $request)
    {
        // Validate the request data
        $this->validate($request, [
            "student_id" => "bail|required|exists:students,id",
        ]);

        // Get the authenticated user and their student relationship
        $user = auth()->user();
        $student = User::find($request->student_id);

        // Check if the target student has previously sent a friend request to the authenticated user
        $previousRequest = Model::where([
            ["sender_id", "=", $request->student_id],
            ["client_id", "=", $user->id],
            ["notification_type_id", "=", 4],
        ])->first();

        // Initialize FirebaseNotificationService
        $firebaseService = app(FirebaseNotificationService::class);

        if ($previousRequest) {
            // Add a row to the friends table to establish the friendship
            $friendshipExists = DB::table("friends")
                ->where(function ($query) use ($user, $request) {
                    $query
                        ->where([
                            ["student_id1", "=", $user->id],
                            ["student_id2", "=", $request->student_id],
                        ])
                        ->orWhere([
                            ["student_id1", "=", $request->student_id],
                            ["student_id2", "=", $user->id],
                        ]);
                })
                ->exists();

            if (!$friendshipExists) {
                $conv = new Conversation();

                $conv->student_id1 = $user->id;
                $conv->student_id2 = $request->student_id;

                $conv->save();
                DB::table("friends")->insert([
                    "student_id1" => $user->id,
                    "student_id2" => $request->student_id,
                    "created_at" => now(),
                    "conversation_id" => $conv->conversation_id,
                ]);
            }

            // Update the previous notification to indicate the friendship
            $previousRequest->notification_type_id = 6; // Assuming 5 is for "Friends now"
            $previousRequest->notification = "انت و هو صديقاً الان";
            $previousRequest->date = now(); // Assuming you have a date column
            $previousRequest->save();

            // Prepare notification data for the friend request update
            $title = $user->student->name;
            $body = "قام بالموافقة على طلب الصداقة";

            // Send notification
            $result = $firebaseService->sendNotification($student->firebase, [
                "type" => "6",
                "notification" => $model,
                "title" => $title,
                "image" => $user->student->profile_image,
                "body" => $body,
                "client" => $user->student,
            ]);

            // Return success response
            if ($result["success"]) {
                return response()->json(
                    [
                        "message" =>
                            "Friend request updated and friendship established.",
                    ],
                    200
                );
            } else {
                return response()->json(
                    ["message" => "Failed to send notification."],
                    500
                );
            }
        } else {
            // Check if a friend request has already been sent by the authenticated user
            $existingNotification = Model::where([
                ["sender_id", "=", $user->id],
                ["client_id", "=", $request->student_id],
                ["notification_type_id", "=", 5],
            ])->first();

            if ($existingNotification) {
                // Update the existing notification's date
                $existingNotification->date = now(); // Assuming you have a date column
                $existingNotification->save();

                // Prepare notification data for the updated friend request
                $title = $user->student->name;
                $body = "أرسل لك طلب صداقة";

                // Send notification
                $result = $firebaseService->sendNotification(
                    $student->firebase,
                    [
                        "type" => "5",
                        "notification" => $existingNotification,
                        "title" => $title,
                        "image" => $user->student->profile_image,
                        "body" => $body,
                        "client" => $user->student,
                    ]
                );

                // Return success response
                if ($result["success"]) {
                    return response()->json(
                        ["message" => "Friend request updated successfully."],
                        200
                    );
                } else {
                    return response()->json(
                        ["message" => "Failed to send notification."],
                        500
                    );
                }
            } else {
                // Create and save a new notification model
                $model = new Model(); // Assuming Model is the correct model name
                $model->client_id = $request->student_id;
                $model->sender_id = $user->id;
                $model->post_id = 1; // Adjust as needed
                $model->notification_type_id = 5; // Adjust to match your schema
                $model->notification = "أرسل لك طلب صداقة";
                $model->date = now(); // Assuming you have a date column
                $model->save();

                // Prepare notification data for the new friend request
                $title = $user->student->name;
                $body = "أرسل لك طلب صداقة";

                // Send notification
                $result = $firebaseService->sendNotification(
                    $student->firebase,
                    [
                        "type" => "5",
                        "notification" => $model,
                        "title" => $title,
                        "image" => $user->student->profile_image,
                        "body" => $body,
                        "client" => $user->student,
                    ]
                );

                // Return success response
                if ($result["success"]) {
                    return response()->json(
                        ["message" => "Friend request sent successfully."],
                        200
                    );
                } else {
                    return response()->json(
                        ["message" => "Failed to send notification."],
                        500
                    );
                }
            }
        }
    }

    public function blockStudent(Request $request)
    {
        // Validate the request
        $validated = $this->validate($request, [
            "student_id" => "bail|required|exists:students,id",
        ]);

        $requesterId = auth()->user()->id;
        $blockedClientId = $validated["student_id"];

        // Check if a block record already exists
        $existingBlock = Block::where(function ($query) use (
            $requesterId,
            $blockedClientId
        ) {
            $query
                ->where("client_id1", $requesterId)
                ->where("client_id2", $blockedClientId);
        })->first();

        if ($existingBlock) {
            return response()->json(
                ["message" => "Block record already exists."],
                200
            );
        }

        // Start transaction
        DB::beginTransaction();

        try {
            // Create a block record
            Block::insert([
                [
                    "client_id1" => $requesterId,
                    "client_id2" => $blockedClientId,
                    "blocker" => 1,
                ],
                [
                    "client_id1" => $blockedClientId,
                    "client_id2" => $requesterId,
                    "blocker" => 0,
                ],
            ]);

            // Remove comments made by the blocked client on the requester's posts
            Comment::where("client_id", $blockedClientId)
                ->whereHas("post", function ($query) use ($requesterId) {
                    $query->where("client_id", $requesterId);
                })
                ->delete();

            Comment::where("client_id", $requesterId)
                ->whereHas("post", function ($query) use ($blockedClientId) {
                    $query->where("client_id", $blockedClientId);
                })
                ->delete();

            // Remove ups related to the posts of the requester where the blocked client is involved
            PostUp::where("client_id", $blockedClientId)
                ->whereHas("post", function ($query) use ($requesterId) {
                    $query->where("client_id", $requesterId);
                })
                ->delete();

            PostUp::where("client_id", $requesterId)
                ->whereHas("post", function ($query) use ($blockedClientId) {
                    $query->where("client_id", $blockedClientId);
                })
                ->delete();

            $inputId = $requesterId;
            $input1Id = $blockedClientId;

            Friend::where(function ($query) use ($inputId, $input1Id) {
                $query
                    ->where("student_id1", $inputId)
                    ->where("student_id2", $input1Id);
            })
                ->orWhere(function ($query) use ($inputId, $input1Id) {
                    $query
                        ->where("student_id1", $input1Id)
                        ->where("student_id2", $inputId);
                })
                ->delete();

            $conv = Conversation::where(function ($query) use (
                $inputId,
                $input1Id
            ) {
                $query
                    ->where("student_id1", $inputId)
                    ->where("student_id2", $input1Id);
            })
                ->orWhere(function ($query) use ($inputId, $input1Id) {
                    $query
                        ->where("student_id1", $input1Id)
                        ->where("student_id2", $inputId);
                })
                ->first();

            if($conv) {
                Message::where("conversation_id", $conv->id)->delete();

            $conv->delete();
            }

            // Commit transaction
            DB::commit();

            return response()->json(
                ["message" => "Client blocked and associated data removed."],
                200
            );
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            return response()->json(
                ["error" => "An error occurred while processing your request.".$e],
                500
            );
        }
    }

    public function getBlockedStudents()
    {
        $user = auth()->user(); // Get the authenticated user

        // Fetch blocked users
        $blockedUsers = Block::where("client_id1", $user->id)
            ->where("blocker", 1)
            ->with("student") // Eager load the student relation
            ->paginate(200);

        return $blockedUsers;
    }

    public function unblockStudent(Request $request)
    {
        $validated = $this->validate($request, [
            "student_id" => "bail|required|exists:students,id",
        ]);

        $user = auth()->user();

        Block::where(function ($query) use ($user, $request) {
            $query
                ->where("client_id1", $user->id)
                ->where("client_id2", $request->student_id);
        })
            ->orWhere(function ($query) use ($user, $request) {
                $query
                    ->where("client_id1", $request->student_id)
                    ->where("client_id2", $user->id);
            })
            ->delete();
    }

    public function acceptFriendRequest(Request $request)
    {
        $validated = $this->validate($request, [
            "notification_id" => "bail|required|exists:notifications,id",
        ]);
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $notification = model::find($request->notification_id);

            $conv = new Conversation();

            $conv->student_id1 = $user->id;
            $conv->student_id2 = $notification->sender_id;

            $conv->save();

            Friend::insert([
                [
                    "student_id1" => $user->id,
                    "student_id2" => $notification->sender_id,
                    "conversation_id" => $conv->id,
                ],
                [
                    "student_id1" => $notification->sender_id,
                    "student_id2" => $user->id,
                    "conversation_id" => $conv->id,
                ],
            ]);

            $data = [
                [
                    "client_id" => $notification->sender_id,
                    "sender_id" => $user->id,
                    "post_id" => 1, // Adjust as needed
                    "notification_type_id" => 6, // Adjust to match your schema
                    "notification" => "قام بقبول طلب الصداقة، أنتم الآن أصدقاء",
                    "date" => now(), // Assuming you have a date column
                ],
                [
                    "client_id" => $user->id,
                    "sender_id" => $notification->sender_id,
                    "post_id" => 1, // Adjust as needed
                    "notification_type_id" => 6, // Adjust to match your schema
                    "notification" => "أنت وهو أصدقاء الآن",
                    "date" => now(), // Assuming you have a date column
                ],
            ];

            // Insert the data into the database
            Model::insert($data);

            $model = new Model();
            $model->client_id = $notification->sender_id;
            $model->sender_id = $user->id;
            $model->post_id = 1;
            $model->notification_type_id = "6";
            $model->notification = "قام بقبول طلب الصداقة، أنتم الآن أصدقاء";

            $reciver = User::find($notification->sender_id);
            $reciver->student = $reciver->load("student")->student;
            $notification->delete();

            $message = new Message();

            $message->sender_id = $user->id;
            $message->message = "نحن اصدقاء الآن، دعنا نتحدث";
            $message->conversation_id = $conv->id;
            $message->is_system = 1;

            $message->save();

            DB::commit();
            $user->student = $user->load("student")->student;

            $firebaseService = app(FirebaseNotificationService::class);
            $firebaseService->sendNotification($reciver->firebase, [
                "type" => "6",
                "notification" => $model,
                "title" => $user->student->name,
                "image" => $user->student->profile_image,
                "body" => "قام بقبول طلب الصداقة، أنتم الآن أصدقاء",
                "client" => $user->student,
                "student_id1" => $user->id,
                "student_id2" => $notification->sender_id,
            ]);

            $conv1 = Conversation::where("id", $conv->id)
                ->with([
                    "messages" => function ($query) use ($user) {
                        $query->latest()->take(1); // Limit to the latest 50 messages
                    },
                ])
                ->with([
                    "friend" => function ($query) use ($conv) {
                        $query->where("student_id1", $conv->student_id1); // Order messages by id in descending order
                    },
                ])
                ->withCount([
                    "messages as user_message_count" => function ($query) {
                        return "1";
                    },
                ])
                ->with("student1", "student2")
                ->first();

            $conv2 = Conversation::where("id", $conv->id)
                ->with([
                    "messages" => function ($query) use ($user) {
                        $query->latest()->take(1); // Limit to the latest 50 messages
                    },
                ])
                ->with([
                    "friend" => function ($query) use ($conv) {
                        $query->where("student_id1", $conv->student_id2); // Order messages by id in descending order
                    },
                ])
                ->withCount([
                    "messages as user_message_count" => function ($query) {
                        return "1";
                    },
                ])
                ->with("student1", "student2")
                ->first();

            $this->notification->requestSocket(
                [
                    "room" => "student_" . $conv->student_id1,
                    "conv" => $conv1->toArray(),
                    "conversation_id" => $conv->id,
                ],
                "pushMessage"
            );
            $this->notification->requestSocket(
                [
                    "room" => "student_" . $conv->student_id2,
                    "conv" => $conv2->toArray(),
                    "conversation_id" => $conv->id,
                ],
                "pushMessage"
            );

            $model = new Model();
            $model->client_id = $user->id;
            $model->sender_id = $notification->sender_id;
            $model->post_id = 1;
            $model->notification_type_id = "6";
            $model->notification = "قام بقبول طلب الصداقة، أنتم الآن أصدقاء";
            $firebaseService->sendNotification($user->firebase, [
                "type" => "6",
                "notification" => $model,
                "title" => $reciver->student->name,
                "image" => $reciver->student->profile_image,
                "body" => "أنت وهو الآن أصدقاء",
                "client" => $reciver->student,
                "student_id1" => $user->id,
                "student_id2" => $notification->sender_id,
            ]);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            return response()->json(["error" => $e], 500);
        }
    }

    public function removeFriendRequest(Request $request)
    {
        $validated = $this->validate($request, [
            "notification_id" => "bail|required|exists:notifications,id",
        ]);

        Model::destroy($request->notification_id);
    }

    public function cancelFriendship(Request $request)
    {
        $validated = $this->validate($request, [
            "student_id" => "bail|required|exists:students,id",
        ]);
        $user = auth()->user();
        $inputId = $user->id;
        $input1Id = $request->student_id;
        $friend = Friend::where("student_id1", $user->id)
            ->where("student_id2", $request->student_id)
            ->first();
        Friend::where(function ($query) use ($inputId, $input1Id) {
            $query
                ->where("student_id1", $inputId)
                ->where("student_id2", $input1Id);
        })
            ->orWhere(function ($query) use ($inputId, $input1Id) {
                $query
                    ->where("student_id1", $input1Id)
                    ->where("student_id2", $inputId);
            })
            ->delete();
        Message::where("conversation_id", $friend->conversation_id)->delete();
        Conversation::destroy($friend->conversation_id);
    }
}
