<?php namespace App\Commands;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WorkflowCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * array of all fields submitted
     * @var Array
     */
    protected $input;

    public $workflow;

    public $company_id;

    /**
     * Consturctor function
     * @var $inputs Array
     * @return Void
     */
    public function __construct($input)
    {
        $this->input = $input;
        $this->company_id = \config('company_scope_id');
        $this->workflow = json_decode($this->input, true);
    }
    
    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\WorkflowCommandHandler::class);
        
        return $commandHandler->handle($this);
    }

}
