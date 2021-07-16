<?php

namespace App\Transformers;

use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOptimized;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;

class EmailsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['to', 'cc', 'bcc', 'customer', 'jobs', 'attachments', 'recipients'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['attachments', 'createdBy'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($email)
    {
        return [
            'id' => $email->id,
            'subject' => $email->subject,
            'content' => $email->content,
            'from' => $email->from,
            'type' => $email->type,
            'status' => $email->status,
            'is_read' => $email->is_read,
            'created_at' => $email->created_at,
            'updated_at' => $email->created_at,
            // 'count'      => $email->getThreadCount(),
            'count' => $email->thread_count,
            'thread_id' => $email->conversation_id,
            'label' => ($email->label) ? $email->label->name : null,
            'label_id' => ($email->label) ? $email->label->id : null,
            'is_moved' => $email->is_moved,
        ];
    }

    /**
     * Include 'to' recipients
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTo($email)
    {
        $emails = $email->recipientsTo->pluck('email')->toArray();
        return $this->item($emails, function ($emails) {
            return $emails;
        });
    }

    /**
     * Include 'cc' recipients
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCc($email)
    {
        $emails = $email->recipientsCc->pluck('email')->toArray();
        return $this->item($emails, function ($emails) {
            return $emails;
        });
    }

    /**
     * Include 'bcc' recipients
     *
     * @return League\Fractal\ItemResource
     */
    public function includeBcc($email)
    {
        $emails = $email->recipientsBcc->pluck('email')->toArray();
        return $this->item($emails, function ($emails) {
            return $emails;
        });
    }

    /**
     * Include attachments
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAttachments($email)
    {
        $attachments = $email->attachments;
        return $this->collection($attachments, function ($attachment) {
            return [
                'id' => $attachment->id,
                'parent_id' => $attachment->parent_id,
                'name' => $attachment->name,
                'size' => $attachment->size,
                'path' => $attachment->path,
                'mime_type' => $attachment->mime_type,
                'meta' => $attachment->meta,
                'created_at' => $attachment->created_at,
                'updated_at' => $attachment->updated_at,
                'url' => $attachment->url,
                'thumb_url' => $attachment->thumb_url,
            ];
        });
    }

    /**
     * Include customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer($email)
    {
        $customer = $email->customer;
        if ($customer) {
            return $this->item($customer, new CustomersTransformerOptimized);
        }
    }

    /**
     * Include job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobs($email)
    {
        $jobs = $email->jobs;
        if ($jobs) {
            $transformer = new JobsTransformerOptimized;
            $transformer->setDefaultIncludes([
                'customer',
                'address',
                'current_stage',
                'parent',
                'resource_ids',
            ]);

            return $this->collection($jobs, $transformer);
        }
    }

    /**
     * Include CreatedBy
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($email)
    {
        $user = $email->createdBy;
        if ($user) {
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    /**
     * Include Replies
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReplies($email)
    {
        $replies = $email->replies;
        if ($replies) {
            return $this->collection($replies, new EmailsTransformer);
        }
    }

    public function includeRecipients($email)
    {
        $recipients = $email->recipients;
        return $this->collection($recipients, function($recipient){
            return [
                'email'  => $recipient->email,
                'type'   => $recipient->type,
                'delivery_date_time' => $recipient->delivery_date_time,
                'bounce_date_time'   => $recipient->bounce_date_time,
            ];
        });
    }
}
