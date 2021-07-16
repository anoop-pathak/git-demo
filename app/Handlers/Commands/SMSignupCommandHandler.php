<?php namespace App\Handlers\Commands;

use App\Repositories\SMRepository;
use App\Services\Contexts\Context;
use App\Services\SkyMeasure\SkyMeasure;
use Illuminate\Support\Facades\DB;

class SMSignupCommandHandler
{

    protected $command;
    protected $scope;
    protected $service;
    protected $repo;

    public function __construct(Context $scope, SkyMeasure $service, SMRepository $repo)
    {
        $this->scope = $scope;
        $this->service = $service;
        $this->repo = $repo;
    }

    /**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
    public function handle($command)
    {
        DB::beginTransaction();
        try {
            $this->service->signup($command->signupDetails);

            $token = $this->service->authentication($command->username, $command->password);

            $client = $this->repo->saveClient($command->username, $token);
        } catch (\Exception $e) {
            // Log::error($e);
            DB::rollback();
            throw $e;
        }
        DB::commit();

        return $client;
    }
}
