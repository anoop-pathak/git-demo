<?php namespace App\Commands;

class JobCreateCommand
{

    private $addressFields = [
        'address',
        'address_line_1',
        'city',
        'state_id',
        'country_id',
        'zip',
        'lat',
        'long'
    ];

    // private $contactFields = [
    //     'job_id',
    //     'first_name',
    //     'last_name',
    //     'email',
    //     'additional_emails',
    //     'phone',
    //     'additional_phones',
    //     'address',
    //     'address_line_1',
    //     'city',
    //     'state_id',
    //     'country_id',
    //     'zip',
    //     'lat',
    //     'long'
    // ];

    private $captureRequestFields = [
        'customer_name',
        'customer_email',
        'customer_phone',
        'job_address',
        'job_address_line_2',
        'job_city',
        'job_zip_code',
        'country_id',
        'state_id',
        'deliverable_id',
        'hover_user_id',
        'hover_user_email'
    ];

    protected $input;
    public $jobData;
    public $customerId;
    public $trades = null;
    public $reps = null;
    public $workAndJobTypes = null;
    public $estimators = null;
    public $customFields = null;
    public $flags = null;
    public $contacts = [];
    public $labours = null;
    public $subContractors = null;
    public $insuranceDetails = [];
    public $captureRequest = null;

    public function __construct($input)
    {
        /**
         * @todo This `isset($input['input'])` is added as it works differently in customer job import than when adding a single customer. During customer add no input key is there, but when customers are imported from import panel. Input key is there. Need to rectify fix this.
         */
        if(isset($input['input'])) {
            $input = $input['input'];
        }

        $this->input = $input;
        $this->customerId = $input['customer_id'];
        $this->extractInput($input);
    }

    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\JobCreateCommandHandler::class);

        return $commandHandler->handle($this);
    }

    private function extractInput($input)
    {
        if (ine($input, 'trades')) {
            $this->trades = $input['trades'];
        }

        if (isset($input['flag_ids'])) {
            $this->flags = $input['flag_ids'];
        }

        if (ine($input, 'insurance') && ine($input, 'insurance_details')) {
            $this->extractInsuranceData();
        }

        if (ine($input, 'rep_ids')) {
            $this->reps = (array)$input['rep_ids'];
        }

        if (ine($input, 'job_types')) {
            $this->workAndJobTypes = (array)$input['job_types'];
        }

        if (ine($input, 'work_types')) {
            $this->workAndJobTypes = array_merge((array)$input['work_types'], (array)$this->workAndJobTypes);
        }

        if (ine($input, 'estimator_ids')) {
            $this->estimators = (array)$input['estimator_ids'];
        }

        if(!ine($input, 'contact_same_as_customer') && ine($input, 'contacts')) {
            $this->contacts = $input['contacts'];
        }

        if(ine($input, 'hover_capture_request')) {
            $this->mapCaptureRequest();
        }

        if (ine($input, 'labour_ids')) {
            $this->labours = (array)$input['labour_ids'];
        }

        if (ine($input, 'sub_contractor_ids')) {
            $this->subContractors = (array)$input['sub_contractor_ids'];
        }

        if(isset($input['custom_fields'])) {
            $this->customFields = (array)$input['custom_fields'];
        }

        $this->mapJobInput();
        $this->mapAddresInput();
    }

    /**
     *  map jobs input data and also map jobs location.
     * @return void.
     */
    private function mapJobInput()
    {
        $map = [
            'job_type_id',
            'name',
            'description',
            'created_by',
            'last_modified_by',
            'same_as_customer_address',
            'other_trade_type_description',
            'call_required',
            'appointment_required',
            'alt_id',
            'division_id',
            'duration',
            'contact_same_as_customer',
            'multi_job',
            'parent_id',
            'status',
            'insurance',
            'wp_job',
            'lead_number',
            'sync_on_companycam',
            'division_code',
            'source_type',
            'spotio_lead_id',
            'quickbook_sync_token',
            'quickbook_sync',
            'quickbook_id',
            'origin',
            'qb_desktop_id',
            'qb_desktop_sequence_number'
        ];

        $jobData = $this->mapInputs($map, $this->input);

        //remove other_trade_description if other trade not selected..
        if (!in_array(\App\Models\Trade::getOtherTradeId(), (array)$this->trades)) {
            $jobData['other_trade_type_description'] = null;
        }

        if(ine($this->input, 'sync_on_hover') && ine($this->input, 'hover_user_id')) {
            $jobData['sync_on_hover'] = true;
            $jobData['hover_user_id'] = issetRetrun($this->input, 'hover_user_id') ?: null;
            $jobData['hover_deliverable_id'] = issetRetrun($this->input, 'hover_deliverable_id') ?: 2; // Roof Only
        }

        if(ine($this->input, 'material_delivery_date')) {
            $jobData['material_delivery_date'] = $this->input['material_delivery_date'];
        }

        if(ine($this->input, 'purchase_order_number')) {
            $jobData['purchase_order_number'] = $this->input['purchase_order_number'];
        }

        $jobData['same_as_customer_address'] = isTrue($jobData['same_as_customer_address']);

        $this->jobData = $jobData;
    }

    private function mapAddresInput()
    {
        $this->jobData['address'] = $this->mapInputs($this->addressFields, $this->input);
    }

    // private function mapContactInput()
    // {
    //     $this->contact = $this->mapInputs($this->contactFields, $this->input['contact']);
    // }

    private function extractInsuranceData()
    {

        $map = [
            'insurance_company',
            'insurance_number',
            'phone',
            'fax',
            'email',
            'adjuster_name',
            'adjuster_phone',
            'adjuster_email',
            'rcv',
            'deductable_amount',
            'policy_number',
            'contingency_contract_signed_date',
            'date_of_loss',
            'acv',
            'total',
            'adjuster_phone_ext',
            'depreciation',
            'supplement',
            'net_claim',
            'upgrade'
        ];
        $this->insuranceDetails = $this->mapInputs($map, $this->input['insurance_details'], null);
    }

    private function mapCaptureRequest()
    {
        $this->captureRequest = $this->mapInputs($this->captureRequestFields, $this->input['hover_capture_request'], null);
    }

    /**
     * Map  Model fields to inputs
     * @return void
     */
    private function mapInputs($map, $input = [], $default = "")
    {
        $ret = [];

        // empty the set default.
        if (empty($input)) {
            $input = $this->input;
        }

        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($input[$value]) ? $input[$value] : $default;
            } else {
                $ret[$key] = isset($input[$value]) ? $input[$value] : $default;
            }
        }

        return $ret;
    }
}
