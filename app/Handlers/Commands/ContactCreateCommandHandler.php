<?php

namespace App\Handlers\Commands;

use App\Repositories\ContactRepository;

class ContactCreateCommandHandler
{
	protected $command;
	protected $repo;

	public function __construct(ContactRepository $repo)
	{
		$this->repo = $repo;
	}

	/**
	 * handle command data.
	 */
	public function handle($command)
	{
		try {
			if(empty($command->jobId)) {
				$command->isPrimary = null;
			}

			if($command->contactId && $this->repo->isCompanyContact($command->contactId)) {
				$contact = $this->repo->linkCompanyContactWithJob(
					$command->jobId,
					$command->contactId,
					$command->isPrimary
				);
			} elseif ($command->contactId) {
				$contact = $this->repo->updateContact(
					$command->contactData,
					$command->addressData,
					$command->emails,
					$command->phones,
					$command->tagIds,
					$command->jobId,
					$command->isPrimary
				);
			} else {
				$contact = $this->repo->saveContact(
					$command->contactData,
					$command->addressData,
					$command->emails,
					$command->phones,
					$command->jobId,
					$command->isPrimary,
					$command->tagIds,
					$command->note
				);
			}
		} catch(\Exception $e) {

			throw $e;
		}

		return $contact;
	}
}