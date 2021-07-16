<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class EmailTemplatesTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'to',
        'cc',
        'bcc'
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'attachments',
        'stage',
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($emailTemplate)
    {
        return [
            'id' => $emailTemplate->id,
            'title' => $emailTemplate->title,
            'template' => $emailTemplate->template,
            'active' => $emailTemplate->active,
            'subject' => $emailTemplate->subject,
            'send_to_customer' => (bool)$emailTemplate->send_to_customer,
            'recipients_setting' => $emailTemplate->recipients_setting,
        ];
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAttachments($emailTemplate)
    {
        $attachments = $emailTemplate->attachments;
        if ($attachments) {
            return $this->collection($attachments, new ResourcesTransformer);
        }
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeStage($emailTemplate)
    {
        $stage = $emailTemplate->stage;
        if ($stage) {
            return $this->item($stage, function ($stage) {
                return $stage->toArray();
            });
        }
    }

    /**
     * Include to
     * @param  object $emailTemplate instance of email template
     * @return collection of to emails
     */
    public function includeTo($emailTemplate)
    {
        $to = $emailTemplate->to->pluck('email')->toArray();

        return $this->primitive(['data' => $to]);
    }

    /**
     * Include Bcc
     * @param  object $emailTemplate instance of email template
     * @return collection of bcc emails
     */
    public function includeBcc($emailTemplate)
    {
        $bcc = $emailTemplate->bcc->pluck('email')->toArray();

        return $this->primitive(['data' => $bcc]);
    }

    /**
     * Include Cc
     * @param  object $emailTemplate instance of email template
     * @return collection of cc emails
     */
    public function includeCc($emailTemplate)
    {
        $cc = $emailTemplate->cc->pluck('email')->toArray();

        return $this->primitive(['data' => $cc]);
    }
}
