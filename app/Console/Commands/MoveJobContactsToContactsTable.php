<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Contact;
use App\Models\EmailAddress;
use App\Models\JobContact;
use App\Models\Address;
use App\Models\Phone;
use Exception;

class MoveJobContactsToContactsTable extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:move_job_contacts_to_contacts_table';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command will move job contacts into contacts table.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	private $skipIds = [];

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		DB::beginTransaction();
		try {
			$startedAt = Carbon::now()->toDateTimeString();
			$this->info('Command Started At: '.$startedAt);

			$query = DB::table('job_contacts')
				->join('jobs', 'jobs.id', '=', 'job_contacts.job_id')
				->join('users', function($join) {
					$join->on('jobs.company_id', '=', 'users.company_id')
						->where('users.group_id', '=', User::GROUP_OWNER);
				})
				->where(function($query) {
					$query->whereNotIn('contact_id', function($query) {
						$query->select('id')
							->from('contacts')
							->where('type', '=', Contact::TYPE_JOB);
					})
					->orWhereNull('contact_id');
				})
				->select('job_contacts.*', 'jobs.company_id', DB::raw('users.id as owner_id'));

			$this->totalCount = $query->count();

			$this->info("Total contacts will be moved: ".$this->totalCount);

			$this->moveContacts($query);

			$ended_at = Carbon::now()->toDateTimeString();
			$this->info('Job Contacts Moved Successfully.');

			$ended_at = Carbon::now()->toDateTimeString();
			DB::commit();

			$this->info('Command Ended At: '.$ended_at);
		} catch (Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}

	private function moveContacts($query)
	{
		$queryClone = clone $query;

		$queryClone->chunk(50, function($jobContacts) {
			foreach ($jobContacts as $jobContact) {
				DB::beginTransaction();
				try {
					$jobContact = json_decode(json_encode($jobContact), true);
					$this->saveContact($jobContact);
					DB::commit();
				} catch (Exception $e) {
					DB::rollBack();
					$errMsg = 'Job contact id: '.$jobContact['id'];
					$errMsg .= ' Error message: '.$e->getMessage();
					$errMsg .= ' Line: '.$e->getLine();
					$this->info($errMsg);
					$this->skipIds[] = $jobContact['id'];
				}

				--$this->totalCount;
				$this->info("Pending Records: ".$this->totalCount);
			}
		});

		$query->whereNotIn('job_contacts.id', $this->skipIds);

		if($query->count()) {
			$this->moveContacts($query);
		}
	}

	private function saveContact($data)
	{
		$address = $this->saveAddress($data);

		$contact = Contact::create([
			'company_id'		=> $data['company_id'],
			'type'				=> Contact::TYPE_JOB,
			'first_name'		=> $data['first_name'],
			'last_name'			=> $data['last_name'],
			'address_id'		=> $address->id,
			'created_by'		=> $data['owner_id'],
			'last_modified_by'	=> $data['owner_id'],
		]);

		$this->attachPhones($contact, $data);
		if(ine($data, 'email')) {
			$emailAddress = EmailAddress::create([
				'company_id' => $data['company_id'],
				'email' => $data['email']
			]);
			$contact->emails()->attach($emailAddress->id, ['is_primary' => true]);
		}

		$this->attachEmails($contact, $data);

		$jobContat = JobContact::firstOrCreate([
			'job_id' => $data['job_id'],
			'contact_id' => $contact->id,
			'is_primary' => true,
		]);

		DB::table('job_contacts')
			->where('id', $data['id'])
			->update([
				'contact_id' => $contact->id
			]);
	}

	private function saveAddress($data)
	{
		$address = Address::create([
			'company_id'		=> $data['company_id'],
			'address'			=> $data['address'],
			'address_line_1'	=> $data['address_line_1'],
			'city'				=> $data['city'],
			'state_id'			=> $data['state_id'],
			'country_id'		=> $data['country_id'],
			'zip'				=> $data['zip'],
		]);

		return $address;
	}

	private function attachEmails($contact, $data)
	{
		$emails = json_decode($data['additional_emails'], true);
		if(empty($emails)) return;

		$contactEmails = [];
		foreach ((array)$emails as $email) {
			$email['company_id'] = $data['company_id'];
			$emailAddress = EmailAddress::create($email);
			$contactEmails[] = $emailAddress->id;
		}

		if($contactEmails) {
			$contact->emails()->attach($contactEmails);
		}

		return $contact;
	}

	/**
	 * Attach Phones
	 */
	private function attachPhones($contact, $data)
	{
		$phones = json_decode($data['additional_phones'], true);
		if(empty($phones)) return;

		$count = 1;
		foreach ((array)$phones as $phoneData) {
			if(ine($phoneData,'label') && ine($phoneData,'number')) {
				$isPrimary = false;
				$phone = Phone::create($phoneData);

				if($count == 1) {
					$isPrimary = true;
				}

				$contact->phones()->attach($phone->id, ['is_primary' => $isPrimary]);
				$count++;
			}
		}

		return $contact;
	}

}
