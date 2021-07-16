<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class DripCampaignEmailTransformer extends TransformerAbstract {

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['recipients', 'attachments'];

	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($dripCampaignEmail) {
        return [
            'id' => $dripCampaignEmail->id,
            'email_template_id' => $dripCampaignEmail->email_template_id,
            'subject' => $dripCampaignEmail->subject,
            'content' => $dripCampaignEmail->content,
            'created_at' => $dripCampaignEmail->created_at,
            'updated_at' => $dripCampaignEmail->updated_at
        ];
    }

    public function includeRecipients($dripCampaignEmail) {
        $recipients = $dripCampaignEmail->recipients;
        if ($recipients) {
            return $this->collection($recipients, function($recipients) {
                return[
                    'email_campaign_id'  => $recipients->email_campaign_id,
                    'type' => $recipients->type,
                    'email' => $recipients->email,
                ];
            });
        }
    }

   public function includeAttachments($dripCampaignEmail) {
        $attachments = $dripCampaignEmail->attachments;
        return $this->collection($attachments, function($attachment){
            return [
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
        });
    }
}