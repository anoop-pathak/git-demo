<?php
namespace App\Handlers\Events;

use Log;
use Mail;
use App\Models\Company;
use App\Models\Trade;
use Exception;

class NewTradesAssignedBySystemQueueHandler
{
	public function fire($queue, $data)
	{
		$company = Company::find($data['company_id']);
		$owner = $company->subscriber;
		$tradeNames = Trade::whereIn('id', $data['trade_ids'])
			->orderBy('name')
			->pluck('name')
            ->toArray();

		try {
			Mail::send('emails.owner.system-assigned-new-trades', [
				'owner'    => $owner,
				'tradeNames' => $tradeNames,
			], function($message) use($owner){
				$subject = "JobProgress Trade Type Update Alert";

				$message->to($owner->email);
				$message->subject($subject);
			});
		} catch(Exception $e) {
			Log::info('New trades assigned by system email error: '. getErrorDetail($e));
		}

		$queue->delete();
	}
}