<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\DeleteOldData::class,
        \App\Console\Commands\SendMotivationalQuote::class,
        \App\Console\Commands\UpdateSubscriptions::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('data:delete-old')->daily();
        $schedule->command('send:motivation')->everyThreeHours();
        $schedule->command('subscriptions:update')->everyThreeHours();
    }
}
