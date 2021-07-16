<?php

namespace App\Handlers\Commands;

use App\Repositories\WorkflowRepository;

class WorkflowCommandHandler
{

    /**
     *  Command Object
     * @var App\Workflow\Commander\WorkflowCommand
     */
    private $command;

    protected $workflowRepo;

    public function __construct(WorkflowRepository $repo)
    {
        $this->workflowRepo = $repo;
    }

    /**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
    public function handle($command)
    {
        $this->command = $command;
        $this->workflowRepo->create($command->workflow, $command->company_id);
    }
}
