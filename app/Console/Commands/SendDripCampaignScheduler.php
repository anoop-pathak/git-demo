<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Carbon\Carbon;
use App\Events\DripCampaigns\SendDripCampaignSchedulers;
use App\Services\Emails\EmailServices;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\DripCampaign;
use App\Models\DripCampaignScheduler;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\Recurr\RecurrService;
use App;
use Illuminate\Support\Facades\Event;

class SendDripCampaignScheduler extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:send_drip_campaign_scheduler';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send DripCamaign Schedulers To Customer.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->schedulerModel = app(DripCampaignScheduler::class);
		$this->emailService   = app(EmailServices::class);
		$this->model = app(DripCampaign::class);
		parent::__construct();
	}

	/**
	 * When a command should run
	 *
	 * @param Schedulable $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
	public function schedule(Schedulable $scheduler)
	{
		return $scheduler->daily()->hours(8);
	}

	protected function getArguments()
    {
        return array(
      		array('drip_campaign_id', InputArgument::OPTIONAL, 'drip_campaign_id'),
      		array('date', InputArgument::OPTIONAL, 'date')
    	);
    }

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		try {
			$dripCampaignId = $this->argument('drip_campaign_id');
			$date   = $this->argument('date');

			if(!$date) {
				$date = Carbon::now()->toDateString();
			}

			$builder = $this->schedulerModel->whereNull('canceled_at')
						->whereStatusReady()
						->where('schedule_date_time', $date)
						->with(['dripCampaign', 'dripCampaign.email', 'dripCampaign.email.recipients', 'dripCampaign.email.attachments']);

			if($dripCampaignId) {
				$builder->whereDripCampaignId($dripCampaignId);
			}

			$campaignSchedulers = $builder->get();

			foreach ($campaignSchedulers as $key => $scheduler) {
				$campaign = $scheduler->dripCampaign;
				setAuthAndScope($campaign->created_by);
				$dripCampaignEmailModel   = $campaign->email;
				$campaignEmailRecipients  = $dripCampaignEmailModel->recipients;
				$campaignEmailAttachments = $dripCampaignEmailModel->attachments;

				if (!$campaign->occurence) {
					$this->createSchedulerForNextDay($campaign);
				}

				if ($scheduler) {
					$this->sendMail($campaign, $dripCampaignEmailModel, $campaignEmailRecipients, $campaignEmailAttachments, $scheduler);
				}
			}

		} catch (Exception $e) {
			Log::error($e);
		}
	}

	private function sendMail($campaign, $dripCampaignEmailModel, $campaignEmailRecipients, $campaignEmailAttachments, $scheduler = null)
	{
		try {

			$format = config('jp.date_format');
			$startDateTime = Carbon::now();
			$startDateTime = Carbon::parse($scheduler->schedule_date_time)->format($format);

			$recipients = [];
			foreach ($campaignEmailRecipients as $campaignEmailRecipient) {
				$recipients[$campaignEmailRecipient->type][] = $campaignEmailRecipient->email;
			}

			$emailAttachments = [];
			foreach ($campaignEmailAttachments as $campaignEmailAttachment) {
				$emailAttachments[] = [
					'type'  => 'drip_campaign_email_attachment',
					'value' => $campaignEmailAttachment['id']
				];
			}

			$meta = [
				'job_id' => $campaign->job_id,
				'customer_id' => $campaign->customer_id
			];

			$email = $this->emailService->sendEmail(
				$dripCampaignEmailModel['subject'],
				$dripCampaignEmailModel['content'],
				(array)$recipients['to'],
				array_key_exists('cc', $recipients) ? (array)$recipients['cc'] : [],
				array_key_exists('bcc', $recipients) ? (array)$recipients['bcc'] : [],
				(array)$emailAttachments,
				Auth::id(),
				$meta
			);

			$scheduler->update([
				'status' => DripCampaignScheduler::STATUS_SUCCESS,
				'status_updated_at' => Carbon::now(),
				'outcome_id'  => $email->id
			]);

			$schedulers = DripCampaignScheduler::whereDripCampaignId($campaign->id)->whereStatusReady()->count();
			if ($schedulers >= 1) {
				$campaign->update([
					'status' => DripCampaign::STATUS_IN_PROCESS
				]);
			} else {
				$campaign->update([
					'status' => DripCampaign::STATUS_CLOSED
				]);
			}

		} catch (Exception $e) {
			$scheduler->update([
				'status' => DripCampaignScheduler::STATUS_FAILED,
				'failed_reason' => $e->getMessage(),
				'status_updated_at' => Carbon::now(),
			]);

			$campaign->update([
				'status' => DripCampaign::STATUS_FAILED
			]);
		}

		Event::fire('JobProgress.DripCampaigns.Events.SendDripCampaignSchedulers', new SendDripCampaignSchedulers($campaign));
	}

	private function createSchedulerForNextDay($campaign)
	{
		$medium = DripCampaignScheduler::MEDIUM_EMAIL;
		$status = DripCampaignScheduler::STATUS_READY;

		$service = App::make(RecurrService::class);
		$recurRule = $service->getCampaignRecurringRule($campaign);
		$nextDate  = $service->createDripCampaignSchedulerForNextDay($recurRule, $campaign, $medium, $status);
		DripCampaignScheduler::insert($nextDate);
	}
}
