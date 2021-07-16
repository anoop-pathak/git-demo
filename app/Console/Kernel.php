<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('command:delete_temp_proposal_pdf')->daily();
        $schedule->command('command:delete_terminated_company_data')->daily();
        $schedule->command('command:document_expire_post_notification')->daily();
        $schedule->command('command:document_expire_pre_notification')->daily();
        $schedule->command('command:geocoding')->daily();
        $schedule->command('command:get_email_reply')->everyMinute();
        $schedule->command('command:get_feed')->cron('0 */8 * * *');
        $schedule->command('command:remove_follow_up_remainder')->cron('0 */8 * * *');
        $schedule->command('command:send_appointment_reminders')->everyMinute();
        $schedule->command('command:send_schedule_reminders')->everyMinute();
        // $schedule->command('command:update_srs_customer_products')->daily()->addHours(6);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
