<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PostImage;
use App\Models\Notification;

class DeleteOldData extends Command
{
    protected $signature = 'data:delete-old';
    protected $description = 'Delete data older than one month';

    public function handle()
    {
        $thresholdDate = Carbon::now()->subMonth();

        DB::transaction(function () use ($thresholdDate) {
            // Delete from various tables
            DB::table('messages')->where('created_at', '<', $thresholdDate)->delete();
            DB::table('comments')->where('date', '<', $thresholdDate)->delete();
            DB::table('posts_ups')->where('created_at', '<', $thresholdDate)->delete();
            DB::table('posts_texts')->where('created_at', '<', $thresholdDate)->delete();
            
            // Get and delete post images
            $postsImages = PostImage::where('created_at', '<', $thresholdDate)->get();
            
            foreach ($postsImages as $postImage) {
                $filePath = "/home/nubikkce/yalla-emtihan.com/yalla-emtihan/public/videos/" . $postImage->image;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Finally, delete images and posts
            DB::table('posts_images')->where('created_at', '<', $thresholdDate)->delete();
            DB::table('posts')->where('date', '<', $thresholdDate)->delete();
            
            $notifications = Notification::where('date', '<', $thresholdDate)->whereNotIn('post_id', function($query) {
                $query->select('id')->from('posts');
            })->get();
            
        });

        $this->info('Old data deleted successfully.');
        $this->logMessage('Old data deleted successfully.');
    }
    function logMessage($message) {
    // Get the current date and time for the filename
    $date = date('Y-m-d');
    $filename = storage_path("logs/yalla_emtihan_message_log_$date.txt");

    // Create the log directory if it doesn't exist
    if (!file_exists(storage_path('logs'))) {
        if (!mkdir(storage_path('logs'), 0755, true) && !is_dir(storage_path('logs'))) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', storage_path('logs')));
        }
    }

    // Prepare the log entry
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";

    // Write the log entry to the file
    if (file_put_contents($filename, $logEntry, FILE_APPEND) === false) {
        throw new \RuntimeException(sprintf('Unable to write to file "%s"', $filename));
    }
}
}
