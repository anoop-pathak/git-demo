<?php namespace App\Commands;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UserUpdateCommand extends AbstractUserCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * array of all fields submitted
     * @var Array
     */
    public $input;

    /**
     * company id
     * @var int
     */
    public $userId;

    /**
     * array of division ids
     * @var Array
     */
    public $divisions = [];

    /**
     * Consturctor function
     * @var $inputs Array
     * @return Void
     */
    public function __construct($input)
    {
        $this->input = $input;
        parent::__construct($input);

        $this->extractInput();
    }

    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\UserUpdateCommandHandler::class);

        return $commandHandler->handle($this);
    }

    private function extractInput()
    {

        $this->userId = $this->input['id'];

        /*change email*/
        if (ine($this->input, 'email')) {
            $this->userData['email'] = $this->input['email'];
        }

        /*set password in user data if not empty*/
        if (ine($this->input, 'password')) {
            $this->userData['password'] = $this->input['password'];
        }

        if (isset($this->userData['group_id']) && !in_array($this->userData['group_id'], User::getNonLoggableGroups())) {
            unset($this->userData['group_id']);
        }

        if (ine($this->input, 'division_ids')) {
            $this->divisions = (array) $this->input['division_ids'];
        }

        /*set all divisions access in user data if not empty*/
        if(isset($this->input['all_divisions_access'])) {
            $this->userData['all_divisions_access'] = $this->input['all_divisions_access'];
        }
    }
}
