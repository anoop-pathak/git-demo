<?php
namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\CompanyContact;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use App\Models\EmailAddress;
use App\Models\Address;
use App\Models\Phone;
use App\Models\ContactNote;

class MoveCompanyContactsToContactsTable extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:move_company_contacts_to_contacts_table';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import Job Contacts To Contacts Table';

	private $skipIds = [];

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$startedAt = Carbon::now()->toDateTimeString();
		$this->info('Command Started At: '.$startedAt);

		$query = CompanyContact::with(['company.subscriber'])
			->withTrashed()
			->where(function($query) {
				$query->whereNotIn('contact_id', function($query) {
					$query->select('id')
						->from('contacts')
						->where('type', Contact::TYPE_COMPANY);
				})
				->orWhereNull('contact_id');
		});

		$this->totalCount = $query->count();
		$this->info("Total contacts will be moved: ".$this->totalCount);

		$this->moveContacts($query);
		$this->info('Company Contacts Moved Successfully.');
		$ended_at = Carbon::now()->toDateTimeString();

		$this->info('Command Ended At: '.$ended_at);
	}

	private function moveContacts($query)
	{
		$queryClone = clone $query;

		$queryClone->chunk(50, function($companyContacts) {
			foreach ($companyContacts as $companyContact) {
				DB::beginTransaction();
				try {
					$this->saveContact($companyContact);
					DB::commit();
				} catch (Exception $e) {
					DB::rollBack();
					$errMsg = 'Company contact id: '.$companyContact->id;
					$errMsg .= ' Error message: '.$e->getMessage();
					$errMsg .= ' Line: '.$e->getLine();
					$this->info($errMsg);
					$this->skipIds[] = $companyContact->id;
				}

				--$this->totalCount;
				$this->info("Pending Records: ".$this->totalCount);
			}
		});

		$query->whereNotIn('company_contacts.id', $this->skipIds);

		if($query->count()) {
			$this->moveContacts($query);
		}
	}

	private function saveContact($companyContact)
	{
		$address = $this->saveAddress($companyContact);

		$contact = Contact::create([
			'company_id'		=> $companyContact->company_id,
			'type'				=> Contact::TYPE_COMPANY,
			'company_name'		=> $companyContact->company_name,
			'first_name'		=> $companyContact->first_name,
			'last_name'			=> $companyContact->last_name,
			'address_id'		=> $address->id,
			'created_by'		=> $companyContact->company->subscriber->id,
			'last_modified_by'	=> $companyContact->company->subscriber->id,
		]);
		$contact->deleted_at = $companyContact->deleted_at;
		$contact->deleted_by = $companyContact->deleted_by;
		$contact->save();

		if($companyContact->email) {
			$emailAddress = EmailAddress::create([
				'company_id' => $companyContact->company_id,
				'email' => $companyContact->email
			]);
			$contact->emails()->attach($emailAddress->id, ['is_primary' => true]);
		}

		if($companyContact->phones) {
			$phones = json_decode(json_encode($companyContact->phones), true);
			$this->attachPhones($contact, $phones);
		}

		if($companyContact->note) {
			$this->saveContactNote($contact, $companyContact);
		}

		DB::table('company_contacts')
			->where('id', $companyContact->id)
			->update([
				'contact_id' => $contact->id,
			]);
	}

	private function saveAddress($contactAddress)
	{
		$address = Address::create([
			'company_id'		=> $contactAddress->company_id,
			'address'			=> $contactAddress->address,
			'address_line_1'	=> null,
			'city'				=> null,
			'state_id'			=> null,
			'country_id'		=> null,
			'zip'				=> null,
		]);

		return $address;
	}

	private function attachPhones($contact, $phones)
	{
		$count = 1;

		foreach ((array)$phones as $phoneData) {
			if(ine($phoneData,'label') && ine($phoneData,'number')) {
				$isPrimary = false;
				if(isset($phoneData['extension'])) {
					$phoneData['ext'] = $phoneData['extension'];
				}
				$phone = Phone::create($phoneData);

				if($count == 1) {
					$isPrimary = true;
				}

				$count++;

				$contact->phones()->attach($phone->id, ['is_primary' => $isPrimary]);
			}
		}

		return $contact;
	}

	private function saveContactNote($contact, $companyContact)
	{
		$note = ContactNote::create([
			'company_id' => $contact->company_id,
			'contact_id' => $contact->id,
			'note'		 => $companyContact->note,
			'created_by' => $contact->created_by,
			'updated_by' => $contact->last_modified_by,
		]);

		return $note;
	}
}
