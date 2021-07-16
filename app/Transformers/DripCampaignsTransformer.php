<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOtimized;
use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOtimized;
use App\Transformers\DripCampaignSchedulersTransformer;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use App\Models\DripCampaign;

class DripCampaignsTransformer extends TransformerAbstract {

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
    protected $availableIncludes = ['customer', 'job', 'email', 'drip_campaign_schedulers', 'created_by', 'canceled_by'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($campaign) {
         //get occurence
        $occurence = null;
        if($campaign->occurence) {
            $occurence = (string)$campaign->occurence;
        } elseif($campaign->until_date) {
            $occurence = DripCampaign::OCCURANCE_UNTIL_DATE;
        } else {
            $occurence = DripCampaign::OCCURANCE_NEVER_END;
        }

        return [
            'id'                        =>  $campaign->id,
            'customer_id'               =>  $campaign->customer_id,
            'job_id'                    =>  $campaign->job_id,
            'name'                      =>  (string)$campaign->name,
            'repeat'                    =>  (string)$campaign->repeat,
            'interval'                  =>  (int)$campaign->interval,
            'occurence'                 =>  $occurence,
            'by_day'                    =>  (array)$campaign->by_day,
            'until_date'                =>  $campaign->until_date ?: "",
            'job_current_stage_code'    =>  (string)$campaign->job_current_stage_code,
            'job_end_stage_code'        =>  (string)$campaign->job_end_stage_code,
            'status'                    =>  (string)$campaign->status,
            'canceled_note'             =>  (string)$campaign->canceled_note,
            'created_at'                =>  $campaign->created_at,
            'updated_at'                =>  $campaign->updated_at
        ];
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer( $campaign )
    {
        $customer = $campaign->customer;
        if($customer){
            return $this->item($customer, new CustomersTransformerOtimized);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($campaign)
    {
        $job = $campaign->job;
        if($job){
            $jobsTrans = new JobsTransformerOtimized;
            $jobsTrans->setDefaultIncludes([]);

            return $this->item($job, $jobsTrans);
        }
    }

    public function includeEmail($campaign)
    {
        $email = $campaign->email;
        if ($email) {
            $includes = (array) \Request::get('includes');
            return $this->item($email, function($item) use($includes) {

                $data = [
                    'id' => $item->id,
                    'email_template_id' => $item->email_template_id,
                    'subject' => $item->subject,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at
                ];

                if(in_array('email.content', $includes)) {
                    $data['content'] = $item->content;
                }

                if(in_array('email.recipients', $includes) && $recipients = $item->recipients) {
                    $data['recipients'] = $this->getEmailRecipients($recipients);
                }

                if(in_array('email.attachments', $includes) && $attachments = $item->attachments) {
                    $data['attachments'] = $this->getEmailAttachments($attachments);
                }

                return $data;
            });
            // return $this->item($email, new DripCampaignEmailTransformer);
        }
    }

    public function includeDripCampaignSchedulers($campaign)
    {
        $schedulers = $campaign->schedulers;
        if ($schedulers) {
            $schedulerTrans = new DripCampaignSchedulersTransformer;
            $schedulerTrans->setDefaultIncludes(['email_details']);

            return $this->collection($schedulers, $schedulerTrans);
        }
    }

    public function includeCreatedBy($campaign)
    {
        $user = $campaign->createdBy;
        if($user) {

            return $this->item($user, new UsersTransformerOptimized);    
        }
    }

    public function includeCanceledBy($campaign)
    {
        $user = $campaign->canceledBy;
        if($user) {

            return $this->item($user, new UsersTransformerOptimized);    
        }
    }

    private function getEmailRecipients($recipients)
    {
        $data['data'] = [];
        foreach($recipients as $recipient) {
            $data['data'][] = [
                    'email_campaign_id'  => $recipient->email_campaign_id,
                    'type' => $recipient->type,
                    'email' => $recipient->email,
                ];
        }
        return $data;
    }

    private function getEmailAttachments($attachments)
    {
        $data['data'] = [];
        foreach($attachments as $attachment) {
            $data['data'][] = [
                'id'                     => $attachment->id,
                'parent_id'              => $attachment->parent_id,
                'name'                   => $attachment->name,
                'size'                   => $attachment->size,
                'path'                   => $attachment->path,
                'mime_type'              => $attachment->mime_type,
                'meta'                   => $attachment->meta,
                'created_at'             => $attachment->created_at,
                'updated_at'             => $attachment->updated_at,
                'url'                    => $attachment->url,
                'thumb_url'              => $attachment->thumb_url,
            ];
        }
        return $data;
    }
}