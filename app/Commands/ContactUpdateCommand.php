<?php
namespace App\Commands;

use App\Exceptions\EmptyFormSubmitException;

Class ContactUpdateCommand
{

	private $addressFields = [
		'address', 'address_line_1', 'city', 'state_id', 'country_id', 'zip', 'lat', 'long',
	];

	private $contactFields = [
		'id', 'company_name', 'first_name', 'last_name', 'note'
	];

	public $input;
	public $contactData = [];
	public $addressData = [];
	public $jobId;
	public $isPrimary = 0;

	public function __construct( $input )
	{
		$this->input = $input;

		$this->jobId = isset($input['job_id']) ? $input['job_id'] : null;
		if($this->jobId) {
			$this->isPrimary = ine($input, 'is_primary');
		}

		$this->extractInput();
	}

	public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\ContactUpdateCommandHandler::class);

        return $commandHandler->handle($this);
    }

	private function extractInput()
	{
		$this->contactData = $this->mapInputs($this->contactFields);
		$this->addressData = $this->mapInputs($this->addressFields);
		$this->emails = isset($this->input['emails']) ? $this->input['emails'] : [];
		$this->phones = isset($this->input['phones']) ? $this->input['phones'] : [];
		$this->tagIds = isset($this->input['tag_ids']) ? $this->input['tag_ids'] : [];
		$contact = $this->contactData;
		unset($contact['id']);
		$formValues = array_filter($contact) + array_filter($this->addressData) + array_filter($this->emails) + array_filter($this->phones) ;
		if(empty($formValues)) {
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