<?php namespace App\Commands;

use Illuminate\Support\Facades\Auth;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CustomerCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $addressFields = ['id', 'address', 'address_line_1', 'city', 'state_id', 'country_id', 'zip', 'lat', 'long'];
    private $customerContactFields = [
        'customer_id',
        'first_name',
        'last_name',
        'email',
        'additional_emails',
        'phones',
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
    public $customerData;
    public $addressData;
    public $billingAddressData;
    public $phonesData;
    public $isBillingAddressSame;
    public $rep;
    public $geocodingRequired = true;
    public $tempId = null; //Id associated to temporally imported customer.
    public $flags = false;
    public $customerContacts = [];
    public $stopDBTransaction = false;
    public $customFields = null;

    public function __construct($input, $geocoding_required = true)
    {
        /**
         * @todo This `isset($input['input'])` is added as it works differently in customer job import than when adding a single customer. During customer add no input key is there, but when customers are imported from import panel. Input key is there. Need to rectify fix this.
         */
        if(isset($input['input'])) {
            $input = $input['input'];
        }

        $this->input = $input;
        $this->extractInput($input);

        $this->phonesData = $input['phones'];
        $this->isBillingAddressSame = $input['billing']['same_as_customer_address'];
        $this->rep = isset($input['rep_id']) ? $input['rep_id'] : 0; // unassign rep..
        $this->geocodingRequired = $geocoding_required;
        $this->tempId = ine($input, 'temp_id') ? $input['temp_id'] : null;

        if (isset($input['custom_fields'])) {
            $this->customFields = (array)$input['custom_fields'];
        }

        if (isset($input['flag_ids'])) {
            $this->flags = $input['flag_ids'];
        }

        if (isset($input['stop_db_transaction'])) {
            $this->stopDBTransaction = true;
        }
    }

    private function extractInput($input)
    {

        $this->mapCustomerInput();
        if ($this->customerData['id']) {
            $this->setLastModifiedBy();
        } else {
            $this->setCreatedBy();
        }
        $this->mapAddressInput();

        if (ine($input, 'customer_contacts')) {
            $this->mapCustomerContactsInput();
        }
    }

    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\CustomerCommandHandler::class);

        return $commandHandler->handle($this);
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
            'referred_by' => 'referred_by_id',
            'referred_by_type',
            'referred_by_note',
            'appointment_required',
            'call_required',
            'note',
            'is_commercial',
            'management_company',
            'property_name',
            'source_type',
            'disable_qbo_sync'
        ];

        $this->customerData = $this->mapInputs($map);

        if(isset($this->input['canvasser'])) {
            $this->customerData['canvasser'] = $this->input['canvasser'];
        }
        if(isset($this->input['call_center_rep'])) {
            $this->customerData['call_center_rep'] = $this->input['call_center_rep'];
        }

        if(isset($this->input['canvasser_id'])) {
            $this->customerData['canvasser_id'] = $this->input['canvasser_id'];
        }

        if(isset($this->input['call_center_rep_id'])) {
            $this->customerData['call_center_rep_id'] = $this->input['call_center_rep_id'];
        }

        //quickbook id on quickbook import customers
        if (ine($this->input, 'quickbook_id')) {
            $this->customerData['quickbook_id'] = $this->input['quickbook_id'];
        }
    }


    /**
     *  map customer locations input data.
     */
    private function mapAddressInput()
    {
        $this->addressData = $this->mapFirstSubInputs($this->addressFields, 'address');
        if (!$this->isBillingAddressSame) {
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
        $this->customerData['created_by'] = Auth::id();
        $this->customerData['last_modified_by'] = Auth::id();
    }

    /**
     *  set id of last_modified by user.
     */
    private function setLastModifiedBy()
    {
        $this->customerData['last_modified_by'] = Auth::id();
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
