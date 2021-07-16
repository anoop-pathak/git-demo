<?php
namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class FinancialAccountObserver {

	//here is the listener
	public function subscribe( $event ){
		$event->listen('eloquent.deleting: FinancialAccount', 'App\Observers\FinancialAccountObserver@deleting');
		$event->listen('eloquent.creating: FinancialAccount', 'App\Observers\FinancialAccountObserver@creating');
		$event->listen('eloquent.updating: FinancialAccount', 'App\Observers\FinancialAccountObserver@updating');
	}

	//before delete
	public function deleting($financialAccount)
	{
		$financialAccount->deleted_by = Auth::id();
		$financialAccount->save();
	}

	//before created
	public function creating($financialAccount)
	{
		if(Auth::check()) {
			$financialAccount->created_by = Auth::id();
			$financialAccount->updated_by = Auth::id();
		}
	}

	//before updated
	public function updating($financialAccount)
	{
        $financialAccount->updated_by = Auth::id();

		if($financialAccount->level < $financialAccount->getOriginal('level')) {
			$this->decrementLevelSubAccount($financialAccount->id, $financialAccount->level);
		}
	}

	/**
	 * Decrement Level Sub Account
	 * @param  Int    $accountId Account Id
	 * @return Booealn
	 */
	private function decrementLevelSubAccount($accountId, $level = 0)
	{
		$repo = \App::make('App\Repositories\FinancialAccountRepository');
		$account = $repo->make()
			->where('parent_id', $accountId)
			->withTrashed()
			->first();
		if($account) {
			$repo->make()->where('parent_id', $accountId)->withTrashed()->update(['level' => $level + 1]);
			$account = $repo->make()->where('parent_id', $accountId)->first();
			if($account) {
				$this->decrementLevelSubAccount($account->id, $account->level);
			}
		}

		return false;
	}
}