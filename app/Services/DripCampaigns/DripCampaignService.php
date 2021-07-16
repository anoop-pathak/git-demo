<?php
namespace App\Services\DripCampaigns;

use App\Repositories\DripCampaignRepository;
use App\Events\DripCampaigns\DripCampaignCreated;
use App\Events\DripCampaigns\DripCampaignCanceled;
use App\Services\DripCampaigns\DripCampaignEmailService;
use App\Services\Recurr\RecurrService;
use App\Services\Contexts\Context;
use Carbon\Carbon;
use App\Models\DripCampaignScheduler;
use App\Models\DripCampaign;
use Exception;
use Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DripCampaignService {

	public function __construct(DripCampaignRepository $repo,
							RecurrService $recurr,
							Context $scope,
                            DripCampaignScheduler $dripCampaignScheduler,
							DripCampaignEmailService $campaignEmailService)
	{
		$this->repo = $repo;
		$this->recurrService = $recurr;
		$this->scope = $scope;
		$this->dripCampaignScheduler = $dripCampaignScheduler;
		$this->campaignEmailService  = $campaignEmailService;
	}

	/**
	 * Save Drip Campaign
	 * @param  Array $dripCampaignData  Drip Campaign Data
	 * @param  array  $emails           Emails Campaign data
	 * @return DripCampaign
	 */
	public function save($dripCampaignData, $emailCampaign = array(), $emailRecipentsTo = array(), $emailRecipentsCC = array(), $emailRecipentsBCC = array(), $emailAttachments = array())
	{
		DB::beginTransaction();
		try {
			$dripCampaign = $this->repo->createDripCampaign($dripCampaignData);
			$this->saveEmailCampaign($dripCampaign, $emailCampaign, $emailRecipentsTo, $emailRecipentsCC, $emailRecipentsBCC, $emailAttachments);
			$this->saveSchedulers($dripCampaign);

			DB::commit();
		} catch(Exception $e) {
			DB::rollback();
			throw $e;
		}

		Event::fire('JobProgress.DripCampaigns.Events.DripCampaignCreated', new DripCampaignCreated($dripCampaign));

		return $dripCampaign;
	}

	public function cancelDripCampaign($dripCampaign, $meta = array())
	{
		DB::beginTransaction();
		try {
			$carbonNow = Carbon::now();
			$schedulers = DripCampaignScheduler::whereDripCampaignId($dripCampaign->id)->whereStatusReady()->count();
			if($schedulers) {
				DripCampaignScheduler::whereDripCampaignId($dripCampaign->id)
								->whereStatusReady()
								->update([
								'canceled_at' => $carbonNow,
								'status'	  => DripCampaignScheduler::STATUS_CANCELED,
								'status_updated_at' => Carbon::now()
							]);
			}

			$dripCampaign->whereIn('status', [DripCampaign::STATUS_READY, DripCampaign::STATUS_IN_PROCESS])
						->where('id', $dripCampaign->id)
						->update([
							'canceled_date_time' => $carbonNow,
							'canceled_note' => ine($meta, 'cancel_note') ? $meta['cancel_note'] : null,
							'canceled_by'   => Auth::id(),
							'status'        => DripCampaign::STATUS_CANCELED
						]);

			DB::commit();
		} catch(Exception $e) {
			DB::rollback();
			throw $e;
		}

		Event::fire('JobProgress.DripCampaigns.Events.DripCampaignCanceled', new DripCampaignCanceled($dripCampaign));

		return true;
	}

	/***********Private Methods *************/

	private function saveSchedulers($dripCampaign)
	{
		$status     = DripCampaignScheduler::STATUS_READY;
		$mediumType = DripCampaignScheduler::MEDIUM_EMAIL;
		$recurringDates = $this->recurrService->getDripCampaignRecurring($dripCampaign, $status, $mediumType);
		DripCampaignScheduler::insert($recurringDates);
	}

	private function saveEmailCampaign($dripCampaign, $emailCampaign, $emailRecipentsTo, $emailRecipentsCC, $emailRecipentsBCC, $emailAttachments)
	{
		if(!is_array($emailCampaign)) return $dripCampaign;
		$email = $this->campaignEmailService->createDripCampaignEmail($dripCampaign, $emailCampaign, $emailRecipentsTo, $emailRecipentsCC, $emailRecipentsBCC, $emailAttachments);

		return $email;
	}

	public function cancelJobCampaign($jobId)
	{
		 $dripCampaigns = DripCampaign::where('job_id', $jobId)->whereIn('status', [DripCampaign::STATUS_READY, DripCampaign::STATUS_IN_PROCESS])->get();
		foreach ($dripCampaigns as $dripCampaign) {
			$meta = [
				'cancel_note' => 'This campaign is getting deleted due to its job deletion'
			];
			$this->cancelDripCampaign($dripCampaign, $meta);
        }
	}

	public function cancelCustomerCampaign($customerId)
	{
		$dripCampaigns = DripCampaign::where('customer_id', $customerId)->whereIn('status', [DripCampaign::STATUS_READY, DripCampaign::STATUS_IN_PROCESS])->get();
		foreach ($dripCampaigns as $dripCampaign) {
			$meta = [
				'cancel_note' => 'This campaign is getting deleted due to its customer deletion'
			];

			$this->cancelDripCampaign($dripCampaign, $meta);
        }
	}

	public function cancelCompanyCampaign($companyId)
	{
		$dripCampaigns = DripCampaign::where('company_id', $companyId)->whereIn('status', [DripCampaign::STATUS_READY, DripCampaign::STATUS_IN_PROCESS])->get();
		foreach ($dripCampaigns as $dripCampaign) {
			$meta = [
				'cancel_note' => 'This campaign is getting deleted due to its company deletion'
			];

			$this->cancelDripCampaign($dripCampaign, $meta);
        }
	}

}