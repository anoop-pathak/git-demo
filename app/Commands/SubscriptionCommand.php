<?php namespace App\Commands;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SubscriptionCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $company_id;

    /**
     * @param string company_id
     */
    public function __construct($company_id)
    {
        $this->company_id = $company_id;
    }
    
    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\SubscriptionCommandHandler::class);
        
        return $commandHandler->handle($this);
    }
}
