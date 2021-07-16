<?php

namespace App\Handlers\Commands;

use App\Repositories\ContactRepository;

class ContactUpdateCommandHandler
{
	protected $command;
	protected $repo;

	public function __construct(ContactRepository $repo) {
		$this->repo = $repo;
	}

	/**
	 *  handle command data.
	 */
	public function handle($command)
	{
		try {
			$contact = $this->repo->updateContact(
				$command->contactData,
				$command->addressData,
				$command->emails,
				$command->phones,
				$command->tagIds,
				$command->jobId,
				$command->isPrimary
			);
		} catch(\Exception $e) {

			throw $e;
		}

		return $contact;
	}
}