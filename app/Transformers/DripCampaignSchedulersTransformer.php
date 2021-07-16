<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use Carbon\Carbon;

class DripCampaignSchedulersTransformer extends TransformerAbstract {

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['email_detail'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($schedulers) {
        $scheduleDate = null;
        if($schedulers->schedule_date_time) {
            $scheduleDate = Carbon::parse($schedulers->schedule_date_time)->format('Y-m-d');
        }
		return [
            'id'                 => $schedulers->id,
            'drip_campaign_id'   => $schedulers->drip_campaign_id,
            'schedule_date'      => $scheduleDate,
            'status_updated_at'  => $schedulers->status_updated_at,
            'failed_reason'      => $schedulers->failed_reason,
            'status'      => $schedulers->status,
            'created_at'  => $schedulers->created_at,
            'updated_at'  => $schedulers->updated_at,
		];
	}

    public function includeEmailDetails($schedulers) {
        $mail = $schedulers->emailDetail;
        if($mail){
            return $this->item($mail, function($item) {

                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'status' => $item->status,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at
                ];
            });
            // $mailTrans = new EmailsTransformer;
            // $mailTrans->setDefaultIncludes(['to','cc','bcc','attachments', 'recipients']);

            // return $this->item($mail, $mailTrans);
        }
    }
}