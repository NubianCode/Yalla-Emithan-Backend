<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\Video;
use App\Models\WatchedVideo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use App\Jobs\ConvertVideoToHLS;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\VideoStream;
use DB;
use DateTime;

class VideoController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api", ["except" => ["watchVideo","streamVideo"]]);
        $this->notification = new Notification();
    }
    public function cleanUpVideos()
    {
        // Paths to the directories
        $videosFolderPath =
            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/videos";
        $backupFolderPath =
            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/backup";

        // Count the number of files in the videos folder excluding directories
        $videoFiles = glob($videosFolderPath . "/*"); // Get all files and directories
        $videoCount = 0;
        foreach ($videoFiles as $file) {
            if (is_file($file)) {
                $videoCount++;
            }
        }

        // Count the number of files in the backup folder excluding directories
        $backupFiles = glob($backupFolderPath . "/*"); // Get all files and directories
        $backupCount = 0;
        foreach ($backupFiles as $file) {
            if (is_file($file)) {
                $backupCount++;
            }
        }

        // Return the counts as JSON response
        return response()->json([
            "videos_count" => $videoCount,
            "backup_count" => $backupCount,
        ]);
    }

    private $notification;
    
    public function uploadVideo(Request $request)
    {
        // Validate the incoming request
        $this->validate($request, [
            "video" => "required|file|mimes:mp4|max:102400", // 100 MB max
            "subject_id" => "required",
            "duration" => "required",
            "name" => "required|string",
        ]);

        $video = $request->file("video");
        $videoName =
            time() .
            rand(100, 1000) .
            "." .
            $video->getClientOriginalExtension();

        // Define the target directory for storing videos
        $videoDirectory =
            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/videos/";
        $tempDirectory =
            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/temp/";

        $videoPath = $videoDirectory . $videoName;
        $tempPath = $tempDirectory . $videoName;

        // Move the uploaded file to the temp directory first
        if (!$video->move($tempDirectory, $videoName)) {
            return response()->json(["error" => "Video upload failed"], 500);
        }

        // Copy the file to the video directory
        if (!copy($tempPath, $videoPath)) {
            return response()->json(
                ["error" => "Failed to copy video to video directory"],
                500
            );
        }

        $video = new Video();

        $video->name = $request->name;
        $video->subject_id = $request->subject_id;
        $video->duration = $request->duration;
        $video->url = $videoName;

        $video->save();

        // Return a response immediately after upload
        return response()->json(
            "Video uploaded successfully. Conversion to HLS is processing in the background.",
            201
        );
    }

    public function editVideo(Request $request)
    {
        // Validate the incoming request
        $this->validate($request, [
            "video_id" => "required", // video_id is required
            "name" => "required|string", // name is required
        ]);

        // Find the video record by ID
        $videoObject = Video::find($request->video_id);

        if (!$videoObject) {
            return response()->json("Video not found", 404);
        }

        // Define the base directory for videos and HLS files
        $videoDirectory =
            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/videos/";
        $tempDirectory =
            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/temp/";
        $hlsDirectory =
            "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/video/hls/";

        // Delete the existing HLS files if they exist
        $videoBaseName = pathinfo($videoObject->url, PATHINFO_FILENAME); // Get the base name of the video (without extension)

        // Find the .m3u8 file and associated .ts files
        $m3u8File = $hlsDirectory . $videoBaseName . ".m3u8";
        $tsFiles = glob($hlsDirectory . $videoBaseName . "*.ts");

        // Delete the .m3u8 file
        if (file_exists($m3u8File)) {
            unlink($m3u8File);
        }

        // Delete all associated .ts files
        foreach ($tsFiles as $tsFile) {
            if (file_exists($tsFile)) {
                unlink($tsFile);
            }
        }

        if ($request->hasFile("video")) {
            // Delete the existing video files
            $videoPath = $videoDirectory . $videoObject->url;
            $tempPath = $tempDirectory . $videoObject->url;

            if (file_exists($videoPath)) {
                unlink($videoPath); // Remove video from video directory
            }

            if (file_exists($tempPath)) {
                unlink($tempPath); // Remove video from temp directory
            }

            // Handle the uploaded video
            $video = $request->file("video");
            $newVideoName =
                time() .
                rand(100, 1000) .
                "." .
                $video->getClientOriginalExtension();

            // Move the uploaded video to the temp directory first
            if (!$video->move($tempDirectory, $newVideoName)) {
                return response()->json("Failed to upload the new video", 500);
            }

            // Copy the video from the temp directory to the videos directory
            $tempVideoPath = $tempDirectory . $newVideoName;
            $finalVideoPath = $videoDirectory . $newVideoName;

            if (!copy($tempVideoPath, $finalVideoPath)) {
                return response()->json(
                    "Failed to move the video to the final directory",
                    500
                );
            }

            // Update the video record with the new video name
            $videoObject->url = $newVideoName;
            $videoObject->duration = $request->duration;
        }

        // Update the name of the video
        $videoObject->name = $request->name;
        $videoObject->save();

        // Return success response
        return response()->json("Video updated successfully", 200);
    }

    public function getVideos(Request $request)
    {
        if ($request->video_id) {
            $videos = Video::where("subject_id", $request->subject_id)
                ->where("id", $request->video_id)
                ->orderBy("date", "desc")
                ->paginate(500);
            return $videos;
        } else {
            $videos = Video::where("subject_id", $request->subject_id)
                ->where("name", "like", "%" . $request->video_name . "%")
                ->orderBy("date", "desc")
                ->paginate(500);
            return $videos;
        }
    }

    public function watchVideo(Request $request)
    {
        $watchedVideo = new WatchedVideo();

        $user = auth()->user();

        $watchedVideo->student_id = $user == null ? 1 : $user->id;
        $watchedVideo->video_id = $request->video_id;
        $watchedVideo->device_id = $request->device_id;

        $watchedVideo->save();

        return response()->json("", 201);
    }

    public function video($filename)
    {
        $videoPath = storage_path("app/videos/" . $filename);

        if (!file_exists($videoPath)) {
            abort(404);
        }

        $fileStream = fopen($videoPath, "r");

        return response()->stream(
            function () use ($fileStream) {
                fpassthru($fileStream);
            },
            200,
            [
                "Content-Type" => "video/mp4",
                "Content-Length" => filesize($videoPath),
                "Content-Disposition" => 'inline; filename="' . $filename . '"',
            ]
        );
    }

    public function updateVideoIndex(Request $request)
    {
        // Validate the incoming request
        $this->validate($request, [
            "video_ids" => "required|array",
            "video_ids.*" => "exists:videos,id",
        ]);

        // Get the array of video IDs from the request
        $videoIds = $request->input("video_ids");

        $numVideos = count($videoIds);

        // Find all videos with the given IDs

        if (!$request->is_up) {
            $videos = Video::whereIn("id", $videoIds)
                ->orderBy("date", "desc")
                ->get();

            $videos[0]->date = $videos[1]->date;
            $videos[0]->save();
            DB::table("videos")
                ->where("subject_id", $request->subject_id)
                ->where("date", ">=", $videos[1]->date)
                ->where("id", "!=", $videos[0]->id)
                ->update([
                    "date" => DB::raw("DATE_ADD(date, INTERVAL 1 MINUTE)"),
                ]);
        } else {
            $videos = Video::whereIn("id", $videoIds)
                ->orderBy("date")
                ->get();

            $videos[0]->date = $videos[1]->date;
            $videos[0]->save();
            DB::table("videos")
                ->where("subject_id", $request->subject_id)
                ->where("date", "<=", $videos[1]->date)
                ->where("id", "!=", $videos[0]->id)
                ->update([
                    "date" => DB::raw("DATE_SUB(date, INTERVAL 1 MINUTE)"),
                ]);
        }
    }

    public function deleteVideo(Request $request)
    {
        // Validate the request data
        $validatedData = $this->validate($request, [
            "video_id" => "required",
        ]);

        // Find the video by its ID
        $video = Video::find($validatedData["video_id"]);

        // Check if the video exists
        if ($video) {
            // Delete related records from WatchedVideo table
            WatchedVideo::where(
                "video_id",
                $validatedData["video_id"]
            )->delete();

            // Delete the video record
            $video->delete();

            // Optionally, return a success message or redirect back
            return response()->json([
                "message" => "Video deleted successfully",
            ]);
        } else {
            // Handle case where video does not exist
            return response()->json(["error" => "Video not found"], 404);
        }
    }
}
