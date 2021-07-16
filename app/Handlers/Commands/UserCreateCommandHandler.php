<?php namespace App\Handlers\Commands;

use App\Events\UserWasCreated;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class UserCreateCommandHandler
{

    /**
     *  Command Object
     * @var App\Users\UserCreateCommand
     */
    private $command;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
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
        $user = null;
        if(!$command->stopDBTransaction) {
            DB::beginTransaction();
        }
        try {
            $user = $this->userRepo->register(
                $command->userData,
                $command->userProfileData,
                $command->group,
                $command->company,
                $command->departments
            );
            $this->assignDivisions($user);
        } catch (\Exception $e) {
            if(!$command->stopDBTransaction) {
                DB::rollback();
            }
            throw $e;
        }
        if(!$command->stopDBTransaction) {
            DB::commit();
        }

        if ($user->isNonLoggable() || $user->isSubContractorPrime()) {
            return $user;
        }

        Event::fire('JobProgress.Users.Events.UserWasCreated', new UserWasCreated($user, $command->input));

        return $user;
    }

    private function assignDivisions($user)
    {
        if (!empty($this->command->divisions)) {
            $this->userRepo->assignDivisions(
                $user,
                $this->command->divisions
            );
        }
    }
}
