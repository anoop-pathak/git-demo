<?php namespace App\Commands;

use App\Models\Trade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Handlers\Commands\JobUpdateCommandHandler;

class JobUpdateCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $addressFields = [
        'id',
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
    public $estimators = null;
    public $workAndJobTypes = null;
    public $jobTypes = null;
    public $flags = null;
    protected $isAddressSameAsCustomer;
    public $contacts = [];
    public $labours = null;
    public $subContractors = null;
    public $insuranceDetails = [];
    public $customFields = null;
    public $captureRequest = null;
    public $deleteContactIds = [];

    public function __construct($input)
    {
        $this->input = $input;
        $this->customerId = $input['customer_id'];
        $this->isAddressSameAsCustomer = $input['same_as_customer_address'];
        $this->extractInput($input);

        if (ine($input, 'insurance') && ine($input, 'insurance_details')) {
            $this->extractInsuranceData();
        }
    }

    public function handle()
    {
        $commandHandler = \App::make(JobUpdateCommandHandler::class);

        return $commandHandler->handle($this);
    }

    private function extractInput($input)
    {

        if (isset($this->input['rep_ids'])) {
            $this->reps = (array)$this->input['rep_ids'];
        }

        if (isset($input['job_types'])) {
            $this->workAndJobTypes = (array)$input['job_types'];
        }

        if (isset($input['work_types'])) {
            $this->workAndJobTypes = array_merge((array)$input['work_types'], (array)$this->workAndJobTypes);
        }

        if (isset($this->input['estimator_ids'])) {
            $this->estimators = (array)$this->input['estimator_ids'];
        }

        if(!ine($input, 'contact_same_as_customer') && isset($input['contacts'])) {
            $this->contacts = $input['contacts'];
        }

        if (isset($input['labour_ids'])) {
            $this->labours = (array)$input['labour_ids'];
        }

        if (isset($input['sub_contractor_ids'])) {
            $this->subContractors = (array)$input['sub_contractor_ids'];
        }

        if (isset($input['flag_ids'])) {
            $this->flags = (array)$input['flag_ids'];
        }

        if (isset($input['trades'])) {
            $this->trades = (array)$input['trades'];
        }

        if(isset($input['custom_fields'])){
            $this->customFields = (array)$input['custom_fields'];
        }

        if(ine($input, 'delete_contact_ids')) {
            $this->deleteContactIds = $this->input['delete_contact_ids'];
        }

        if(ine($input, 'hover_capture_request')) {
            $this->mapCaptureRequest();
        }

        $this->setLastModifiedBy();
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
            'id',
            'job_type_id',
            'description',
            'last_modified_by',
            'same_as_customer_address',
            'other_trade_type_description',
            'call_required',
            'appointment_required',
            'alt_id',
            'lead_number',
            'division_id',
            'duration',
            'contact_same_as_customer',
            'parent_id',
            'status',
            'insurance',
            'custom_fields',
            'sync_on_companycam',
            'division_code',
            'qb_desktop_id',
            'qb_desktop_sequence_number'
        ];

        if(isset($this->input['name'])) {
            $map[] = 'name';
        }
        $map = array_intersect($map, array_keys($this->input));
        $jobData = $this->mapInputs($map, $this->input);

        //remove other_trade_description if other trade not selected..
        if (!in_array(Trade::getOtherTradeId(), (array)$this->trades)) {
            $jobData['other_trade_type_description'] = null;
        }

        if(ine($this->input, 'sync_on_hover') && ine($this->input, 'hover_user_id')) {
            $jobData['sync_on_hover'] = true;
            $jobData['hover_user_id'] = $this->input['hover_user_id'];
            $jobData['hover_deliverable_id'] = issetRetrun($this->input, 'hover_deliverable_id') ?: 2; // Roof Only
        }

        if(ine($this->input, 'material_delivery_date')) {
            $jobData['material_delivery_date'] = $this->input['material_delivery_date'];
        }

        if(ine($this->input, 'company_contact_id')) {
            $jobData['company_contact_id'] = $this->input['company_contact_id'];
        }

        if(isset($this->input['purchase_order_number'])) {
            $jobData['purchase_order_number'] = $this->input['purchase_order_number'];
        }

        $jobData['same_as_customer_address'] = isTrue($jobData['same_as_customer_address']);

        $this->jobData = $jobData;
    }

    private function mapAddresInput()
    {
        if (!$this->isAddressSameAsCustomer && ine($this->input, 'address')) {
            $this->jobData['address'] = $this->mapInputs($this->addressFields, $this->input['address']);
        }
    }

    // private function mapContactInput()
    // {
    //     $this->contact = $this->mapInputs($this->contactFields, $this->input['contact']);
    // }

    /**
     *  set id of created by user.
     */
    private function setLastModifiedBy()
    {
        $this->input['last_modified_by'] = \Auth::id();
    }

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
