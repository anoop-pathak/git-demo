<?php namespace App\Commands;

use App\Handlers\Commands\SubscriberSignupCommandHandler;

class SubscriberSignupCommand
{
    /**
     * array of all fields submitted
     * @var Array
     */
    protected $input;

    /**
     * Array Of Company Details fields
     * @var Array
     */
    public $companyDetails;

    /**
     * Array Of Admin Details fields
     * @var Array
     */
    public $adminDetails;

    /**
     * Array Of Admin Profile Details fields
     * @var Array
     */
    public $adminProfileDetails;

    /**
     * Array Of billing Details fields
     * @var Array
     */
    public $billingDetails;

    /**
     * Bool value to check admin address same as company address
     * @var Bool
     */
    protected $sameAsCompanyAddress;

    /**
     * Bool value to check billing address same as company address
     * @var Bool
     */
    protected $billingAddressSameAsCompanyAddress;

    /**
     * Array Of tradeIds
     * @var Array
     */
    public $trades = [];

    /**
     * Array Of statesIds
     * @var Array
     */
    public $states = [];

    /**
     * String of time-zone name..
     * @var String
     */
    public $timezone = null;

    /**
     * String of signup temp token
     * @var String
     */
    public $signup_temp_token = null;

    /**
     * Consturctor function
     * @var $inputs Array
     * @return Void
     */
    public function __construct($input, $check_auth_login = true)
    {
        $this->input = $input;
        $this->sameAsCompanyAddress = $input['admin_details']['same_as_company_address'];
        $this->billingAddressSameAsCompanyAddress = $input['billing_details']['same_as_company_address'];

        if (ine($input, 'signup_temp_token')) {
            $this->signup_temp_token = $input['signup_temp_token'];
        }

        if (isset($input['company_details']['trades'])) {
            $this->trades = $input['company_details']['trades'];
        }

        if (isset($input['company_details']['states'])) {
            $this->states = $input['company_details']['states'];
        }

        $this->check_auth_login = $check_auth_login;

        $this->extractInput();
    }

    public function handle()
    {
        $commandHandler = \App::make(SubscriberSignupCommandHandler::class);

        return $commandHandler->handle($this);

    }

    /**
     * Extract input to respective Model
     * @return void
     */
    private function extractInput()
    {
        $this->mapCompanyInput();

        // if company country is empty set office country as company country..
        if (empty($this->companyDetails['company_country'])) {
            $this->companyDetails['company_country'] = $this->companyDetails['office_country'];
        }

        $this->mapAdminInput();
        $this->mapAdminProfileInput();
        $this->mapBillingInput();
        $this->mapBillingAddressInput();
        if (ine($this->input['company_details'], 'timezone')) {
            $this->timezone = $this->input['company_details']['timezone'];
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
            'office_state' => 'state_id',
            'office_country' => 'country_id',
            'office_address' => 'address',
            'office_address_line_1' => 'address_line_1',
            'office_city' => 'city',
            'office_zip' => 'zip',
            'office_phone' => 'phone',
            'office_email' => 'email',
            'office_fax' => 'fax',
            'additional_phone',
            'additional_email',
            'company_country',
            'account_manager_id',
        ];

        $this->companyDetails = $this->mapFirstSubInputs($map, 'company_details');
    }

    /**
     * Map  User Model inputs
     * @return void
     */
    private function mapAdminInput()
    {
        $map = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email',
            'password' => 'password'
        ];
        $this->adminDetails = $this->mapFirstSubInputs($map, 'admin_details');
    }

    /**
     * Map  UserProfile Model inputs
     * @return void
     */
    private function mapAdminProfileInput()
    {
        $map = [
            'address',
            'address_line_1',
            'city',
            'state_id',
            'country_id',
            'zip'
        ];
        if ((bool)$this->sameAsCompanyAddress) {
            $this->adminProfileDetails = $this->mapFirstSubInputs($map, 'company_details');
        } else {
            $this->adminProfileDetails = $this->mapFirstSubInputs($map, 'admin_details');
        }
    }

    /**
     * Map  billing inputs
     * @return void
     */
    private function mapBillingInput()
    {
        $map = [
            'token',
            'product_id',
            'email',
            'trial_coupon',
            'monthly_fee_coupon',
            'setup_fee_coupon',
            'gaf_code',
        ];

        $this->billingDetails = $this->mapFirstSubInputs($map, 'billing_details');
    }

    /**
     * Map  Company Model billing address inputs
     * @return void
     */
    private function mapBillingAddressInput()
    {
        $map = [
            'address',
            'address_line_1',
            'city',
            'state_id',
            'country_id',
            'zip',
        ];
        if ((bool)$this->billingAddressSameAsCompanyAddress) {
            $billingAddress = $this->mapFirstSubInputs($map, 'company_details');
        } else {
            $billingAddress = $this->mapFirstSubInputs($map, 'billing_address');
        }
        $this->billingDetails = array_merge($this->billingDetails, $billingAddress);
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
