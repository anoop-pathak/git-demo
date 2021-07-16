<?php namespace App\Commands;

use Illuminate\Support\Facades\Auth;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class NewProspectCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $addressFields = ['address', 'address_line_1', 'city', 'state_id', 'country_id', 'zip'];
    private $contactFields = [
        'job_id',
        'first_name',
        'last_name',
        'email',
        'additional_emails',
        'phone',
        'additional_phones',
        'address',
        'address_line_1',
        'city',
        'state_id',
        'country_id',
        'zip',
        'lat',
        'long',
    ];
    private $customerContactFields = [
        'customer_id',
        'first_name',
        'last_name',
        'email',
        'additional_emails',
        'phone',
        'additional_phones',
        'address',
        'address_line_1',
        'city',
        'state_id',
        'country_id',
        'zip',
        'lat',
        'long'
    ];
    protected $input;
    public $jobData = [];
    public $customerData;
    public $addressData;
    public $billingAddressData;
    public $phonesData;
    public $isSameAsCustomerAddress;
    public $tempId = null; //Id associated to temporally imported customer.
    public $flags = false;
    public $customerContacts = [];

    public function __construct($input)
    {
        $this->input = $input;
        $this->extractInput($input);
        $this->isSameAsCustomerAddress = $input['billing']['same_as_customer_address'];
        $this->phonesData = $input['phones'];
        $this->tempId = ine($input, 'temp_id') ? $input['temp_id'] : null;
    }
    
    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\NewProspectCommandHandler::class);
        
        return $commandHandler->handle($this);
    }

    private function extractInput($input)
    {

        $this->mapCustomerInput();
        $this->setCreatedBy();
        if (isset($input['flag_ids'])) {
            $this->flags = $input['flag_ids'];
        }
        if (isset($this->input['jobs']) && is_array($this->input['jobs']) && !empty($input['jobs'])) {
            $this->mapJobInput();
        }

        $this->mapAddressInput();

        if (ine($input, 'customer_contacts')) {
            $this->mapCustomerContactsInput();
        }
    }

    /**
     * Map  Customer Model inputs
     * @return void
     */
    private function mapCustomerInput()
    {
        $map = [
            'id',
            'first_name',
            'last_name',
            'company_name',
            'email',
            'additional_emails',
            'rep_id',
            'referred_by' => 'referred_by_id',
            'referred_by_type',
            'referred_by_note',
            'appointment_required',
            'call_required',
            'note',
            'is_commercial',
            'management_company',
            'property_name',
        ];

        $this->customerData = $this->mapInputs($map);
    }

    /**
     *  map jobs input data and also map jobs location.
     * @return void.
     */
    private function mapJobInput()
    {
        $map = [
            'trades',
            'job_type_id',
            'name',
            'description',
            'rep_ids',
            'estimator_ids',
            'created_by',
            'last_modified_by',
            'same_as_customer_address',
            'amount',
            'other_trade_type_description',
            'call_required',
            'appointment_required',
            'work_types',
            'flag_ids',
            'job_types',
            'labour_ids',
            'sub_contractor_ids',
            'alt_id',
            'division_id',
            'duration',
            'contact_same_as_customer',
            'multi_job',
            'parent_id',
        ];

        // set jobs.
        $this->jobData = [];
        foreach ($this->input['jobs'] as $jobKey => $job) {
            $job['created_by'] = \Auth::id();
            $job['last_modified_by'] = \Auth::id();
            $job = $this->setJobRepId($job);
            $this->jobData[$jobKey] = $this->mapInputs($map, $job);
            $this->jobData[$jobKey]['address'] = $this->mapInputs($this->addressFields, $job);
            $this->jobData[$jobKey]['contact'] = [];
            if (ine($job, 'contact')) {
                $this->jobData[$jobKey]['contact'] = $this->mapInputs($this->contactFields, $job['contact']);
            }
        }
    }

    /**
     *  map customer locations input data.
     */
    private function mapAddressInput()
    {
        $this->addressData = $this->mapFirstSubInputs($this->addressFields, 'address');
        if (!$this->isSameAsCustomerAddress) {
            $this->billingAddressData = $this->mapFirstSubInputs($this->addressFields, 'billing');
        }
    }

    /**
     *  map customer_contacts locations input data.
     */
    private function mapCustomerContactsInput()
    {
        foreach ($this->input['customer_contacts'] as $key => $contact) {
            $this->customerContacts[$key] = $this->mapInputs($this->customerContactFields, $contact);
        }
    }

    /**
     *  set id of created by user.
     */
    private function setCreatedBy()
    {
        $this->customerData['created_by'] = \Auth::id();
        $this->customerData['last_modified_by'] = \Auth::id();
    }

    /**
     *  set id of rep id in job data.
     */
    private function setJobRepId($job)
    {
        if (!isset($job['rep_id']) || empty($job['rep_id'])) {
            $job['rep_id'] = $this->customerData['rep_id'];
        }
        return $job;
    }

    /**
     * Map  Model fields to inputs
     * @return void
     */
    private function mapInputs($map, $input = [])
    {
        $ret = [];

        // empty the set default.
        if (empty($input)) {
            $input = $this->input;
        }

        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($input[$value]) ? $input[$value] : "";
            } else {
                $ret[$key] = isset($input[$value]) ? $input[$value] : "";
            }
        }

        return $ret;
    }

    /**
     *  Map  Model fields to inputs
     * @return array of mapped array fields.
     */
    private function mapFirstSubInputs($map, $inputKey)
    {
        $ret = [];
        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($this->input[$inputKey][$value]) ? $this->input[$inputKey][$value] : "";
            } else {
                $ret[$key] = isset($this->input[$inputKey][$value]) ? $this->input[$inputKey][$value] : "";
            }
        }

        return $ret;
    }
}
