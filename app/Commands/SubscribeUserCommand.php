<?php namespace App\Commands;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Handlers\Commands\SubscribeUserCommandHandler;

class SubscribeUserCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * array of all fields submitted
     * @var Array
     */
    protected $input;

    /**
     * company note
     * @var text
     */
    public $note;

    /**
     * array of comapny fields
     * @var Array
     */
    public $companyData;

    /**
     * array of subscriber fields
     * @var Array
     */

    public $userData;

    /**
     * array of subscriber profile fields
     * @var Array
     */

    public $userProfileData;

    /**
     * array of subscriber profile fields
     * @var Array
     */

    public $productId;

    /**
     * String of time-zone name..
     * @var String
     */
    public $timezone = null;

    /**
     * Consturctor function
     * @var $inputs Array
     * @return Void
     */
    public function __construct($input)
    {
        $this->input = $input;
        $this->extractInput();
        $this->note = isset($input['notes']) ? $input['notes'] : null;
        $this->productId = $input['product_id'];
    }
    
    public function handle()
    {
        $commandHandler = \App::make(SubscribeUserCommandHandler::class);
        
        return $commandHandler->handle($this);
    }

    /**
     * Extract input to respective Model
     * @return void
     */
    private function extractInput()
    {
        $this->mapCompanyInput();
        $this->mapUserInput();
        $this->mapUserProfileInput();
        if (ine($this->input, 'timezone')) {
            $this->timezone = $this->input['timezone'];
        }
    }

    /**
     * Map  Company Model inputs
     * @return void
     */
    private function mapCompanyInput()
    {
        $map = [
            'name' => 'company_name',
            'office_state' => 'office_state_id',
            'office_country' => 'office_country_id',
            'account_manager_id',
            'office_address',
            'office_address_line_1',
            'office_city',
            'office_zip',
            'office_phone',
            'office_email',
            'office_fax',
            'additional_phone' => 'office_additional_phone',
            'additional_email' => 'office_additional_email'
        ];

        $this->companyData = $this->mapInputs($map);
    }

    /**
     * Map  User Model inputs
     * @return void
     */
    private function mapUserInput()
    {
        $map = [
            'first_name',
            'last_name',
            'email',
            'password'
        ];
        $this->userData = $this->mapInputs($map);
    }


    /**
     * Map  UserProfile Model inputs
     * @return void
     */
    private function mapUserProfileInput()
    {
        $map = [
            'user_id',
            'phone' => 'office_phone',
            'address' => 'office_address',
            'address_line_1' => 'office_address_line_1',
            'city' => 'office_city',
            'state_id' => 'office_state_id',
            'country_id' => 'office_country_id',
            'zip' => 'office_zip'
        ];
        $this->userProfileData = $this->mapInputs($map);
    }

    /**
     * Map  Model fields to inputs
     * @return void
     */
    private function mapInputs($map)
    {
        $ret = [];
        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($this->input[$value]) ? $this->input[$value] : "";
            } else {
                $ret[$key] = isset($this->input[$value]) ? $this->input[$value] : "";
            }
        }

        return $ret;
    }
}
