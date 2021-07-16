<?php namespace App\Commands;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UserCreateCommand extends AbstractUserCommand implements ShouldQueue
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
    public $company;

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
        $commandHandler = \App::make(\App\Handlers\Commands\UserCreateCommandHandler::class);

        return $commandHandler->handle($this);
    }

    private function extractInput()
    {

        $this->company = $this->input['company_id'];

        /*set email and password in user data*/
        $this->userData['email'] = isset($this->input['email']) ? $this->input['email'] : "";

        // password may empty in case of non loggable user..
        $this->userData['password'] = isset($this->input['password']) ? $this->input['password'] : "";

        if (ine($this->input, 'division_ids')) {
            $this->divisions = (array) $this->input['division_ids'];
        } else {
            $this->userData['all_divisions_access'] = true;
       }

       $this->stopDBTransaction = ine($this->input, 'stop_db_transaction');
    }
}
