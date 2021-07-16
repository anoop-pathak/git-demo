<?php
namespace App\Commands;

use App\Exceptions\EmptyFormSubmitException;

Class ContactCreateCommand
{
	private $addressFields = [
		'address', 'address_line_1', 'city', 'state_id', 'country_id', 'zip', 'lat', 'long'
	];

	private $contactFields = [
		'type', 'company_name', 'first_name', 'last_name', 'note'
	];

	public $input;
	public $contactId;
	public $contactData = [];
	public $addressData = [];
	public $tagIds = [];
	public $actionType;
	public $isPrimary = 0;

	public function __construct($input, $jobId = null)
	{
		$this->input = $input;
		$this->jobId = isset($input['job_id']) ? $input['job_id'] : $jobId;
		if($this->jobId) {
			$this->contactId = isset($input['id']) ? $input['id'] : null;
			$this->isPrimary = ine($input, 'is_primary');
		}

		$this->extractInput();

	}

	public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\ContactCreateCommandHandler::class);

        return $commandHandler->handle($this);
    }

	private function extractInput()
	{
		$this->contactData = $this->mapInputs($this->contactFields);
		if($this->jobId) {
			$this->contactData['id'] = $this->contactId;
		}
		$this->addressData = $this->mapInputs($this->addressFields);
		$this->emails = isset($this->input['emails']) ? $this->input['emails'] : [];
		$this->phones = isset($this->input['phones']) ? $this->input['phones'] : [];
		$this->tagIds = isset($this->input['tag_ids']) ? $this->input['tag_ids'] : [];
		$this->note   = ine($this->input, 'note') ? $this->input['note'] : null;


		$formValues = array_filter($this->contactData) + array_filter($this->addressData) + array_filter($this->emails) + array_filter($this->phones);
		if(empty($formValues) && !$this->contactId) {
			throw new EmptyFormSubmitException("Contact form is empty so it cannot be save.");
		}

	}

	private function mapInputs($map, $input = array(), $default = "")
	{
		$ret = array();

		if(empty($input)) {
			$input = $this->input;
		}

		foreach ($map as $key => $value) {
			if(is_numeric($key)){
				$ret[$value] = isset($input[$value]) ? $input[$value] : $default;
			}else{
				$ret[$key] = isset($input[$value]) ? $input[$value] : $default;
			}
		}

		return $ret;
	}
}