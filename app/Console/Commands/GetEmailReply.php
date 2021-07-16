<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class GetEmailReply extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:get_email_reply';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To get email reply.';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $repo;
    protected $mailbox;
    protected $resourcesRepo;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $service = App::make(\App\Services\Emails\ImapService::class);
        $service->recieveMails();
    }
}
