<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateSubscriptions extends Command
{
    // The name and signature of the console command.
    protected $signature = 'subscriptions:update';

    // The console command description.
    protected $description = 'Update subscription status if the date exceeds 7 or 30 days based on the package';

    // Execute the console command.
    public function handle()
{
    // Get the current date and time.
    $currentDateTime = Carbon::now();

    // Get the date 7 days ago and 30 days ago for comparison
    $sevenDaysAgo = $currentDateTime->subDays(7)->toDateString(); // get only date part
    $thirtyDaysAgo = $currentDateTime->subDays(30)->toDateString(); // get only date part

    // Update subscriptions in a single query
    DB::table('subscriptions')
        ->where('status_id',  1)
        ->where('subscription_package_id', '!=', 3)
        ->where(function($query) use ($sevenDaysAgo, $thirtyDaysAgo) {
            $query->where(function($subQuery) use ($sevenDaysAgo) {
                $subQuery->where('subscription_package_id', 1) // weekly
                         ->whereDate('date', '<=', $sevenDaysAgo); // compare only date
            })
            ->orWhere(function($subQuery) use ($thirtyDaysAgo) {
                $subQuery->where('subscription_package_id', 2) // monthly
                         ->whereDate('date', '<=', $thirtyDaysAgo); // compare only date
            });
        })
        ->update(['status_id' => 2]);

    // Output a success message
    $this->info("Subscriptions updated successfully.");
    $this->logMessage("Subscriptions updated successfully.");
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
