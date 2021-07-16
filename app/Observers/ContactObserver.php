<?php
namespace App\Observers;

use Illuminate\Support\Facades\Auth;
use App\Models\Job;

class ContactObserver
{
	public function subscribe( $event )
	{
		$event->listen(
			'eloquent.creating: Contact', 'App\Observers\ContactObserver@creating'
		);
		$event->listen(
			'eloquent.updating: Contact', 'App\Observers\ContactObserver@updating'
		);
		$event->listen(
			'eloquent.deleting: Contact', 'App\Observers\ContactObserver@deleting'
		);
	}

	public function creating($contact)
	{
		if(Auth::check()) {
			$contact->created_by = Auth::id();
			$contact->last_modified_by = Auth::id();
		}
	}

	public function updating($contact)
	{
		if(Auth::check()) {
			$contact->last_modified_by = Auth::id();
		}
	}

	public function deleting($contact)
	{
		$linkedJobs = $contact->jobs()->with([
			'contacts' => function($query) use($contact) {
				$query->where('contacts.id', '<>', $contact->id);
			}
		])->get();


		foreach ($linkedJobs as $job) {
			if(!$job->contacts->count() && !$job->contact_same_as_customer) {
				Job::where('id', $job->id)
					->update([
						'contact_same_as_customer' => true
					]);
			}
		}

		$contact->jobs()->detach();

		if(Auth::check()) {
			$contact->deleted_by = Auth::id();
			$contact->save();
		}
	}
}