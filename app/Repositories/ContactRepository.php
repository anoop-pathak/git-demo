<?php
namespace App\Repositories;

use App\Models\Address;
use App\Models\Phone;
use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\EmailAddress;
use Illuminate\Support\Facades\Auth;
use App\Services\Contexts\Context;
use App\Exceptions\InvalidContactIdsException;
use App\Models\Job;
use App\Models\JobContact;
use App\Models\Tag;
use App\Exceptions\InvalidTagIdsException;

Class ContactRepository extends ScopedRepository
{
	protected $model;
    protected $address;
    protected $phone;
    protected $scope;

    function __construct(Contact $model, Context $scope, Address $address, Phone $phone){
		$this->model = $model;
		$this->address = $address;
		$this->phone = $phone;
		$this->scope = $scope;
	}

	/**
	 * Get All Contacts
	 */
	public function getFilteredContacts($filters = array(), $type = Contact::TYPE_COMPANY)
	{
		$with = $this->getIncludes($filters);
		$contacts = $this->make($with);
		$contacts->where('type', $type);
		$contacts->select('contacts.*');
		$contacts = $contacts->sortable();

		$this->applyFilters($contacts, $filters);

		return $contacts;
	}

	/**
	 * Add Contacts Detail
	 */
	public function saveContact($contactData, $addressData, $emails = [], $phones = [], $jobId = null, $isPrimary = false, $tagIds = [], $note = null)
	{
		$contactData['company_id'] = $this->scope->id();

		$addressData['company_id'] = $this->scope->id();
		$address = $this->address->create(array_filter($addressData));
		$contactData['address_id'] = $address->id;
		$contactData['type'] = ine($contactData, 'type') ? $contactData['type']: Contact::TYPE_JOB;

		$contact = $this->model->create($contactData);
		if(!empty($phones)) {
			$contact = $this->attachPhones($contact, $phones);
		}

		if(!empty($emails)) {
			$contact = $this->attachEmails($contact, $emails);
		}

		if(!empty($tagIds)) {
			$contact->tags()->sync($tagIds);
		}

		if($note) {
			ContactNote::Create([
				'contact_id' => $contact->id,
				'company_id' => $contact->company_id,
				'created_by' => Auth::id(),
				'note' => $note,
			]);
		}

		if($jobId) {
			$job = Job::find($jobId);

			if($job->contact_same_as_customer) {
				Job::where('id', $job->id)
					->update([
						'contact_same_as_customer' => false
					]);
			}

			if($isPrimary) {
				JobContact::where('job_id', $job->id)->update(['is_primary' => false]);
			}
			JobContact::create(['contact_id' => $contact->id, 'job_id' => $job->id, 'is_primary' => (bool)$isPrimary]);

			if($isPrimary) {
				$contact = $job->contacts()->where('contact_id', $contact->id)->first();
			}
		}

		return $contact;
	}

	/**
	 * Update Contacts Detail
	 */
	public function updateContact($contactData, $addressData, $emails = [], $phones = [], $tagIds = [], $jobId = null, $isPrimary = null)
	{
		$contact = $this->getById($contactData['id']);

		if($address = $contact->address) {
			$address = $address->update($addressData);
		}

		$contact->update($contactData);
		$this->attachPhones($contact, $phones);
		$this->attachEmails($contact, $emails);

		if(!empty($tagIds)) {
			$contact->tags()->sync($tagIds);
		}

		if($jobId) {
			$job = Job::find($jobId);

			if($job->contact_same_as_customer) {
				Job::where('id', $job->id)
					->update([
						'contact_same_as_customer' => false
					]);
			}

			if($isPrimary) {
				JobContact::where('job_id', $job->id)->update(['is_primary' => false]);
			}

			JobContact::where([
					'contact_id' => $contact->id,
					'job_id' => $job->id,
				])
				->update(['is_primary' => (bool)$isPrimary]);

			if($isPrimary) {
				$contact = $job->contacts()->where('contact_id', $contact->id)->first();
			}
		}

		return $contact;
	}

	public function linkCompanyContactWithJob($jobId, $contactId, $isPrimary = false)
	{
		// get company contact - getbyid
		$contact = $this->getById($contactId);

		if($contact->type != Contact::TYPE_COMPANY) {
			throw new InvalidContactIdsException(trans('response.error.invalid', ['attribute' => 'contact id']));
		}

		$job = Job::where('company_id', getScopeId())->findOrFail($jobId);
		if($isPrimary && $job->hasPrimaryContact()) {
			JobContact::where(['job_id' => $jobId])->update(['is_primary' => false]);
		}

		if($job->contact_same_as_customer) {
			Job::where('id', $job->id)
				->update([
					'contact_same_as_customer' => false
				]);
		}

		$jobContact = JobContact::firstOrNew(['contact_id' => $contactId, 'job_id' => $jobId]);
		$jobContact->is_primary = $isPrimary;
		$jobContact->save();

		$contact = $job->contacts()->where('contacts.id', $contact->id)->first();

		return $contact;
	}

	public function unlinkContactWithJob($job, Contact $contact)
	{
		$jobContact = JobContact::where('contact_id', $contact->id)
			->where('job_id', $job->id)
			->delete();

		if(!$job->contacts()->count()) {
			$job->contact_same_as_customer = true;
			$job->save();
		}
	}

	/**
	 * Assign Tag to Contact
	 */
	public function assignTags(Contact $contact, $tagIds)
	{
		if(empty($tagIds)) return $companyContacts;

		$contact->tags()->sync($tagIds);

		return $contact;
	}

	/**
	 * Validate Contact Tags
	 */
	public function validateTags($tagIds)
	{
		$tagCount = Tag::where('company_id', getScopeId())
			->where('type', Tag::TYPE_CONTACT)
			->whereIn('id', (array)$tagIds)
			->count();

		return ($tagCount == count(arry_fu($tagIds)));
	}

	/**
	 * Find an entity by id
	 *
	 * @param int $id
	 * @param array $with
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function getJobContactById($id, array $with = array())
	{
		$contact = $this->make($with)
			->join('job_contact', 'job_contact.contact_id', '=', 'contacts.id')
			->where('contacts.type', Contact::TYPE_JOB)
			->select('contacts.*', 'job_contact.is_primary')
			->findOrFail($id);

		return $contact;
	}

	public function isCompanyContact($id)
	{
		$query = $this->make();
		$query->where('type', Contact::TYPE_COMPANY);
		$query->where('id', $id);

		return $query->exists($id);
	}

	/**
	 * Find an entity by id
	 *
	 * @param int $id
	 * @param array $with
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function getCompanyContactById($id, array $with = array())
	{
		$query = $this->make($with);
		$query->where('type', Contact::TYPE_COMPANY);

		return $query->findOrFail($id);
	}

	public function validateCompanyContacts($contactIds)
	{
		$contactCount = $this->make()
			->companyContacts()
			->whereIn('id', $contactIds)
			->count();

		return $contactCount == count(arry_fu($contactIds));
	}

	public function assignMultipleTags($tagIds, $contactIds)
	{
		if(!$this->validateTags($tagIds)) {
			throw new InvalidTagIdsException(trans('response.error.invalid', ['attribute' => 'tag id(s)']));
		}

		$filters['ids'] = $contactIds;
		$contacts = $this->getFilteredContacts($filters)->get();
		foreach ($contacts as $contact) {
			$contact->tags()->sync($tagIds, false);
		}

		return true;
	}

	/**
	 * Delete Job Contact
	 */
	public function deleteJobContact($jobId, $ids)
	{
		$jobContacts = $this->model->where('type', Contact::TYPE_JOB)
				->where('company_id', getScopeId())
				->whereIn('id', $ids);
		$jobContacts->delete();

		$contactCount = JobContact::where('job_id', $jobId)->whereIn('contact_id', $ids)->count();
		if($contactCount) {
			JobContact::where('job_id', $jobId)->whereIn('contact_id', $ids)->delete();
		}
	}

	/***** Private Function *****/

	private function applyFilters($query, $filters = array())
	{
		if(ine($filters, 'type')) {
			$query->where('type', $filters['type']);
		}

		if(ine($filters, 'job_id')) {
			$query->jobs($filters['job_id']);
		}

		if(ine($filters, 'company_name')){
			$query->where('company_name', 'LIKE', '%'.$filters['company_name'].'%');
		}

		if(ine($filters, 'email')){
			$email = $filters['email'];
			$query->whereHas('emails', function ($query) use ($email) {
			    $query->where('email', $email);
			});
		}

		if(ine($filters, 'address')) {
			$query->Join('addresses', 'addresses.id', '=', 'contacts.address_id')
				->whereRaw("CONCAT(addresses.address,' ',addresses.city,' ',addresses.zip) LIKE ?",['%'.$filters['address'].'%']);
		}

		if(ine($filters, 'name')) {
			$query->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?",['%'.$filters['name'].'%']);
		}

		if(ine($filters, 'phone_number')) {
			$phone = $filters['phone_number'];
			$query->whereHas('phones', function ($query) use ($phone) {
			    $query->where('number', 'Like', $phone.'%');
			});
		}

		if(ine($filters, 'tag_ids')) {
			$tagIds = $filters['tag_ids'];
			$query->whereHas('tags', function ($query) use ($tagIds) {
			    $query->whereIn('tags.id', $tagIds);
			});
		}

		if(ine($filters, 'ids')) {
			$query->whereIn('id', (array)$filters['ids']);
		}

		if(ine($filters, 'keyword')) {
			$query->keywordSearch($filters['keyword']);
		}

		if(ine($filters, 'query')) {
			$query->where(function($query)  use($filters){
				$email = $filters['query'];
				$query->whereHas('emails', function ($query) use ($email) {
					$query->where('email', 'LIKE', "%{$email}%");
				})
				->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%'.$filters['query'].'%']);
			});
		}
	}

	private function attachEmails($contact, $emails)
	{
		$contact->emails()->delete();
		$contact->emails()->detach();

		$contactEmails = [];
		foreach ((array)$emails as $email) {
			if(ine($email,'email')) {
				$email['company_id'] = $this->scope->id();
				$emailAddress = EmailAddress::create($email);
				$contactEmails[$emailAddress->id]['is_primary'] = ine($email, 'is_primary');
			}
		}
		if($contactEmails) {
			$contact->emails()->attach($contactEmails);
		}

		return $contact;
	}

	private function attachPhones($contact, $phones)
	{
		$contact->phones()->delete();
		$contact->phones()->detach();

		$contactPhones = [];

		foreach ((array)$phones as $phoneData) {
			if(ine($phoneData,'label') && ine($phoneData,'number')) {
				$phone = Phone::create($phoneData);
				$contactPhones[$phone->id]['is_primary'] = ine($phoneData, 'is_primary');
			}
		}

		if($contactPhones) {
			$contact->phones()->attach($contactPhones);
		}

		return $contact;
	}

	private function getIncludes($input)
	{
		$with = [];

		if(!ine($input, 'includes')) return $with;

		$includes = (array)$input['includes'];

		if(in_array('address', $includes)) {
			$with[] = 'address';
		}

		if(in_array('emails', $includes)) {
			$with[] = 'emails';
		}

		if(in_array('phones', $includes)) {
			$with[] = 'phones';
		}

		if(in_array('tags', $includes)) {
			$with[] = 'tags';
		}

		if(in_array('notes', $includes)) {
			$with[] = 'notes';
		}

		return $with;
	}
}