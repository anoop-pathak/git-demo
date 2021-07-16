<?php namespace App\Handlers\Commands;

use App\Events\UserUpdated;
use App\Repositories\UserRepository;
use App\Services\Subscriptions\SubscriptionServices;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class UserUpdateCommandHandler
{

    /**
     *  Command Object
     * @var App\Users\UserCreateCommand
     */
    private $command;
    protected $subscriptionServices;

    public function __construct(UserRepository $userRepo, SubscriptionServices $subscriptionServices)
    {
        $this->userRepo = $userRepo;
        $this->subscriptionServices = $subscriptionServices;
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
        DB::beginTransaction();
        try {
            $user = $this->userRepo->update(
                $command->userId,
                $command->userData,
                $command->userProfileData,
                $command->departments
            );
            if ($user->isOwner()) {
                $this->subscriptionServices->updateSubscriptionAccount($user);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        if ($user->isNonLoggable() || $user->isSubContractorPrime()) {
            return $user;
        }

        Event::fire('JobProgress.Users.Events.UserUpdated', new UserUpdated($user, $command->input));
        return $user;
    }
}
