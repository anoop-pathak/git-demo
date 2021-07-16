<?php namespace App\Commands;

use App\Handlers\Commands\SubscriberUpdateCommandHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SubscriberUpdateCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * array of all fields submitted
     * @var Array
     */
    public $input;

    /**
     * array of comapny fields
     * @var Array
     */
    public $companyData;

    public function __construct($input)
    {
        $this->input = $input;
        $this->mapCompanyInput();
    }

    public function handle()
    {
        $commandHandler = \App::make(SubscriberUpdateCommandHandler::class);

        return $commandHandler->handle($this);
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
            'additional_email' => 'office_additional_email',
            'license_numbers',
        ];

        $this->companyData = $this->mapInputs($map);
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
