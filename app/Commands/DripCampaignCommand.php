<?php
namespace App\Commands;

use Carbon\Carbon;
use App\Models\DripCampaign;

class DripCampaignCommand {

	private $emailCampaignFields = ['subject', 'content', 'email_template_id'];

	/**
	 * array of all fields submitted
	 * @var Array
	 */
	public $input;
	public $campaignData;
	public $emailCampaign = null;
	public $emailRecipentsTo  = [];
	public $emailRecipentsCC  = [];
	public $emailRecipentsBCC = [];
	Public $emailAttachments  = [];


	/**
     * Consturctor function
     * @var $inputs Array
     * @return Void
     */
	public function __construct($input)
	{
		$this->input = $input;
		$this->jobId = $input['job_id'];
		$this->extractInput();
    }

    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\DripCampaignCommandHandler::class);

        return $commandHandler->handle($this);
    }

	private function extractInput() {
		$this->mapCampaignInput();
		$this->mapCampaignEmail();
		$this->mapEmailRecipentsTo();
		$this->mapEmailRecipentsCC();
		$this->mapEmailRecipentsBCC();
		$this->mapEmailAttachments();
	}

	/**
     * Map  DripCampaign Model inputs
     * @return void
     */
    private function mapCampaignInput() {
    	$map = [
            'name',
            'customer_id',
            'job_id',
            'created_by',
            'job_current_stage_code',
            'repeat',
            'occurence',
            'interval',
            'until_date',
            'job_end_stage_code',
            'by_day',
            'customer_id'
		];

		$this->campaignData = $this->mapInputs($map);

		$job = \App::make('App\Repositories\JobRepository')->getById($this->campaignData['job_id']);
		$this->campaignData['customer_id'] = $job->customer_id;

		if(!in_array($this->campaignData['repeat'], [DripCampaign::REPEAT_WEEKLY, DripCampaign::REPEAT_MONTHLY])) {
			$this->campaignData['by_day'] = null;
		}

		if($this->campaignData['occurence'] != DripCampaign::OCCURANCE_UNTIL_DATE) {
			$this->campaignData['until_date'] = null;
		}


		switch ($this->campaignData['occurence']) {
			case DripCampaign::OCCURANCE_NEVER_END:
				$this->campaignData['until_date'] = null;
				$this->campaignData['occurence']  = null;
				break;

			case DripCampaign::OCCURANCE_UNTIL_DATE:
				$untilDate = Carbon::parse($this->campaignData['until_date'])->endOfDay();
				$this->campaignData['occurence']   = DripCampaign::OCCURANCE_UNTIL_DATE;
				$this->campaignData['until_date']  = utcConvert($untilDate);
				break;
		}
    }

    /**
     * Map  Model fields to inputs
     * @return void
     */
    private function mapInputs($map, $input = array()){
    	$ret = array();

    	// empty the set default.
    	if(empty($input)) {
    		$input = $this->input;
    	}

    	foreach ($map as $key => $value) {
			if(is_numeric($key)){
				$ret[$value] = isset($input[$value]) ? $input[$value] : "";
			}else{
				$ret[$key] = isset($input[$value]) ? $input[$value] : "";
			}
		}

        return $ret;
    }

    private function mapCampaignEmail()
    {
    	if(isset($this->input['email']) && !empty($this->input['email'])) {
        	$this->emailCampaign = $this->mapInputs($this->emailCampaignFields, $this->input['email'], null);
        }
    }

    private function mapEmailRecipentsTo()  {
        foreach($this->input['email']['recipients']['to'] as $key => $recipient) {
            $this->emailRecipentsTo[$key] = $recipient;
        }
    }

    private function mapEmailRecipentsCC()  {
    	if (isset($this->input['email']['recipients']['cc'])) {
	        foreach($this->input['email']['recipients']['cc'] as $key => $recipient) {
	            $this->emailRecipentsCC[$key] = $recipient;
    		}
    	}
    }

    private function mapEmailRecipentsBCC()  {
    	if (isset($this->input['email']['recipients']['bcc'])) {
	        foreach($this->input['email']['recipients']['bcc'] as $key => $recipient) {
	            $this->emailRecipentsBCC[$key] = $recipient;
	        }
	    }
    }

    private function mapEmailAttachments()  {
    	if (isset($this->input['email']['attachments'])) {
	        $this->emailAttachments = $this->input['email']['attachments'];
	    }
    }
}